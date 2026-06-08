@extends('layouts.app')

@section('title', 'Sales')
@section('page-title', 'Sales History')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Sales</h5>
        <a href="{{ route('sales.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle"></i> New Sale
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Receipt #</th>
                    <th>Date/Time</th>
                    <th>Cashier</th>
                    <th>Customer</th>
                    <th>Total Amount</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr>
                        <td><strong>#{{ $sale->receipt_number }}</strong></td>
                        <td>{{ $sale->created_at->format('d M Y H:i') }}</td>
                        <td>{{ $sale->cashier->name }}</td>
                        <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                        <td><strong>KES {{ number_format($sale->total_amount, 2) }}</strong></td>
                        <td>
                            <span class="badge bg-info">{{ ucfirst($sale->primary_payment_method) }}</span>
                        </td>
                        <td>
                            @if ($sale->status === 'completed')
                                <span class="badge bg-success">Completed</span>
                            @elseif ($sale->status === 'cancelled')
                                <span class="badge bg-danger">Cancelled</span>
                            @else
                                <span class="badge bg-warning">{{ ucfirst($sale->status) }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('sales.show', $sale) }}" class="btn btn-outline-primary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('sales.receipt', $sale) }}" class="btn btn-outline-secondary" title="Receipt">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No sales found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<div class="row mt-4">
    <div class="col">
        {{ $sales->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
