@extends('layouts.app')
@section('title', 'Smart Reorder')
@section('page-title', 'AI Smart Reorder')

@section('content')
<div class="container-fluid px-4">

    @if(!empty($ai_summary))
    <div class="alert alert-info d-flex gap-2 mb-4">
        <span style="font-size:1.3rem;">🤖</span>
        <div><strong>Claude's Brief:</strong> {{ $ai_summary }}</div>
    </div>
    @endif

    @if(empty($items))
        <div class="card border-0 shadow-sm"><div class="card-body text-center py-5 text-success">
            <i class="bi bi-check-circle-fill" style="font-size:3rem;"></i>
            <p class="mt-3 fw-bold">All products are above reorder levels!</p>
        </div></div>
    @else
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0 fw-bold">Items to Reorder ({{ count($items) }})</h6>
            <div>
                <span class="badge bg-danger me-1">{{ collect($items)->where('urgency','high')->count() }} High</span>
                <span class="badge bg-warning text-dark me-1">{{ collect($items)->where('urgency','medium')->count() }} Medium</span>
                <span class="badge bg-secondary">{{ collect($items)->where('urgency','low')->count() }} Low</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th class="text-center">Current Stock</th>
                        <th class="text-center">Reorder Level</th>
                        <th class="text-center">Suggested Qty</th>
                        <th class="text-end">Est. Cost</th>
                        <th class="text-center">Urgency</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td class="fw-bold">{{ $item['product_name'] }}</td>
                        <td class="text-muted">{{ $item['category'] }}</td>
                        <td class="text-center {{ $item['current_stock'] == 0 ? 'text-danger fw-bold' : '' }}">{{ $item['current_stock'] }}</td>
                        <td class="text-center">{{ $item['reorder_level'] }}</td>
                        <td class="text-center fw-bold text-primary">{{ $item['suggested_qty'] }}</td>
                        <td class="text-end">KSh {{ number_format($item['estimated_cost'], 2) }}</td>
                        <td class="text-center">
                            @if($item['urgency']==='high')<span class="badge bg-danger">High</span>
                            @elseif($item['urgency']==='medium')<span class="badge bg-warning text-dark">Medium</span>
                            @else<span class="badge bg-secondary">Low</span>@endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="5" class="fw-bold text-end">Total Estimated Cost:</td>
                        <td class="fw-bold text-end text-primary">KSh {{ number_format($total_cost, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="text-end">
        <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">
            <i class="bi bi-cart-plus me-1"></i> Create Purchase Order
        </a>
    </div>
    @endif

</div>
@endsection
