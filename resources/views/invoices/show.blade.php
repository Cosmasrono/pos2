@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Invoice {{ $invoice->invoice_number }}</h2>
        <div>
            <a href="{{ route('invoices.print', $invoice) }}" class="btn btn-info" target="_blank">
                <i class="bi bi-printer"></i> Print
            </a>
            @if($invoice->canEdit())
                <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-secondary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            @endif
            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Main Content -->
        <div class="col-md-8">
            <!-- Invoice Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Bill To:</h5>
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
                            <p class="mb-1"><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
                            <p class="mb-1"><strong>Issue Date:</strong> {{ $invoice->issue_date->format('M d, Y') }}</p>
                            <p class="mb-1"><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</p>
                            @if($invoice->payment_terms)
                                <p class="mb-1"><strong>Payment Terms:</strong> {{ $invoice->payment_terms }}</p>
                            @endif
                            <p class="mb-0">
                                <span class="badge bg-{{ $invoice->status_badge_color }} fs-6">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Invoice Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->items as $item)
                                    <tr>
                                        <td>{{ $item->description }}</td>
                                        <td class="text-end">{{ $item->quantity }}</td>
                                        <td class="text-end">KSh {{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end">KSh {{ number_format($item->discount_per_item, 2) }}</td>
                                        <td class="text-end">KSh {{ number_format($item->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end">KSh {{ number_format($invoice->subtotal, 2) }}</td>
                                </tr>
                                @if($invoice->tax_amount > 0)
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Tax:</strong></td>
                                        <td class="text-end">KSh {{ number_format($invoice->tax_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if($invoice->discount_amount > 0)
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Discount:</strong></td>
                                        <td class="text-end">-KSh {{ number_format($invoice->discount_amount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class="table-active">
                                    <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                                    <td class="text-end"><strong>KSh {{ number_format($invoice->total_amount, 2) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            @if($invoice->payments->count() > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Payment History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->payments as $payment)
                                        <tr>
                                            <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                                            <td>KSh {{ number_format($payment->amount, 2) }}</td>
                                            <td>{{ ucfirst($payment->payment_method) }}</td>
                                            <td>{{ $payment->reference_number ?? '-' }}</td>
                                            <td>{{ $payment->recordedBy->name }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Notes -->
            @if($invoice->notes)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Notes</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $invoice->notes }}</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Payment Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Payment Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Amount:</span>
                        <strong>KSh {{ number_format($invoice->total_amount, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Amount Paid:</span>
                        <span class="text-success">KSh {{ number_format($invoice->amount_paid, 2) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Balance Due:</strong>
                        <strong class="text-danger">KSh {{ number_format($invoice->balance_due, 2) }}</strong>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    @if($invoice->status === 'draft')
                        <form action="{{ route('invoices.send', $invoice) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-send"></i> Mark as Sent
                            </button>
                        </form>
                    @endif

                    @if($invoice->balance_due > 0 && !in_array($invoice->status, ['cancelled']))
                        <button type="button" class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#paymentModal">
                            <i class="bi bi-cash"></i> Record Payment
                        </button>
                    @endif

                    @if(!in_array($invoice->status, ['paid', 'cancelled']))
                        <form action="{{ route('invoices.cancel', $invoice) }}" method="POST" 
                            onsubmit="return confirm('Are you sure you want to cancel this invoice?')">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100 mb-2">
                                <i class="bi bi-x-circle"></i> Cancel Invoice
                            </button>
                        </form>
                    @endif

                    @if($invoice->canDelete())
                        <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" 
                            onsubmit="return confirm('Are you sure you want to delete this invoice?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-trash"></i> Delete Invoice
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Invoice Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Invoice Information</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><small class="text-muted">Created by:</small><br>{{ $invoice->createdBy->name }}</p>
                    <p class="mb-2"><small class="text-muted">Created at:</small><br>{{ $invoice->created_at->format('M d, Y h:i A') }}</p>
                    @if($invoice->sent_at)
                        <p class="mb-2"><small class="text-muted">Sent at:</small><br>{{ $invoice->sent_at->format('M d, Y h:i A') }}</p>
                    @endif
                    @if($invoice->paid_at)
                        <p class="mb-0"><small class="text-muted">Paid at:</small><br>{{ $invoice->paid_at->format('M d, Y h:i A') }}</p>
                    @endif
                    @if($invoice->sale_id)
                        <hr>
                        <p class="mb-0">
                            <small class="text-muted">Generated from:</small><br>
                            <a href="{{ route('sales.show', $invoice->sale_id) }}">Sale #{{ $invoice->sale->receipt_number }}</a>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('invoices.payment', $invoice) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" 
                            max="{{ $invoice->balance_due }}" value="{{ $invoice->balance_due }}" required>
                        <small class="text-muted">Maximum: KSh {{ number_format($invoice->balance_due, 2) }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="mpesa">M-Pesa</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="check">Check</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference Number</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="e.g., M-Pesa code, check number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
