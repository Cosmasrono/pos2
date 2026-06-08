@extends('layouts.app')

@section('title', 'Purchase Orders')
@section('page-title', 'Purchase Orders Management')

@section('content')
<div class="container-fluid px-4">
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Pending Orders</p>
                            <h4 class="mb-0">{{ $pendingCount }}</h4>
                        </div>
                        <div style="font-size: 2.5rem;">⏳</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Received This Month</p>
                            <h4 class="mb-0">{{ $receivedCount }}</h4>
                        </div>
                        <div style="font-size: 2.5rem;">✓</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Spending</p>
                            <h4 class="mb-0">KES {{ $totalSpending }}</h4>
                        </div>
                        <div style="font-size: 2.5rem;">💰</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <a href="{{ route('purchase-orders.create') }}" class="card border-0 shadow-sm text-decoration-none" style="height: 100%; cursor: pointer;">
                <div class="card-body d-flex align-items-center justify-content-center text-center">
                    <div>
                        <div style="font-size: 2.5rem; margin-bottom: 10px;">➕</div>
                        <p class="text-muted mb-0">New Order</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Main Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold">📦 Purchase Orders</h6>
                <a href="{{ route('purchase-orders.create') }}" class="btn btn-sm btn-success">+ New Order</a>
            </div>
        </div>
        <div class="card-body p-0">
            @if($orders->count())
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold">PO Number</th>
                                <th class="fw-bold">Supplier</th>
                                <th class="fw-bold">Items</th>
                                <th class="fw-bold text-end">Total Cost</th>
                                <th class="fw-bold">Order Date</th>
                                <th class="fw-bold">Status</th>
                                <th class="fw-bold text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td>
                                        <span class="badge bg-primary">{{ $order->po_number }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $order->supplier_name }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $order->items->count() }} items</span>
                                    </td>
                                    <td class="text-end fw-bold">KES {{ number_format($order->total_cost, 2) }}</td>
                                    <td><small>{{ $order->order_date->format('M d, Y') }}</small></td>
                                    <td>
                                        @if($order->status === 'pending')
                                            <span class="badge bg-warning">⏳ Pending</span>
                                        @elseif($order->status === 'received')
                                            <span class="badge bg-success">✓ Received</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $order->status }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-sm btn-info" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($order->status === 'pending')
                                            <form action="{{ route('purchase-orders.receive', $order) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-success" title="Mark Received" 
                                                        onclick="return confirm('Mark this order as received?')">
                                                    ✓ Receive
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <small class="text-muted">Showing {{ $orders->count() }} of {{ $orders->total() }} orders</small>
                    {{ $orders->links('pagination::bootstrap-4') }}
                </div>
            @else
                <div class="p-5 text-center">
                    <div style="font-size: 4rem; margin-bottom: 10px;">📦</div>
                    <p class="text-muted">No purchase orders yet</p>
                    <a href="{{ route('purchase-orders.create') }}" class="btn btn-sm btn-success">Create First Order</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
