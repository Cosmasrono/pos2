@extends('layouts.app')

@section('title', 'Stock Transfers')

@section('page-title', 'Stock Transfers')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Stock Transfer History</h4>
            <p class="text-muted small">View and manage stock movements between branches</p>
        </div>
        <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Initiate Fetch
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Product</th>
                            <th>From Branch</th>
                            <th>To Branch</th>
                            <th>Quantity</th>
                            <th>Authorized By</th>
                            <th class="pe-4">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $transfer)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium">{{ $transfer->created_at->format('M d, Y') }}</span>
                                        <span class="text-muted small">{{ $transfer->created_at->format('h:i A') }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded p-2 me-3">
                                            <i class="bi bi-box-seam text-primary"></i>
                                        </div>
                                        <div>
                                            <span class="d-block fw-bold">{{ $transfer->product->name }}</span>
                                            <span class="text-muted small">SKU: {{ $transfer->product->sku }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary opacity-75">
                                        {{ $transfer->fromBranch->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary">
                                        {{ $transfer->branch->name }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary">{{ number_format($transfer->quantity) }}</span>
                                </td>
                                <td>
                                    <span class="small text-muted">{{ $transfer->user->name ?? 'System' }}</span>
                                </td>
                                <td class="pe-4">
                                    <small class="text-muted">{{ $transfer->notes }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                        <p class="mb-0">No stock transfers found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transfers->hasPages())
            <div class="card-footer bg-white border-top-0 py-3">
                {{ $transfers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
