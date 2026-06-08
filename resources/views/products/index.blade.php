@extends('layouts.app')

@section('title', 'Products')
@section('page-title', 'Products')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h5>Product Inventory</h5>
    </div>
    <div class="col-md-6 text-end">
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Product
        </a>
    </div>
</div>

<!-- Filter Buttons -->
<div class="mb-3">
    <div class="btn-group" role="group">
        <a href="{{ route('products.index', ['filter' => 'all']) }}" 
           class="btn btn-outline-secondary {{ $filter === 'all' ? 'active' : '' }}">
            <i class="bi bi-funnel"></i> All Products
        </a>
        <a href="{{ route('products.index', ['filter' => 'active']) }}" 
           class="btn btn-outline-success {{ $filter === 'active' ? 'active' : '' }}">
            <i class="bi bi-check-circle"></i> Active
        </a>
        <a href="{{ route('products.index', ['filter' => 'inactive']) }}" 
           class="btn btn-outline-danger {{ $filter === 'inactive' ? 'active' : '' }}">
            <i class="bi bi-x-circle"></i> Inactive
        </a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Total Stock</th>
                    <th>Branch Breakdown</th>
                    @if(!auth()->user()->isCashier())
                    <th>Cost</th>
                    @endif
                    <th>Selling Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php $totalStock = $product->branchStocks->sum('quantity_in_stock'); @endphp
                    <tr>
                        <td><strong>{{ $product->sku }}</strong></td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category->name }}</td>

                        {{-- Total Stock with low-stock warning --}}
                        <td>
                            @if ($totalStock <= $product->reorder_level)
                                <span class="badge bg-danger" title="Low Stock">{{ $totalStock }}</span>
                            @else
                                <span class="fw-semibold">{{ $totalStock }}</span>
                            @endif
                        </td>

                        {{-- Per-branch breakdown --}}
                        <td>
                            @if ($product->branchStocks->isEmpty())
                                <span class="text-muted small">—</span>
                            @else
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ($product->branchStocks as $stock)
                                        @php
                                            $qty   = $stock->quantity_in_stock;
                                            $low   = $qty <= $product->reorder_level;
                                            $label = $stock->branch->name;
                                            // Shorten label: use abbreviation if branch name is long
                                            $short = mb_strlen($label) > 12
                                                        ? mb_strtoupper(mb_substr($label, 0, 8)) . '…'
                                                        : $label;
                                        @endphp
                                        <span class="badge {{ $low ? 'bg-danger' : 'bg-success' }} bg-opacity-85"
                                              title="{{ $label }}: {{ $qty }} units{{ $low ? ' (Low Stock)' : '' }}"
                                              style="font-size:.75rem; font-weight:500;">
                                            {{ $short }}: {{ $qty }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>

                        @if(!auth()->user()->isCashier())
                        <td>KES {{ number_format($product->cost_price, 2) }}</td>
                        @endif
                        <td><strong>KES {{ number_format($product->selling_price, 2) }}</strong></td>

                        <td>
                            @if ($product->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>

                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('products.show', $product) }}" class="btn btn-outline-primary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('products.destroy', $product) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            @if($filter === 'active')
                                No active products found
                            @elseif($filter === 'inactive')
                                No inactive products found
                            @else
                                No products found
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row mt-4">
    <div class="col">
        {{ $products->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection