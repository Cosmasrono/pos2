<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MpesaPayment;
use App\Models\Sale;
use App\Services\MpesaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    protected MpesaService $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    /**
     * Initiate STK Push payment
     */
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^(254|0)[0-9]{9}$/'],
            'amount' => 'required|numeric|min:1',
            'sale_id' => 'nullable|exists:sales,id',
        ]);

        try {
            Log::info('M-Pesa Initiate Request', [
                'phone' => $validated['phone_number'],
                'amount' => $validated['amount']
            ]);

            // Create M-Pesa payment record
            $payment = MpesaPayment::create([
                'phone_number' => $validated['phone_number'],
                'amount' => $validated['amount'],
                'sale_id' => $validated['sale_id'] ?? null,
                'user_id' => auth()->id(),
                'status' => 'initiated',
                'account_reference' => 'Sale-' . ($validated['sale_id'] ?? time()),
                'transaction_desc' => 'POS Sale Payment',
            ]);

            Log::info('M-Pesa Payment Record Created', ['payment_id' => $payment->id]);

            // Initiate STK Push
            $response = $this->mpesaService->initiateStKPush($payment);

            Log::info('M-Pesa Raw Response', ['response' => $response]);

            // Update payment with M-Pesa response
            if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
                $payment->update([
                    'checkout_request_id' => $response['CheckoutRequestID'] ?? null,
                    'merchant_request_id' => $response['MerchantRequestID'] ?? null,
                    'status' => 'pending',
                ]);

                Log::info('M-Pesa STK Push Success', [
                    'payment_id' => $payment->id,
                    'checkout_request_id' => $response['CheckoutRequestID'] ?? null
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'STK Push sent successfully',
                    'data' => [
                        'payment_id' => $payment->id,
                        'checkout_request_id' => $response['CheckoutRequestID'] ?? null,
                    ],
                ]);
            }

            $errorMsg = $response['CustomerMessage'] ?? $response['errorMessage'] ?? json_encode($response);
            $payment->update(['status' => 'failed']);

            Log::warning('M-Pesa STK Push Failed', [
                'payment_id' => $payment->id,
                'response' => $response,
                'error' => $errorMsg
            ]);

            return response()->json([
                'success' => false,
                'message' => $errorMsg,
                'debug_response' => $response
            ], 400);

        } catch (\Exception $e) {
            Log::error('M-Pesa Initiate Error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle M-Pesa callback
     */
    public function callback(Request $request): JsonResponse
    {
        try {
            Log::info('M-Pesa Callback:', $request->all());

            $this->mpesaService->processCallback($request->all());

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);

        } catch (\Exception $e) {
            Log::error('M-Pesa Callback Error: ' . $e->getMessage());
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Failed']);
        }
    }

    /**
     * Query payment status
     */
    public function status(string $checkoutRequestId): JsonResponse
    {
        try {
            $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestId)->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            // If already confirmed or failed, return status
            if ($payment->status !== 'pending') {
                return response()->json([
                    'success' => true,
                    'status' => $payment->status,
                    'data' => $payment,
                ]);
            }

            // Query M-Pesa for latest status
            $response = $this->mpesaService->queryTransactionStatus($checkoutRequestId);

            if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
                $resultCode = $response['ResultCode'] ?? null;
                
                if ($resultCode == '0') {
                    $payment->update(['status' => 'confirmed']);
                } elseif ($resultCode != null) {
                    $payment->update(['status' => 'failed']);
                }
            }

            return response()->json([
                'success' => true,
                'status' => $payment->status,
                'data' => $payment,
            ]);

        } catch (\Exception $e) {
            Log::error('M-Pesa Status Query Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error checking payment status',
            ], 500);
        }
    }
}
