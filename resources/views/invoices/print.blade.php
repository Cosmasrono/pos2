<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #fff;
            padding: 20px;
        }
        .invoice-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
        }
        .invoice-header {
            border-bottom: 3px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .invoice-title {
            font-size: 36px;
            font-weight: bold;
            color: #333;
        }
        .company-info {
            text-align: right;
        }
        .invoice-details {
            margin-bottom: 30px;
        }
        .bill-to {
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            margin-bottom: 20px;
        }
        th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #333;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
        }
        .text-right {
            text-align: right;
        }
        .totals-section {
            margin-top: 30px;
            float: right;
            width: 350px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        .total-amount {
            font-size: 20px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }
        .payment-info {
            clear: both;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
        }
        .print-btn {
            text-align: center;
            margin-bottom: 20px;
        }
        @media print {
            body {
                padding: 0;
            }
            .print-btn {
                display: none;
            }
            .invoice-container {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-btn">
        <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">Back to Invoice</a>
    </div>

    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="row">
                <div class="col-md-6">
                    <div class="invoice-title">INVOICE</div>
                    <p class="mb-0 mt-2">{{ $invoice->invoice_number }}</p>
                </div>
                <div class="col-md-6 company-info">
                    <h4>RETAIL POS</h4>
                    <p class="mb-0">Your Business Address</p>
                    <p class="mb-0">Phone: +254 XXX XXX XXX</p>
                    <p class="mb-0">Email: info@retailpos.com</p>
                </div>
            </div>
        </div>

        <!-- Invoice Details and Bill To -->
        <div class="row invoice-details">
            <div class="col-md-6 bill-to">
                <h5>BILL TO:</h5>
                <p class="mb-1"><strong>{{ $invoice->customer->name }}</strong></p>
                @if($invoice->customer->phone)
                    <p class="mb-1">{{ $invoice->customer->phone }}</p>
                @endif
                @if($invoice->customer->email)
                    <p class="mb-1">{{ $invoice->customer->email }}</p>
                @endif
                @if($invoice->customer->address)
                    <p class="mb-0">{{ $invoice->customer->address }}</p>
                @endif
            </div>
            <div class="col-md-6 text-end">
                <table class="table table-borderless" style="width: auto; margin-left: auto;">
                    <tr>
                        <td><strong>Invoice Date:</strong></td>
                        <td>{{ $invoice->issue_date->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Due Date:</strong></td>
                        <td>{{ $invoice->due_date->format('M d, Y') }}</td>
                    </tr>
                    @if($invoice->payment_terms)
                        <tr>
                            <td><strong>Payment Terms:</strong></td>
                            <td>{{ $invoice->payment_terms }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="status-badge" style="background-color: 
                                @if($invoice->status === 'paid') #28a745
                                @elseif($invoice->status === 'overdue') #dc3545
                                @elseif($invoice->status === 'partial') #ffc107
                                @elseif($invoice->status === 'sent') #17a2b8
                                @else #6c757d
                                @endif; color: white;">
                                {{ strtoupper($invoice->status) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Invoice Items -->
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50%;">DESCRIPTION</th>
                    <th class="text-right" style="width: 10%;">QTY</th>
                    <th class="text-right" style="width: 15%;">UNIT PRICE</th>
                    <th class="text-right" style="width: 10%;">DISCOUNT</th>
                    <th class="text-right" style="width: 15%;">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">KSh {{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">KSh {{ number_format($item->discount_per_item, 2) }}</td>
                        <td class="text-right">KSh {{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>KSh {{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            
            @if($invoice->tax_amount > 0)
                <div class="total-row">
                    <span>Tax:</span>
                    <span>KSh {{ number_format($invoice->tax_amount, 2) }}</span>
                </div>
            @endif

            @if($invoice->discount_amount > 0)
                <div class="total-row">
                    <span>Discount:</span>
                    <span>-KSh {{ number_format($invoice->discount_amount, 2) }}</span>
                </div>
            @endif

            <div class="total-row total-amount">
                <span>TOTAL:</span>
                <span>KSh {{ number_format($invoice->total_amount, 2) }}</span>
            </div>

            @if($invoice->amount_paid > 0)
                <div class="total-row" style="color: #28a745;">
                    <span>Amount Paid:</span>
                    <span>KSh {{ number_format($invoice->amount_paid, 2) }}</span>
                </div>
            @endif

            @if($invoice->balance_due > 0)
                <div class="total-row" style="color: #dc3545; font-weight: bold;">
                    <span>Balance Due:</span>
                    <span>KSh {{ number_format($invoice->balance_due, 2) }}</span>
                </div>
            @endif
        </div>

        <!-- Payment Information -->
        <div class="payment-info">
            <h5>PAYMENT INFORMATION</h5>
            <p>Please make payment to:</p>
            <p class="mb-1"><strong>Bank Name:</strong> Your Bank</p>
            <p class="mb-1"><strong>Account Name:</strong> RETAIL POS</p>
            <p class="mb-1"><strong>Account Number:</strong> XXXX-XXXX-XXXX</p>
            <p class="mb-1"><strong>M-Pesa Paybill:</strong> XXXXX</p>
        </div>

        <!-- Notes -->
        @if($invoice->notes)
            <div style="margin-top: 30px;">
                <h5>NOTES</h5>
                <p>{{ $invoice->notes }}</p>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p class="mb-1">Thank you for your business!</p>
            <p class="mb-0">This is a computer-generated invoice and is valid without signature.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
