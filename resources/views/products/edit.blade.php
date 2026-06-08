@extends('layouts.app')

@section('title', 'Edit Product')
@section('page-title', 'Edit Product')

@section('content')
@php
    $isMain = !auth()->user()->branch_id; // SuperAdmin/Main account
    $userBranch = auth()->user()->branch;
@endphp
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Product: {{ $product->name ?? 'N/A' }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('products.update', $product->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $product->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <input type="text" id="category_id" name="category_id" class="form-control @error('category_id') is-invalid @enderror" 
                                   value="{{ old('category_id', $product->category->name ?? '') }}" placeholder="Enter category name" required>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                            <input type="text" id="sku" name="sku" class="form-control @error('sku') is-invalid @enderror" 
                                   value="{{ old('sku', $product->sku) }}" required>
                            @error('sku')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="barcode" class="form-label">Barcode</label>
                            <input type="text" id="barcode" name="barcode" class="form-control @error('barcode') is-invalid @enderror" 
                                   value="{{ old('barcode', $product->barcode) }}">
                            @error('barcode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        @if(!auth()->user()->isCashier())
                        <div class="col-md-6">
                            <label for="cost_price" class="form-label">Cost Price (KES)</label>
                            <input type="number" step="0.01" id="cost_price" name="cost_price" 
                                   class="form-control @error('cost_price') is-invalid @enderror" 
                                   value="{{ old('cost_price', $product->cost_price) }}">
                            <small class="text-muted">Cost for a single item (Optional)</small>
                            @error('cost_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @else
                        <div class="col-md-6">
                            {{-- Placeholder to keep layout consistent or just leave empty --}}
                        </div>
                        @endif
                        <div class="col-md-6">
                            <label for="selling_price" class="form-label">Selling Price (KES) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" id="selling_price" name="selling_price" 
                                   class="form-control @error('selling_price') is-invalid @enderror" 
                                   value="{{ old('selling_price', $product->selling_price) }}" required>
                            @error('selling_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="reorder_level" class="form-label">Reorder Level</label>
                            <input type="number" id="reorder_level" name="reorder_level" 
                                   class="form-control @error('reorder_level') is-invalid @enderror" 
                                   value="{{ old('reorder_level', $product->reorder_level) }}">
                            @error('reorder_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="card bg-light border mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Stock Management & Branch Distribution</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="total_initial_stock" class="form-label fw-bold">Total Stock</label>
                                    <input type="number" id="total_initial_stock" name="total_initial_stock" 
                                           class="form-control form-control-lg @error('total_initial_stock') is-invalid @enderror" 
                                           min="0" value="{{ old('total_initial_stock', $product->quantity_in_stock) }}" required>
                                    @error('total_initial_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    @if($isMain)
                                        <small class="text-muted">Update total stock. It will be redistributed by branch percentages.</small>
                                    @else
                                        <small class="text-muted">Update stock for {{ $userBranch->name }} branch only.</small>
                                    @endif
                                </div>
                            </div>

                            <hr>

                            @if($isMain)
                                <h6 class="mt-3 mb-3">Current Branch Allocation</h6>
                                <div class="row">
                                    @foreach ($branches as $branch)
                                        @php
                                            $stock = $product->branchStocks->where('branch_id', $branch->id)->first()?->quantity_in_stock ?? 0;
                                        @endphp
                                        <div class="col-md-6 mb-2">
                                            <div class="p-3 bg-white border rounded">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>{{ $branch->name }}</strong> 
                                                        @if ($branch->is_main)
                                                            <span class="badge bg-success">Main</span>
                                                        @endif
                                                    </div>
                                                    <span class="badge bg-info" style="font-size: 0.95rem;">{{ $stock }} units</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <h6 class="mt-3 mb-3">{{ $userBranch->name }} Branch Stock</h6>
                                <div class="p-3 bg-white border rounded">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $userBranch->name }}</strong>
                                            @if ($userBranch->is_main)
                                                <span class="badge bg-success">Main</span>
                                            @endif
                                        </div>
                                        <span class="badge bg-info" style="font-size: 0.95rem;">
                                            {{ $product->branchStocks->where('branch_id', $userBranch->id)->first()?->quantity_in_stock ?? 0 }} units
                                        </span>
                                    </div>
                                </div>
                            @endif

                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle"></i>
                                @if($isMain)
                                    <strong>Note:</strong> When you update the total stock, it will be automatically redistributed to each branch based on their percentage allocation. The quantities shown above will update accordingly.
                                @else
                                    <strong>Note:</strong> You are editing stock only for your branch ({{ $userBranch->name }}). SuperAdmin can manage all branches.
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" 
                                  rows="3">{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" id="is_active" name="is_active" class="form-check-input" value="1"
                                   {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Product is Active
                            </label>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="{{ route('products.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection