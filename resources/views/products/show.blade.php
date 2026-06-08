@extends('layouts.app')

@section('title', 'Product Details')
@section('page-title', 'Product Details')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Product Name</label>
                        <h5 class="fw-bold">{{ $product->name ?? 'N/A' }}</h5>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">SKU</label>
                        <h5 class="fw-bold">{{ $product->sku ?? 'N/A' }}</h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Category</label>
                        <p>{{ $product->category?->name ?? 'Uncategorized' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Barcode</label>
                        <p>{{ $product->barcode ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="row">
                    @if(!auth()->user()->isCashier())
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Cost Price (KES)</label>
                        <h6 class="fw-bold">{{ number_format($product->cost_price ?? 0, 2) }}</h6>
                    </div>
                    @endif
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Selling Price (KES)</label>
                        <h6 class="fw-bold text-success">{{ number_format($product->selling_price ?? 0, 2) }}</h6>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="text-muted small">Branch Stocks</label>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Branch</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($product->branchStocks as $inventory)
                                    <tr>
                                        <td>{{ $inventory->branch->name }}</td>
                                        <td>{{ $inventory->quantity_in_stock }}</td>
                                        <td>
                                            <span class="badge {{ $inventory->quantity_in_stock <= $product->reorder_level ? 'bg-danger' : 'bg-success' }}">
                                                {{ $inventory->quantity_in_stock <= $product->reorder_level ? 'Low Stock' : 'In Stock' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3">No branch stock</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="text-muted small">Reorder Level</label>
                    <h6 class="fw-bold">{{ $product->reorder_level ?? 0 }}</h6>
                </div>

                <div class="mb-3">
                    <label class="text-muted small">Description</label>
                    <p>{{ $product->description ?? 'No description provided.' }}</p>
                </div>

                <div class="mb-3">
                    <label class="text-muted small">Status</label>
                    <p>
                        @if($product->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Stock Movement History -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Stock Movement History</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($product->stockMovements ?? [] as $movement)
                            <tr>
                                <td>{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $movement->branch?->name ?? 'N/A' }}</td>
                                <td>{{ ucfirst($movement->type ?? 'N/A') }}</td>
                                <td>{{ $movement->quantity ?? 0 }}</td>
                                <td>{{ $movement->notes ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No stock movements recorded</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar Actions -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Actions</h5>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('products.edit', $product->id) }}" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit Product
                </a>
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#addStockModal">
                    <i class="bi bi-plus-circle"></i> Add Stock
                </button>
                <form action="{{ route('products.destroy', $product->id) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger w-100" 
                            onclick="return confirm('Are you sure? This will deactivate the product.')">
                        <i class="bi bi-trash"></i> Deactivate
                    </button>
                </form>
                <a href="{{ route('products.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Products
                </a>
            </div>
        </div>

        @if(!auth()->user()->isCashier())
        <!-- Summary Card -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Summary</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="text-muted small">Profit Margin</label>
                        <h6>
                            @php
                                $margin = $product->selling_price - $product->cost_price;
                                $marginPct = $product->cost_price > 0 ? ($margin / $product->cost_price) * 100 : 0;
                            @endphp
                            {{ number_format($marginPct, 1) }}%
                        </h6>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small">Margin Value</label>
                        <h6>KES {{ number_format($margin, 2) }}</h6>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <label class="text-muted small">Total Value (Cost)</label>
                        <h6>KES {{ number_format($product->branchStocks->sum('quantity_in_stock') * $product->cost_price, 2) }}</h6>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small">Total Value (Selling)</label>
                        <h6 class="text-success">KES {{ number_format($product->branchStocks->sum('quantity_in_stock') * $product->selling_price, 2) }}</h6>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Add Stock Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('stock.add') }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="branch_id" class="form-label">Branch</label>
                        <select id="branch_id" name="branch_id" class="form-control" required>
                            @foreach(\App\Models\Branch::all() as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" required min="1">
                    </div>
                    <div class="mb-3">
                        <label for="reference" class="form-label">Reference (e.g., PO#, Manual)</label>
                        <input type="text" id="reference" name="reference" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection