@extends('layouts.app')

@section('title', 'Edit Promotion')
@section('page-title', 'Edit Promotion')

@section('content')
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold">Update Promotion: {{ $promotion->name }}</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('promotions.update', $promotion) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Promotion Name</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g., Summer Sale 2026" required value="{{ old('name', $promotion->name) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Promotion Code</label>
                                <input type="text" name="code" class="form-control" placeholder="SUMMER26" value="{{ old('code', $promotion->code) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Discount Type</label>
                                <select name="type" class="form-select" required>
                                    <option value="fixed" {{ old('type', $promotion->type) == 'fixed' ? 'selected' : '' }}>Fixed Amount (KES)</option>
                                    <option value="percentage" {{ old('type', $promotion->type) == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Discount Value</label>
                                <input type="number" name="value" class="form-control" step="0.01" min="0" required value="{{ old('value', $promotion->value) }}">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Minimum Spend Requirement</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted">KES</span>
                                    <input type="number" name="min_spend" class="form-control" step="0.01" min="0" required value="{{ old('min_spend', $promotion->min_spend) }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Start Date</label>
                                <input type="datetime-local" name="start_date" class="form-control" value="{{ old('start_date', $promotion->start_date ? $promotion->start_date->format('Y-m-d\TH:i') : '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">End Date</label>
                                <input type="datetime-local" name="end_date" class="form-control" value="{{ old('end_date', $promotion->end_date ? $promotion->end_date->format('Y-m-d\TH:i') : '') }}">
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $promotion->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="is_active">Active Status</label>
                                </div>
                                <p class="text-muted small">Only active promotions appear in the POS system.</p>
                            </div>

                            <div class="col-12 mt-4">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary rounded-pill px-4">Update Promotion</button>
                                    <a href="{{ route('promotions.index') }}" class="btn btn-light rounded-pill px-4">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
