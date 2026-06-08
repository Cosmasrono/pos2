@extends('layouts.app')

@section('title', 'Sales Receipt')
@section('page-title', 'Receipt')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-body" id="receipt-content">
                <!-- Printable Receipt -->
                <div style="max-width: 400px; margin: 0 auto; font-family: monospace;">
                    <div class="text-center mb-4">
                        <h4>RETAIL POS SYSTEM</h4>
                        <p class="text-muted small">Receipt</p>
                        <hr style="margin: 5px 0;">
                    </div>

                    <div class="mb-3 small">
                        <div class="row">
                            <div class="col-6">
                                <strong>Receipt #:</strong>
                            </div>
                            <div class="col-6 text-end">
                                {{ $sale->receipt_number ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <strong>Date:</strong>
                            </div>
                            <div class="col-6 text-end">
                                {{ $sale->created_at?->format('d/m/Y H:i') ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <strong>Cashier:</strong>
                            </div>
                            <div class="col-6 text-end">
                                {{ $sale->cashier?->name ?? 'N/A' }}
                            </div>
                        </div>
                        @if($sale->customer)
                            <div class="row">
                                <div class="col-6">
                                    <strong>Customer:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    {{ $sale->customer->name }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <hr style="margin: 5px 0;">

                    <table class="w-100 mb-3 small">
                        <thead>
                            <tr style="border-bottom: 1px dashed #000;">
                                <th class="text-start">Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sale->items ?? [] as $item)
                                <tr>
                                    <td class="text-start">{{ $item->product?->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">KES {{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end">KES {{ number_format($item->total_price, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No items</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <hr style="margin: 5px 0;">

                    <div class="mb-3 small">
                        <div class="row">
                            <div class="col-6">
                                <strong>Subtotal:</strong>
                            </div>
                            <div class="col-6 text-end">
                                KES {{ number_format($sale->subtotal ?? 0, 2) }}
                            </div>
                        </div>
                        @if($sale->discount > 0)
                            <div class="row text-info">
                                <div class="col-6">
                                    <strong>Discount:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    -KES {{ number_format($sale->discount, 2) }}
                                </div>
                            </div>
                        @endif
                        <div class="row">
                            <div class="col-6">
                                <strong>Tax (16%):</strong>
                            </div>
                            <div class="col-6 text-end">
                                KES {{ number_format($sale->tax ?? 0, 2) }}
                            </div>
                        </div>
                        @if($sale->trade_in_amount > 0)
                            <div class="row text-success">
                                <div class="col-6">
                                    <strong>Trade-in Credit:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    -KES {{ number_format($sale->trade_in_amount, 2) }}
                                </div>
                            </div>
                        @endif
                        <div class="row" style="border-top: 1px solid #000; padding-top: 5px;">
                            <div class="col-6">
                                <strong>TOTAL:</strong>
                            </div>
                            <div class="col-6 text-end">
                                <strong>KES {{ number_format($sale->total_amount ?? 0, 2) }}</strong>
                            </div>
                        </div>
                    </div>

                    <hr style="margin: 5px 0;">

                    <div class="mb-3 small">
                        <div class="row">
                            <div class="col-6">
                                <strong>Payment Method:</strong>
                            </div>
                            <div class="col-6 text-end">
                                {{ ucfirst($sale->payment_method ?? 'N/A') }}
                            </div>
                        </div>
                        @if($sale->payment_method === 'mpesa' && $sale->mpesa_code)
                            <div class="row">
                                <div class="col-6">
                                    <strong>M-Pesa Code:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    {{ $sale->mpesa_code }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <hr style="margin: 5px 0;">

                    <div class="text-center small">
                        <p class="text-muted mb-1">Thank you for your purchase!</p>
                        <p class="text-muted mb-0">{{ now()->format('d M Y H:i') }}</p>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Receipt
                    </button>
                    <a href="{{ route('sales.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Sales
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    @media print {
        body {
            background: white;
        }
        .card {
            border: none;
            box-shadow: none;
        }
        .btn, .card-body {
            display: none;
        }
        #receipt-content {
            display: block !important;
            padding: 0 !important;
        }
    }
</style>
@endpush
@endsection
