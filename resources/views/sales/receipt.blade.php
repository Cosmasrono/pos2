<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Receipt #{{ $sale->id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
            padding: 20px;
        }
        .receipt-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .receipt-header h1 {
            font-size: 24px;
            margin: 0;
            font-weight: bold;
        }
        .receipt-header p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        .receipt-info {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .receipt-info div {
            flex: 1;
        }
        .receipt-info strong {
            display: block;
            color: #333;
        }
        table {
            width: 100%;
            font-size: 13px;
            margin-bottom: 15px;
        }
        th {
            border-bottom: 1px solid #333;
            padding: 8px 0;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #333;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 13px;
        }
        .total-amount {
            font-size: 18px;
            font-weight: bold;
            color: #000;
            padding: 10px 0;
        }
        .payment-method {
            margin-top: 15px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
            font-size: 13px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
        .print-btn {
            text-align: center;
            margin-top: 20px;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                max-width: 100%;
            }
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>WING LTD RECEIPT</h1>
            <p>Sales Receipt</p>
            <p>Receipt #{{ $sale->id }}</p>
        </div>

        <div class="receipt-info">
            <div>
                <strong>Date:</strong>
                {{ $sale->created_at->format('M d, Y') }}
            </div>
            <div>
                <strong>Time:</strong>
                {{ $sale->created_at->format('h:i A') }}
            </div>
            <div>
                <strong>Cashier:</strong>
                {{ $sale->cashier->name ?? 'N/A' }}
            </div>
        </div>

        @if($sale->customer)
            <div class="receipt-info">
                <div>
                    <strong>Customer:</strong>
                    {{ $sale->customer->name }}
                </div>
                @if($sale->customer->phone)
                    <div>
                        <strong>Phone:</strong>
                        {{ $sale->customer->phone }}
                    </div>
                @endif
            </div>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">KSh {{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">KSh {{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>KSh {{ number_format($sale->subtotal, 2) }}</span>
            </div>
            
            @if($sale->discount_amount > 0)
                <div class="total-row">
                    <span>Discount:</span>
                    <span>-KSh {{ number_format($sale->discount_amount, 2) }}</span>
                </div>
            @endif

            @if($sale->tax_amount > 0)
                <div class="total-row">
                    <span>Tax:</span>
                    <span>KSh {{ number_format($sale->tax_amount, 2) }}</span>
                </div>
            @endif

            @if($sale->trade_in_amount > 0)
                <div class="total-row">
                    <span>Trade-in Credit:</span>
                    <span class="text-success">-KSh {{ number_format($sale->trade_in_amount, 2) }}</span>
                </div>
            @endif

            <div class="total-row total-amount">
                <span>Total:</span>
                <span>KSh {{ number_format($sale->total_amount, 2) }}</span>
            </div>
        </div>

        <div class="payment-method">
            <strong>Payment Method:</strong>
            <div style="margin-top: 5px;">
                @if($sale->cash_paid > 0)
                    <div>Cash: KSh {{ number_format($sale->cash_paid, 2) }}</div>
                @endif
                @if($sale->mpesa_paid > 0)
                    <div>M-Pesa: KSh {{ number_format($sale->mpesa_paid, 2) }}</div>
                @endif
                @if($sale->card_paid > 0)
                    <div>Card: KSh {{ number_format($sale->card_paid, 2) }}</div>
                @endif
            </div>
            
            @if($sale->change_amount > 0)
                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #ddd;">
                    <strong>Change:</strong> KSh {{ number_format($sale->change_amount, 2) }}
                </div>
            @endif
        </div>

        <div class="footer">
            <p>Thank you for your purchase!</p>
            <p>Please keep this receipt for your records.</p>
        </div>

        <div class="print-btn">
            <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
            <a href="{{ route('sales.index') }}" class="btn btn-secondary">Back to Sales</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
