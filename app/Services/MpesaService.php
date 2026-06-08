<?php

namespace App\Services;

use App\Models\MpesaPayment;
use Illuminate\Support\Facades\Http;

class MpesaService
{
    protected string $baseUrl;
    protected string $consumerKey;
    protected string $consumerSecret;
    protected string $shortCode;
    protected string $passkey;
    protected bool $verifySsl;

    public function __construct()
    {
        $env = config('mpesa.environment', 'sandbox');
        $this->baseUrl = $env === 'production' 
            ? 'https://api.safaricom.co.ke' 
            : 'https://sandbox.safaricom.co.ke';
        
        $this->consumerKey = config('mpesa.consumer_key');
        $this->consumerSecret = config('mpesa.consumer_secret');
        $this->shortCode = config('mpesa.short_code');
        $this->passkey = config('mpesa.passkey');
        $this->verifySsl = config('mpesa.verify_ssl', true);
    }

    /**
     * Generate OAuth token from M-PESA
     */
    public function getAccessToken(): string
    {
        try {
            $request = Http::timeout(10)
                ->withBasicAuth($this->consumerKey, $this->consumerSecret);
            
            if (!$this->verifySsl) {
                $request = $request->withoutVerifying();
            }
            
            $response = $request->get("{$this->baseUrl}/oauth/v1/generate?grant_type=client_credentials");

            if (!$response->successful()) {
                \Log::error('M-Pesa Token Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to get M-Pesa token: ' . $response->body());
            }

            return $response->json()['access_token'];
        } catch (\Exception $e) {
            \Log::error('M-Pesa getAccessToken Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Initiate STK Push (Lipa Na M-PESA Online)
     */
    public function initiateStKPush(MpesaPayment $payment): array
    {
        try {
            $token = $this->getAccessToken();
            $timestamp = date('YmdHis');
            $password = base64_encode($this->shortCode . $this->passkey . $timestamp);
            $formattedPhone = $this->formatPhoneNumber($payment->phone_number);

            \Log::info('M-Pesa STK Push Request', [
                'phone' => $formattedPhone,
                'amount' => $payment->amount,
                'short_code' => $this->shortCode,
                'callback_url' => route('api.mpesa.callback'),
            ]);

            $response = Http::timeout(30)
                ->withToken($token);
            
            if (!$this->verifySsl) {
                $response = $response->withoutVerifying();
            }
            
            $response = $response->post("{$this->baseUrl}/mpesa/stkpush/v1/processrequest", [
                    'BusinessShortCode' => $this->shortCode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => (int)$payment->amount,
                    'PartyA' => $formattedPhone,
                    'PartyB' => $this->shortCode,
                    'PhoneNumber' => $formattedPhone,
                    'CallBackURL' => config('mpesa.callback_url'),
                    'AccountReference' => "Sale-{$payment->id}",
                    'TransactionDesc' => "POS Sale Payment",
                ]);

            $responseData = $response->json();

            \Log::info('M-Pesa STK Push Response', [
                'response_code' => $responseData['ResponseCode'] ?? 'unknown',
                'response' => $responseData
            ]);

            return $responseData;
        } catch (\Exception $e) {
            \Log::error('M-Pesa STK Push Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    /**
     * Query transaction status
     */
    public function queryTransactionStatus(string $checkoutRequestId): array
    {
        $token = $this->getAccessToken();
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortCode . $this->passkey . $timestamp);

        $response = Http::withToken($token);
        
        if (!$this->verifySsl) {
            $response = $response->withoutVerifying();
        }
        
        $response = $response->post("{$this->baseUrl}/mpesa/stkpushquery/v1/query", [
                'BusinessShortCode' => $this->shortCode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ]);

        return $response->json();
    }

    /**
     * Process callback from M-PESA
     */
    public function processCallback(array $data): void
    {
        $callbackData = $data['Body']['stkCallback'];
        $resultCode = $callbackData['ResultCode'];
        $checkoutRequestId = $callbackData['CheckoutRequestId'];

        // Find the payment by checkout request ID
        $payment = MpesaPayment::where('transaction_code', $checkoutRequestId)->first();

        if (!$payment) {
            return;
        }

        if ($resultCode == 0) {
            // Success
            $transactionCode = $callbackData['CallbackMetadata']['Item'][1]['Value'] ?? null;
            $amount = $callbackData['CallbackMetadata']['Item'][0]['Value'] ?? null;

            $payment->update([
                'transaction_code' => $transactionCode,
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'response_data' => json_encode($callbackData),
            ]);
        } else {
            // Failed
            $errorMessage = $this->getErrorMessage($resultCode);
            $payment->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $errorMessage,
            ]);

            // Cancel the sale if payment failed
            if ($payment->sale) {
                $payment->sale->update(['status' => 'cancelled']);
            }
        }
    }

    /**
     * Process refund (reverse transaction)
     */
    public function refundPayment(string $transactionId, float $amount): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token);
        
        if (!$this->verifySsl) {
            $response = $response->withoutVerifying();
        }
        
        $response = $response->post("{$this->baseUrl}/mpesa/reversal/v1/request", [
                'Initiator' => config('services.mpesa.initiator_name'),
                'SecurityCredential' => $this->encryptSecurityCredential(),
                'CommandID' => 'TransactionReversal',
                'TransactionID' => $transactionId,
                'Amount' => (int)$amount,
                'ReceiverParty' => $this->shortCode,
                'RecieverIdentifierType' => '4',
                'ResultURL' => route('api.mpesa.reversal-callback'),
                'QueueTimeOutURL' => route('api.mpesa.timeout-callback'),
                'Remarks' => 'POS Refund',
                'Occasion' => 'Sales Refund',
            ]);

        return $response->json();
    }

    /**
     * Get M-PESA account balance
     */
    public function getAccountBalance(): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token);
        
        if (!$this->verifySsl) {
            $response = $response->withoutVerifying();
        }
        
        $response = $response->post("{$this->baseUrl}/mpesa/accountbalance/v1/query", [
                'Initiator' => config('services.mpesa.initiator_name'),
                'SecurityCredential' => $this->encryptSecurityCredential(),
                'CommandID' => 'GetAccount',
                'PartyA' => $this->shortCode,
                'IdentifierType' => '4',
                'ResultURL' => route('api.mpesa.balance-callback'),
                'QueueTimeOutURL' => route('api.mpesa.timeout-callback'),
                'Remarks' => 'Check Balance',
            ]);

        return $response->json();
    }

    /**
     * Format phone number to 254XXXXXXXXX format
     */
    protected function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 1) === '0') {
            $phone = '254' . substr($phone, 1);
        } elseif (substr($phone, 0, 3) !== '254') {
            $phone = '254' . $phone;
        }

        return $phone;
    }

    /**
     * Encrypt security credential (placeholder - requires proper implementation)
     */
    protected function encryptSecurityCredential(): string
    {
        // This is a placeholder. In production, implement proper encryption
        // using the public key provided by M-PESA
        return config('services.mpesa.security_credential');
    }

    /**
     * Get user-friendly error message
     */
    protected function getErrorMessage(int $resultCode): string
    {
        $messages = [
            1 => 'Insufficient funds',
            2 => 'Less amount than expected',
            3 => 'More amount than expected',
            4 => 'Bill information is invalid',
            5 => 'Bill is not due for collection',
            6 => 'Customer rejected the payment',
            7 => 'Invalid phone number format',
            8 => 'Invalid phone number',
            9 => 'Your phone number does not have enough credit to pay the bill',
            10 => 'Payment timeout',
            11 => 'Payment was cancelled',
            12 => 'Duplicate payment attempt',
            13 => 'Unspecified payment failure',
        ];

        return $messages[$resultCode] ?? 'Payment failed. Please try again.';
    }
}
