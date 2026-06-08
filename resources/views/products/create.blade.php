@extends('layouts.app')

@section('title', 'Create Product')
@section('page-title', 'Add New Product')

@section('content')
@php
    $isMain = !auth()->user()->branch_id; // SuperAdmin/Main account
    $userBranch = auth()->user()->branch;
@endphp

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-xl-10 mx-auto">
            {{-- Status Alert --}}
            @if($isMain)
                <div class="alert alert-info d-flex align-items-center mb-4 border-0 shadow-sm" style="background: var(--info-light); border-left: 5px solid var(--info) !important;">
                    <div class="rounded-circle bg-white p-2 me-3 shadow-sm">
                        <i class="bi bi-building-fill text-info fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-info">Main Account (SuperAdmin)</h6>
                        <p class="mb-0 small opacity-75">You are creating products for the <strong>Main</strong> branch. Stock will be distributed across branches.</p>
                    </div>
                </div>
            @else
                <div class="alert alert-primary d-flex align-items-center mb-4 border-0 shadow-sm" style="background: var(--primary-light); border-left: 5px solid var(--primary) !important;">
                    <div class="rounded-circle bg-white p-2 me-3 shadow-sm">
                        <i class="bi bi-geo-alt-fill text-primary fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-primary">Branch Account: {{ $userBranch->name }}</h6>
                        <p class="mb-0 small opacity-75">Stock will be allocated strictly to your branch only.</p>
                    </div>
                </div>
            @endif

            <form action="{{ route('products.store') }}" method="POST">
                @csrf

                <div class="row g-4">
                    {{-- Left Column: Product Details --}}
                    <div class="col-lg-8">
                        {{-- Basic Information Card --}}
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-0 pt-4 px-4">
                                <h5 class="mb-0 fw-bold text-primary">
                                    <i class="bi bi-info-circle me-2"></i>Basic Information
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-tag text-muted"></i></span>
                                            <input type="text" id="name" name="name" class="form-control border-start-0 @error('name') is-invalid @enderror" 
                                                   value="{{ old('name') }}" placeholder="Enter product name" required>
                                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-5">
                                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-grid text-muted"></i></span>
                                            <input type="text" id="category_id" name="category_id" class="form-control border-start-0 @error('category_id') is-invalid @enderror" 
                                                   value="{{ old('category_id') }}" placeholder="Category name" required>
                                            @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="sku" class="form-label">SKU (System Generated)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-robot text-muted"></i></span>
                                           <input type="text" id="sku" name="sku" class="form-control border-start-0 border-end-0 bg-light fw-bold text-primary" 
       value="" placeholder="e.g. 130326-142507-384921" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="barcode" class="form-label">Barcode</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-upc-scan text-muted"></i></span>
                                            <input type="text" id="barcode" name="barcode" class="form-control border-start-0 @error('barcode') is-invalid @enderror" 
                                                   value="{{ old('barcode') }}" placeholder="Scan or enter barcode">
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea name="description" rows="3" class="form-control" placeholder="Optional product description...">{{ old('description') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Pricing Section --}}
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-0 pt-4 px-4">
                                <h5 class="mb-0 fw-bold text-success">
                                    <i class="bi bi-currency-dollar me-2"></i>Pricing & Costs
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="cost_price" class="form-label">Cost Price (KES)</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-white border-end-0 text-muted small">KES</span>
                                            <input type="number" step="0.01" id="cost_price" name="cost_price" 
                                                   class="form-control border-start-0 fw-bold text-muted" 
                                                   value="{{ old('cost_price') }}" placeholder="0.00">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="selling_price" class="form-label text-success fw-bold">Selling Price (KES) *</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-success-light border-success border-end-0 text-success fw-bold small">KES</span>
                                            <input type="number" step="0.01" id="selling_price" name="selling_price" 
                                                   class="form-control border-success border-start-0 fw-bold text-success" 
                                                   value="{{ old('selling_price') }}" required placeholder="0.00">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Column: Inventory & Allocation --}}
                    <div class="col-lg-4">
                        {{-- Inventory Settings --}}
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-0 pt-4 px-4">
                                <h6 class="mb-0 fw-bold text-warning">
                                    <i class="bi bi-shield-exclamation me-2"></i>Settings
                                </h6>
                            </div>
                            <div class="card-body p-4">
                                <label for="reorder_level" class="form-label">Reorder Level *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-bell text-warning"></i></span>
                                    <input type="number" id="reorder_level" name="reorder_level" class="form-control" 
                                           value="{{ old('reorder_level', 10) }}" required min="0">
                                </div>
                                <small class="text-muted mt-1 d-block">Alert me when stock falls below this.</small>
                            </div>
                        </div>

                        {{-- Stock Distribution Card --}}
                        <div class="card border-0 shadow-sm" style="border-top: 5px solid var(--primary) !important;">
                            <div class="card-header bg-transparent border-0 pt-4 px-4">
                                <h5 class="mb-0 fw-bold">
                                    <i class="bi bi-boxes me-2"></i>Stock Distribution
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                @if($isMain)
                                    <div class="mb-4">
                                        <label for="total_stock" class="form-label fw-bold">Grand Total Stock <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-primary text-white border-primary"><i class="bi bi-plus-square"></i></span>
                                            <input type="number" id="total_stock" name="total_stock" 
                                                   class="form-control border-primary fw-bold" 
                                                   value="{{ old('total_stock', 0) }}" min="0" required>
                                        </div>
                                        <small class="text-muted mt-2 d-block">Enter total units purchased. Allocate to branches below — remainder stays in Main Branch.</small>
                                    </div>

                                    <hr class="my-4 opacity-10">

                                    <p class="text-muted small mb-3">Distribute the total units below:</p>
                                    
                                    <div class="branch-inputs overflow-auto" style="max-height: 450px; padding-right: 5px;">
                                        @forelse ($branches as $branch)
                                            @php
                                                $isMainBranch = (bool) $branch->is_main;
                                            @endphp
                                            <div class="mb-3 p-3 {{ $isMainBranch ? 'bg-primary-light border-primary main-branch-card' : 'bg-light other-branch-card' }} rounded-3 transition-hover border hover-border-primary position-relative">
                                                @if($isMainBranch)
                                                    <span class="position-absolute top-0 end-0 mt-2 me-2">
                                                        <i class="bi bi-star-fill text-primary" title="Main Store/Source"></i>
                                                    </span>
                                                @endif
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="fw-bold small {{ $isMainBranch ? 'text-primary' : 'text-dark' }} d-flex align-items-center gap-2">
                                                        <i class="bi {{ $isMainBranch ? 'bi-house-heart-fill' : 'bi-geo-alt' }} opacity-50"></i>
                                                        {{ $branch->name }}
                                                        @if($isMainBranch)
                                                            <span class="badge bg-primary text-white" style="font-size: 0.6rem;">AUTO-ALLOCATED</span>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="input-group input-group-sm">
                                                    <input type="number"
                                                           name="branch_quantities[{{ $branch->id }}]"
                                                           class="form-control branch-qty text-center fw-bold shadow-none {{ $isMainBranch ? 'main-branch-qty bg-white' : 'other-branch-qty' }}"
                                                           min="0"
                                                           {{ $isMainBranch ? 'readonly' : '' }}
                                                           value="{{ old('branch_quantities.' . $branch->id, 0) }}"
                                                           placeholder="0">
                                                    <span class="input-group-text bg-white text-muted">Units</span>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center py-4">
                                                <i class="bi bi-building-exclamation fs-1 text-muted opacity-25"></i>
                                                <p class="text-muted small mt-2">No branches available.</p>
                                                <a href="{{ route('branches.create') }}" class="btn btn-sm btn-outline-primary">Add Branch</a>
                                            </div>
                                        @endforelse
                                    </div>

                                    <div id="allocation-error" class="alert alert-danger mt-3 d-none">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        Branch allocations exceed total stock!
                                    </div>
                                @else
                                    {{-- Single Branch Allocation (No changes needed for branch users) --}}
                                    <div class="text-center py-3">
                                        <div class="rounded-circle bg-primary-light d-inline-flex p-3 mb-3">
                                            <i class="bi bi-box-seam text-primary fs-3"></i>
                                        </div>
                                        <label for="branch_single_qty" class="form-label d-block fw-bold mb-3">
                                            Units for {{ $userBranch->name }}
                                        </label>
                                        <div class="input-group input-group-lg justify-content-center mx-auto" style="max-width: 200px;">
                                            <input type="number"
                                                   id="branch_single_qty"
                                                   name="branch_quantities[{{ $userBranch->id }}]"
                                                   class="form-control text-center fw-bold rounded-start"
                                                   min="0"
                                                   value="{{ old('branch_quantities.' . $userBranch->id, 0) }}"
                                                   placeholder="0">
                                            <span class="input-group-text bg-white text-muted">QTY</span>
                                        </div>
                                        <p class="text-muted small mt-3 px-2">Stock will be exclusively managed by your branch.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-3 mt-5 pb-5">
                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow-lg">
                        <i class="bi bi-check-circle me-2"></i>Save Product
                    </button>
                    <a href="{{ route('products.index') }}" class="btn btn-light btn-lg px-4 border">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .transition-hover {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .hover-border-primary:hover {
        border-color: var(--primary) !important;
        transform: translateY(-2px);
        background: white !important;
        box-shadow: var(--shadow-sm);
    }
    .bg-primary-light { background: var(--primary-light) !important; }
    .bg-success-light { background: var(--success-light) !important; }
    .input-group-text { border: 2px solid var(--border-color); }
    .form-control { border: 2px solid var(--border-color); }
    .card { border-radius: 20px !important; }
    .input-group:focus-within .input-group-text {
        border-color: var(--primary);
        color: var(--primary) !important;
    }
</style>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const totalStockInput = document.getElementById('total_stock');
    const mainBranchQty   = document.querySelector('.main-branch-qty');
    const otherBranchQtys = document.querySelectorAll('.other-branch-qty');
    const errorAlert      = document.getElementById('allocation-error');

    function updateAllocations() {
        if (!totalStockInput || !mainBranchQty) return;

        const total      = parseInt(totalStockInput.value || 0, 10);
        let allocated   = 0;
        
        otherBranchQtys.forEach(inp => {
            allocated += parseInt(inp.value || 0, 10);
        });

        const mainShare = total - allocated;

        if (mainShare < 0) {
            mainBranchQty.value = 0;
            mainBranchQty.closest('.main-branch-card').classList.add('border-danger', 'animate-shake');
            errorAlert.classList.remove('d-none');
            // Reset shake after animation
            setTimeout(() => mainBranchQty.closest('.main-branch-card').classList.remove('animate-shake'), 500);
        } else {
            mainBranchQty.value = mainShare;
            mainBranchQty.closest('.main-branch-card').classList.remove('border-danger');
            errorAlert.classList.add('d-none');
        }
        
        // Highlight active other branches
        otherBranchQtys.forEach(inp => {
            const parent = inp.closest('.other-branch-card');
            if (parseInt(inp.value) > 0) {
                parent.classList.add('bg-primary-light', 'border-primary');
                parent.classList.remove('bg-light');
            } else {
                parent.classList.remove('bg-primary-light', 'border-primary');
                parent.classList.add('bg-light');
            }
        });
    }

    if (totalStockInput) {
        // Listen for all possible changes
        ['input', 'change', 'keyup', 'click'].forEach(evt => {
            totalStockInput.addEventListener(evt, updateAllocations);
        });
        
        otherBranchQtys.forEach(inp => {
            ['input', 'change', 'keyup'].forEach(evt => {
                inp.addEventListener(evt, updateAllocations);
            });
        });
        
        // Final check on load
        setTimeout(updateAllocations, 100);
    }
});
</script>
<style>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}
.animate-shake { animation: shake 0.2s ease-in-out 0s 2; }
</style>
@endsection
@endsection