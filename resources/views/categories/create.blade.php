@extends('layouts.app')

@section('title', 'Add Product Category')
@section('page-title', 'Add Product Category')

@section('content')
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="alert alert-info border-0 mb-4">
                <i class="bi bi-lightbulb-fill me-2"></i>
                <strong>What is a category?</strong>
                A category groups similar products together — for example: <em>Medicines</em>, <em>Drinks</em>, or <em>Electronics</em>.
                Once you create a category here, you can assign products to it.
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="bi bi-tags me-2"></i>New Category Details
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('categories.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                Category Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name"
                                   class="form-control form-control-lg @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}"
                                   placeholder="e.g. Medicines, Food, Electronics"
                                   autofocus required>
                            <div class="form-text">Choose a short, clear name that describes the group of products.</div>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Description <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea name="description" rows="3"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="e.g. Over-the-counter medicines and supplements">{{ old('description') }}</textarea>
                            <div class="form-text">Extra notes about this category — you can leave this blank.</div>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-circle me-1"></i>Save Category
                            </button>
                            <a href="{{ route('categories.index') }}" class="btn btn-light px-4">
                                <i class="bi bi-x me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
