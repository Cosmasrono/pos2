@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Create Invoice</h2>
        <a href="{{ route('invoices.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Invoices
        </a>
    </div>

    <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
        @csrf

        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Invoice Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Customer <span class="text-danger">*</span></label>
                                <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                                    <option value="">Select Customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" 
                                            {{ (old('customer_id') == $customer->id || ($sale && $sale->customer_id == $customer->id)) ? 'selected' : '' }}>
                                            {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Terms</label>
                                <select name="payment_terms" class="form-select">
                                    <option value="">Select Terms</option>
                                    <option value="Due on Receipt" {{ old('payment_terms') == 'Due on Receipt' ? 'selected' : '' }}>Due on Receipt</option>
                                    <option value="Net 15" {{ old('payment_terms') == 'Net 15' ? 'selected' : '' }}>Net 15</option>
                                    <option value="Net 30" {{ old('payment_terms') == 'Net 30' ? 'selected' : '' }}>Net 30</option>
                                    <option value="Net 60" {{ old('payment_terms') == 'Net 60' ? 'selected' : '' }}>Net 60</option>
                                    <option value="Net 90" {{ old('payment_terms') == 'Net 90' ? 'selected' : '' }}>Net 90</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                                <input type="date" name="issue_date" class="form-control @error('issue_date') is-invalid @enderror" 
                                    value="{{ old('issue_date', $sale ? $sale->created_at->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                                @error('issue_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Due Date <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" 
                                    value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}" required>
                                @error('due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @if($sale)
                            <input type="hidden" name="sale_id" value="{{ $sale->id }}">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Creating invoice from Sale #{{ $sale->receipt_number }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Invoice Items -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Invoice Items</h5>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addInvoiceItem()">
                            <i class="bi bi-plus"></i> Add Item
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th style="width: 35%;">Description</th>
                                        <th style="width: 15%;">Quantity</th>
                                        <th style="width: 20%;">Unit Price</th>
                                        <th style="width: 15%;">Discount</th>
                                        <th style="width: 10%;">Total</th>
                                        <th style="width: 5%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsContainer">
                                    @if($sale)
                                        @foreach($sale->items as $index => $item)
                                            <tr class="item-row">
                                                <td>
                                                    <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                                    <input type="text" name="items[{{ $index }}][description]" class="form-control" 
                                                        value="{{ $item->product->name }}" required>
                                                </td>
                                                <td>
                                                    <input type="number" name="items[{{ $index }}][quantity]" class="form-control item-quantity" 
                                                        value="{{ $item->quantity }}" min="1" required onchange="calculateLineTotal(this)">
                                                </td>
                                                <td>
                                                    <input type="number" name="items[{{ $index }}][unit_price]" class="form-control item-price" 
                                                        value="{{ $item->unit_price }}" step="0.01" min="0" required onchange="calculateLineTotal(this)">
                                                </td>
                                                <td>
                                                    <input type="number" name="items[{{ $index }}][discount_per_item]" class="form-control item-discount" 
                                                        value="0" step="0.01" min="0" onchange="calculateLineTotal(this)">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control item-total" readonly value="{{ number_format($item->line_total, 2) }}">
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr class="item-row">
                                            <td>
                                                <select name="items[0][product_id]" class="form-select product-select" onchange="selectProduct(this)">
                                                    <option value="">Custom Item</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}" data-price="{{ $product->selling_price }}" data-name="{{ $product->name }}">
                                                            {{ $product->name }} - KSh {{ number_format($product->selling_price, 2) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <input type="text" name="items[0][description]" class="form-control mt-2" placeholder="Description" required>
                                            </td>
                                            <td>
                                                <input type="number" name="items[0][quantity]" class="form-control item-quantity" value="1" min="1" required onchange="calculateLineTotal(this)">
                                            </td>
                                            <td>
                                                <input type="number" name="items[0][unit_price]" class="form-control item-price" value="0" step="0.01" min="0" required onchange="calculateLineTotal(this)">
                                            </td>
                                            <td>
                                                <input type="number" name="items[0][discount_per_item]" class="form-control item-discount" value="0" step="0.01" min="0" onchange="calculateLineTotal(this)">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control item-total" readonly value="0.00">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Additional Notes</h5>
                    </div>
                    <div class="card-body">
                        <textarea name="notes" class="form-control" rows="3" placeholder="Add any additional notes or terms...">{{ old('notes', $sale->notes ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Right Column - Totals -->
            <div class="col-md-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header">
                        <h5 class="mb-0">Invoice Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Subtotal</label>
                            <input type="text" id="subtotalDisplay" class="form-control" readonly value="0.00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tax Amount</label>
                            <input type="number" name="tax_amount" id="taxAmount" class="form-control @error('tax_amount') is-invalid @enderror" 
                                value="{{ old('tax_amount', $sale->tax_amount ?? 0) }}" step="0.01" min="0" onchange="calculateTotals()">
                            @error('tax_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Discount</label>
                            <input type="number" name="discount_amount" id="discountAmount" class="form-control" 
                                value="{{ old('discount_amount', $sale->discount_amount ?? 0) }}" step="0.01" min="0" onchange="calculateTotals()">
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label"><strong>Total Amount</strong></label>
                            <input type="text" id="totalDisplay" class="form-control fw-bold" readonly value="0.00">
                            <input type="hidden" name="total_amount" id="totalAmount" value="0">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Invoice
                            </button>
                            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let itemIndex = {{ $sale ? count($sale->items) : 1 }};

function selectProduct(select) {
    const row = select.closest('tr');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const price = option.dataset.price;
        const name = option.dataset.name;
        
        row.querySelector('[name*="[unit_price]"]').value = price;
        row.querySelector('[name*="[description]"]').value = name;
    } else {
        row.querySelector('[name*="[unit_price]"]').value = 0;
        row.querySelector('[name*="[description]"]').value = '';
    }
    
    calculateLineTotal(select);
}

function addInvoiceItem() {
    const container = document.getElementById('itemsContainer');
    const row = document.createElement('tr');
    row.className = 'item-row';
    row.innerHTML = `
        <td>
            <select name="items[${itemIndex}][product_id]" class="form-select product-select" onchange="selectProduct(this)">
                <option value="">Custom Item</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-price="{{ $product->selling_price }}" data-name="{{ $product->name }}">
                        {{ $product->name }} - KSh {{ number_format($product->selling_price, 2) }}
                    </option>
                @endforeach
            </select>
            <input type="text" name="items[${itemIndex}][description]" class="form-control mt-2" placeholder="Description" required>
        </td>
        <td>
            <input type="number" name="items[${itemIndex}][quantity]" class="form-control item-quantity" value="1" min="1" required onchange="calculateLineTotal(this)">
        </td>
        <td>
            <input type="number" name="items[${itemIndex}][unit_price]" class="form-control item-price" value="0" step="0.01" min="0" required onchange="calculateLineTotal(this)">
        </td>
        <td>
            <input type="number" name="items[${itemIndex}][discount_per_item]" class="form-control item-discount" value="0" step="0.01" min="0" onchange="calculateLineTotal(this)">
        </td>
        <td>
            <input type="text" class="form-control item-total" readonly value="0.00">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    container.appendChild(row);
    itemIndex++;
}

function removeItem(button) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length > 1) {
        button.closest('tr').remove();
        calculateTotals();
    } else {
        alert('At least one item is required');
    }
}

function calculateLineTotal(element) {
    const row = element.closest('tr');
    const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const discount = parseFloat(row.querySelector('.item-discount').value) || 0;
    
    const total = (quantity * price) - discount;
    row.querySelector('.item-total').value = total.toFixed(2);
    
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    document.querySelectorAll('.item-total').forEach(input => {
        subtotal += parseFloat(input.value) || 0;
    });
    
    const tax = parseFloat(document.getElementById('taxAmount').value) || 0;
    const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
    const total = subtotal + tax - discount;
    
    document.getElementById('subtotalDisplay').value = subtotal.toFixed(2);
    document.getElementById('totalDisplay').value = total.toFixed(2);
    document.getElementById('totalAmount').value = total.toFixed(2);
}

// Calculate totals on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateTotals();
});
</script>
@endsection
