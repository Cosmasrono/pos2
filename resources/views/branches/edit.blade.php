@extends('layouts.app')

@section('title', 'Edit Branch')
@section('page-title', 'Edit Branch: ' . $branch->name)

@section('content')
<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('branches.update', $branch) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Branch Name *</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" 
                               value="{{ old('name', $branch->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="code" class="form-label">Branch Code</label>
                        <input type="text" id="code" name="code" class="form-control @error('code') is-invalid @enderror" 
                               value="{{ old('code', $branch->code) }}">
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" 
                                  rows="3">{{ old('address', $branch->address) }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                               value="{{ old('phone', $branch->phone) }}">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="card bg-light border mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Stock Distribution</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="stock_distribution_percentage" class="form-label fw-bold">Stock Distribution Percentage</label>
                                <div class="input-group">
                                    <input type="number" id="stock_distribution_percentage" name="stock_distribution_percentage" 
                                           class="form-control @error('stock_distribution_percentage') is-invalid @enderror" 
                                           value="{{ old('stock_distribution_percentage', $branch->stock_distribution_percentage ?? 0) }}" 
                                           min="0" max="100" step="0.01">
                                    <span class="input-group-text">%</span>
                                </div>
                                @error('stock_distribution_percentage') 
                                    <div class="invalid-feedback d-block">{{ $message }}</div> 
                                @enderror
                                <small class="text-muted">
                                    When you create a product, the total stock will be distributed to each branch based on this percentage. 
                                    For example, 40% means this branch receives 40% of all new stock added.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" id="is_main" name="is_main" class="form-check-input" value="1" {{ old('is_main', $branch->is_main) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_main">
                            Mark as Main Branch
                        </label>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Branch
                        </button>
                        <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
