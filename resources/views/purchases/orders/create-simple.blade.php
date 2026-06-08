@extends('layouts.app')

@section('title', 'Create Purchase Order')
@section('page-title', 'New Purchase Order')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-success text-white py-3">
                    <h5 class="m-0"><i class="bi bi-bag-plus"></i> Order Stock from Supplier</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('purchase-orders.store') }}" method="POST" id="poForm">
                        @csrf
                        
                        <!-- Step 1: Select Supplier -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">🏪 Select Supplier</label>
                            <input type="text" 
                                   name="supplier_name"
                                   class="form-control form-control-lg @error('supplier_name') is-invalid @enderror" 
                                   id="supplierSearch" 
                                   list="supplierList" 
                                   placeholder="Type to search or enter new supplier..."
                                   value="{{ old('supplier_name') }}"
                                   autocomplete="off"
                                   required>
                            <input type="hidden" name="supplier_id" id="supplier_id" value="{{ old('supplier_id') }}">
                            
                            <datalist id="supplierList">
                                @foreach($suppliers as $supplier)
                                    <option data-id="{{ $supplier->id }}" value="{{ $supplier->name }}">
                                @endforeach
                            </datalist>
                            
                            @error('supplier_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Supplier Info Panel -->
                        <div id="supplierInfo" class="alert alert-info d-none" role="alert">
                            <small id="supplierDetails"></small>
                        </div>

                        <!-- Step 2: Dates -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">📅 Order Date</label>
                                <input type="date" name="order_date" class="form-control form-control-lg @error('order_date') is-invalid @enderror" 
                                       value="{{ old('order_date', date('Y-m-d')) }}" required>
                                @error('order_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">🚚 Expected Delivery</label>
                                <input type="date" name="expected_delivery_date" class="form-control form-control-lg @error('expected_delivery_date') is-invalid @enderror" 
                                       value="{{ old('expected_delivery_date') }}">
                                @error('expected_delivery_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Step 3: Add Items -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-bold mb-0">📦 Items to Order</label>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-item">
                                    <i class="bi bi-plus-circle"></i> Add Item
                                </button>
                            </div>

                            <div id="items-container" class="border rounded p-3" style="background-color: #f9f9f9;">
                                <div class="row mb-3 item-row">
                                    <div class="col-md-5">
                                        <label class="form-label small">Product</label>
                                        <select name="products[0][id]" class="form-select product-select" required>
                                            <option value="">Select Product</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" data-current-stock="{{ $product->quantity_in_stock }}">
                                                    {{ $product->name }} (Current: {{ $product->quantity_in_stock }} units)
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">Qty</label>
                                        <input type="number" name="products[0][quantity]" class="form-control qty-input" 
                                               placeholder="Qty" min="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Unit Cost</label>
                                        <input type="number" name="products[0][cost]" class="form-control cost-input" 
                                               placeholder="Cost" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm w-100 remove-item" disabled>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @error('products')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Order Summary -->
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">Total Items:</small>
                                        <h5 id="totalItems">0</h5>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Estimated Total Cost:</small>
                                        <h5 id="totalCost">KES 0.00</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">📝 Special Instructions (Optional)</label>
                            <textarea name="notes" class="form-control" rows="3" 
                                      placeholder="Any special requests or notes for supplier...">{{ old('notes') }}</textarea>
                        </div>

                        <!-- Buttons -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg fw-bold" id="submitBtn">
                                ✓ Create Purchase Order
                            </button>
                            <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Helper Panel -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom">
                    <h6 class="m-0 fw-bold">💡 Quick Tips</h6>
                </div>
                <div class="card-body small">
                    <div class="mb-3">
                        <strong>📌 How to Order:</strong>
                        <ol class="mb-0 ps-3">
                            <li>Choose a supplier</li>
                            <li>Set dates</li>
                            <li>Add items with costs</li>
                            <li>Review total</li>
                            <li>Submit</li>
                        </ol>
                    </div>
                    <div class="alert alert-info p-2 mb-0">
                        <small><strong>💬 Tip:</strong> Add notes if items have special instructions or are urgent.</small>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="m-0 fw-bold">📊 Low Stock Items</h6>
                </div>
                <div class="card-body small">
                    @php
                        $lowStockItems = $products->where('quantity_in_stock', '<', 10)->take(5);
                    @endphp
                    @if($lowStockItems->count())
                        <ul class="mb-0 ps-3">
                            @foreach($lowStockItems as $item)
                                <li>
                                    {{ $item->name }}: <strong>{{ $item->quantity_in_stock }}</strong> left
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">All items in good stock</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-success {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    }
</style>

@push('scripts')
<script>
let itemIndex = 1;
const products = @json($products);

// Supplier Selection Logic
const supplierSearch = document.getElementById('supplierSearch');
const supplierList = document.getElementById('supplierList');
const supplierIdInput = document.getElementById('supplier_id');

supplierSearch.addEventListener('input', function() {
    const value = this.value;
    let foundId = '';
    
    // Check if the typed value matches any option in the datalist
    const options = supplierList.options;
    for (let i = 0; i < options.length; i++) {
        if (options[i].value === value) {
            foundId = options[i].getAttribute('data-id');
            break;
        }
    }
    
    supplierIdInput.value = foundId;
    updateSupplierInfo();
});

function updateSupplierInfo() {
    const info = document.getElementById('supplierInfo');
    const details = document.getElementById('supplierDetails');
    
    if (supplierIdInput.value) {
        info.classList.remove('d-none');
        details.textContent = 'Supplier "' + supplierSearch.value + '" selected. Ready to add items.';
    } else {
        info.classList.add('d-none');
    }
}

// Initialize info if pre-filled
if (supplierIdInput.value) {
    updateSupplierInfo();
}

// Add Item Row
document.getElementById('add-item').addEventListener('click', function() {
    const container = document.getElementById('items-container');
    const newItem = document.createElement('div');
    newItem.className = 'row mb-3 item-row';
    newItem.innerHTML = `
        <div class="col-md-5">
            <select name="products[${itemIndex}][id]" class="form-select product-select" required>
                <option value="">Select Product</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-current-stock="{{ $product->quantity_in_stock }}">
                        {{ $product->name }} (Current: {{ $product->quantity_in_stock }} units)
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" name="products[${itemIndex}][quantity]" class="form-control qty-input" 
                   placeholder="Qty" min="1" required>
        </div>
        <div class="col-md-3">
            <input type="number" name="products[${itemIndex}][cost]" class="form-control cost-input" 
                   placeholder="Cost" step="0.01" min="0" required>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="button" class="btn btn-outline-danger btn-sm w-100 remove-item">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    
    container.appendChild(newItem);
    itemIndex++;
    
    attachEventListeners();
    updateTotals();
});

// Remove Item
function attachEventListeners() {
    document.querySelectorAll('.remove-item').forEach((btn, idx) => {
        btn.disabled = document.querySelectorAll('.item-row').length === 1;
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            this.closest('.item-row').remove();
            attachEventListeners();
            updateTotals();
        });
    });

    document.querySelectorAll('.qty-input, .cost-input').forEach(input => {
        input.addEventListener('change', updateTotals);
    });
}

// Calculate Totals
function updateTotals() {
    let totalQty = 0;
    let totalCost = 0;

    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseInt(row.querySelector('.qty-input').value) || 0;
        const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
        totalQty += qty;
        totalCost += qty * cost;
    });

    document.getElementById('totalItems').textContent = totalQty;
    document.getElementById('totalCost').textContent = `KES ${totalCost.toFixed(2)}`;
}

attachEventListeners();
</script>
@endpush

@endsection
