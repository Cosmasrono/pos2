@extends('layouts.app')

@section('title', 'Create Purchase Order')
@section('page-title', 'Create Purchase Order')

@section('content')
<div class="container-fluid px-4">
    <div class="card">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 font-weight-bold text-primary">Purchase Order Details</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('purchase-orders.store') }}" method="POST" id="poForm">
                @csrf
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Supplier</label>
                        <input type="text" 
                               name="supplier_name"
                               class="form-control @error('supplier_name') is-invalid @enderror" 
                               id="supplierSearch" 
                               list="supplierList" 
                               placeholder="Type to search or enter new..."
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
                    <div class="col-md-4">
                        <label class="form-label">Order Date</label>
                        <input type="date" name="order_date" class="form-control @error('order_date') is-invalid @enderror" 
                               value="{{ old('order_date', date('Y-m-d')) }}" required>
                        @error('order_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Expected Delivery</label>
                        <input type="date" name="expected_delivery_date" class="form-control @error('expected_delivery_date') is-invalid @enderror" 
                               value="{{ old('expected_delivery_date') }}">
                        @error('expected_delivery_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>

                <hr>

                <h6 class="mb-3">Order Items</h6>
                <div id="items-container">
                    <div class="row mb-2 item-row">
                        <div class="col-md-5">
                            <select name="products[0][id]" class="form-control" required>
                                <option value="">Select Product</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" name="products[0][quantity]" class="form-control" placeholder="Quantity" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <input type="number" name="products[0][cost]" class="form-control" placeholder="Unit Cost" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-item" disabled>
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-item">
                    <i class="bi bi-plus"></i> Add Item
                </button>

                <hr>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('purchase-orders.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Purchase Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
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
});

let itemIndex = 1;

document.getElementById('add-item').addEventListener('click', function() {
    const container = document.getElementById('items-container');
    const newRow = container.querySelector('.item-row').cloneNode(true);
    
    // Update names
    newRow.querySelectorAll('select, input').forEach(input => {
        const name = input.getAttribute('name');
        if (name) {
            input.setAttribute('name', name.replace(/\[\d+\]/, `[${itemIndex}]`));
            input.value = '';
        }
    });
    
    // Enable remove button
    newRow.querySelector('.remove-item').disabled = false;
    
    container.appendChild(newRow);
    itemIndex++;
});

document.getElementById('items-container').addEventListener('click', function(e) {
    if (e.target.closest('.remove-item')) {
        e.target.closest('.item-row').remove();
    }
});
</script>
@endpush
@endsection
