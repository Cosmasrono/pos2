@extends('layouts.app')

@section('title', 'Products')
@section('page-title', 'Products')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h5>Product Inventory</h5>
    </div>
    <div class="col-md-6 text-end d-flex gap-2 justify-content-end">
        <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-tags"></i> Categories
        </a>
        <a href="{{ route('stock.receive') }}" class="btn btn-success">
            <i class="bi bi-box-arrow-in-down"></i> Receive Delivery
        </a>
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Product
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
                                @if(!auth()->user()->isCashier())
                                <button type="button" class="btn btn-outline-success restock-btn" title="Add Stock"
                                    data-id="{{ $product->id }}" data-name="{{ $product->name }}">
                                    <i class="bi bi-plus-circle"></i>
                                </button>
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('products.destroy', $product) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Deactivate">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="text-center py-5">
                                <i class="bi bi-box-seam fs-1 text-muted opacity-25 d-block mb-3"></i>
                                @if($filter === 'active')
                                    <h6 class="text-muted">No active products</h6>
                                    <p class="text-muted small mb-3">All your products may be deactivated. Try switching the filter above, or add a new product.</p>
                                @elseif($filter === 'inactive')
                                    <h6 class="text-muted">No inactive products</h6>
                                    <p class="text-muted small mb-3">All your products are currently active!</p>
                                @else
                                    <h6 class="text-muted">No products yet</h6>
                                    <p class="text-muted small mb-3">Start by adding your first product to the system.</p>
                                    <a href="{{ route('products.create') }}" class="btn btn-primary">
                                        <i class="bi bi-plus-circle me-1"></i> Add Your First Product
                                    </a>
                                @endif
                            </div>
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

<!-- Quick Restock Modal -->
<div class="modal fade" id="quickRestockModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="{{ route('stock.add') }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" id="restock_product_id">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-bold">Add Stock</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="restock_product_name"></p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Branch</label>
                        <select name="branch_id" class="form-select form-select-sm" required>
                            @foreach(\App\Models\Branch::where('is_active', true)->get() as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Units Received</label>
                        <input type="number" name="quantity" class="form-control form-control-sm" min="1" required placeholder="e.g. 100">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small">Reference (optional)</label>
                        <input type="text" name="reference" class="form-control form-control-sm" placeholder="Invoice #, PO #, etc.">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="bi bi-plus-circle me-1"></i> Add Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.restock-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('restock_product_id').value = this.dataset.id;
        document.getElementById('restock_product_name').textContent = this.dataset.name;
        new bootstrap.Modal(document.getElementById('quickRestockModal')).show();
    });
});
</script>
@endpush

@endsection