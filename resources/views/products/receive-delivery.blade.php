@extends('layouts.app')
@section('title', 'Receive Delivery')
@section('page-title', 'Receive Delivery')

@section('content')
<div class="container-fluid px-4 py-2">

    <div class="alert alert-info d-flex gap-2 mb-4">
        <i class="bi bi-info-circle-fill mt-1"></i>
        <div>
            Enter the quantity received for each product. Leave blank (or zero) for products not in this delivery.
            Only products with a quantity will be updated.
        </div>
    </div>

    <form action="{{ route('stock.receive.process') }}" method="POST">
        @csrf

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">Receiving Branch</label>
                        <select name="branch_id" class="form-select" required>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $branch->id == $defaultBranchId ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold mb-1">Reference / Supplier Invoice #</label>
                        <input type="text" name="reference" class="form-control" placeholder="e.g. INV-2024-001, Supplier name, LPO #">
                    </div>
                    <div class="col-md-3 text-end">
                        <span class="text-muted small">
                            <span id="updatedCount" class="fw-bold text-success">0</span> products to update
                        </span>
                    </div>
                </div>
            </div>

            <!-- Search bar -->
            <div class="card-body border-bottom py-2 px-4">
                <input type="text" id="productSearch" class="form-control form-control-sm"
                    placeholder="Search product by name or category..." autocomplete="off">
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0" id="productsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-center">Current Stock</th>
                            <th class="text-center">Reorder Level</th>
                            <th style="width:160px;">Qty Received</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                        @php
                            $totalStock = $product->branchStocks->sum('quantity_in_stock');
                            $isLow = $totalStock <= $product->reorder_level;
                        @endphp
                        <tr class="product-row {{ $isLow ? 'table-warning' : '' }}"
                            data-name="{{ strtolower($product->name) }}"
                            data-category="{{ strtolower($product->category->name ?? '') }}">
                            <td>
                                <div class="fw-bold">{{ $product->name }}</div>
                                @if($product->sku)
                                    <div class="text-muted small">{{ $product->sku }}</div>
                                @endif
                            </td>
                            <td class="text-muted">{{ $product->category->name ?? '—' }}</td>
                            <td class="text-center">
                                @if($totalStock == 0)
                                    <span class="badge bg-danger">Out of Stock</span>
                                @elseif($isLow)
                                    <span class="badge bg-warning text-dark">{{ $totalStock }} (Low)</span>
                                @else
                                    <span class="text-success fw-bold">{{ $totalStock }}</span>
                                @endif
                            </td>
                            <td class="text-center text-muted">{{ $product->reorder_level }}</td>
                            <td>
                                <input type="number"
                                    name="quantities[{{ $product->id }}]"
                                    class="form-control form-control-sm qty-input text-center"
                                    min="0" value="" placeholder="—"
                                    style="width:120px;">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center pb-4">
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-success btn-lg px-5" id="submitBtn" disabled>
                <i class="bi bi-box-arrow-in-down me-2"></i> Confirm Receipt
            </button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const qtyInputs = document.querySelectorAll('.qty-input');
    const countEl = document.getElementById('updatedCount');
    const submitBtn = document.getElementById('submitBtn');

    function refreshCount() {
        let count = 0;
        qtyInputs.forEach(inp => { if (parseInt(inp.value) > 0) count++; });
        countEl.textContent = count;
        submitBtn.disabled = count === 0;
    }

    qtyInputs.forEach(inp => {
        inp.addEventListener('input', refreshCount);
        // Highlight row when quantity is entered
        inp.addEventListener('input', function () {
            const row = this.closest('tr');
            if (parseInt(this.value) > 0) {
                row.classList.add('table-success');
                row.classList.remove('table-warning');
            } else {
                row.classList.remove('table-success');
            }
        });
    });

    // Search
    document.getElementById('productSearch').addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.product-row').forEach(row => {
            const match = row.dataset.name.includes(q) || row.dataset.category.includes(q);
            row.style.display = match ? '' : 'none';
        });
    });
});
</script>
@endpush
