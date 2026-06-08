@extends('layouts.app')

@section('title', 'Fetch Stock')
@section('page-title', 'Fetch Stock from Another Branch')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-arrow-left-right"></i> Stock Transfer Request</h5>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info">
                    <strong>3/4 Rule Policy:</strong>
                    <ul class="mb-0 small">
                        <li>You can only fetch from a branch if they have at least 75% of their original stock allocation.</li>
                        <li>You cannot fetch more than 75% of their current allocation.</li>
                    </ul>
                </div>

                <form action="{{ route('stock-transfers.store') }}" method="POST">
                    @csrf

                    {{-- Product --}}
                    <div class="mb-3">
                        <label for="product_id" class="form-label fw-bold">Select Product</label>
                        <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                            <option value="">-- Choose Product --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->sku }} - {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Source Branch --}}
                    <div class="mb-3">
                        <label for="source_branch_id" class="form-label fw-bold">Source Branch</label>
                        <select name="source_branch_id" id="source_branch_id" class="form-select @error('source_branch_id') is-invalid @enderror" required>
                            <option value="">-- Select Source --</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('source_branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('source_branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Destination Branch (Super Admin only) --}}
                    @if(!auth()->user()->branch_id)
                    <div class="mb-3">
                        <label for="target_branch_id" class="form-label fw-bold">Destination Branch</label>
                        <select name="target_branch_id" id="target_branch_id"
                                class="form-select @error('target_branch_id') is-invalid @enderror" required>
                            <option value="">-- Select Destination --</option>
                            @foreach($allBranches as $branch)
                                <option value="{{ $branch->id }}" {{ old('target_branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('target_branch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted">As Super Admin, select which branch will receive the stock.</div>
                    </div>
                    @endif

                    {{-- Dynamic Stock Info Panel --}}
                    <div id="stock-info-panel" class="mb-3" style="display:none;">
                        <div class="card border-0 bg-light">
                            <div class="card-body py-3">
                                <h6 class="text-muted mb-3"><i class="bi bi-bar-chart-fill me-1"></i> Branch Stock Info</h6>
                                <div class="row text-center g-2">
                                    <div class="col-4">
                                        <div class="p-2 rounded bg-white border">
                                            <div class="small text-muted">In Stock</div>
                                            <div id="info-in-stock" class="fs-5 fw-bold text-primary">—</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded bg-white border">
                                            <div class="small text-muted">Allocation</div>
                                            <div id="info-allocation" class="fs-5 fw-bold text-secondary">—</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded bg-white border">
                                            <div class="small text-muted">Max You Can Fetch</div>
                                            <div id="info-max-fetch" class="fs-5 fw-bold text-success">—</div>
                                        </div>
                                    </div>
                                </div>
                                <div id="info-eligibility-msg" class="mt-2 small text-center"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Stock Not Found Warning --}}
                    <div id="stock-not-found" class="alert alert-warning mb-3" style="display:none;">
                        <i class="bi bi-exclamation-triangle"></i> This branch has no stock record for the selected product.
                    </div>

                    {{-- Quantity --}}
                    <div class="mb-3">
                        <label for="quantity" class="form-label fw-bold">Quantity to Fetch</label>
                        <input type="number" name="quantity" id="quantity"
                               class="form-control @error('quantity') is-invalid @enderror"
                               value="{{ old('quantity') }}" min="1" required>
                        @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div id="transfer-limit-hint" class="form-text text-muted"></div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Initiate Fetch
                        </button>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const stockInfoUrl  = "{{ route('stock-transfers.stock-info') }}";

    const productSelect  = document.getElementById('product_id');
    const branchSelect   = document.getElementById('source_branch_id');
    const targetSelect   = document.getElementById('target_branch_id'); // null for branch managers
    const panel          = document.getElementById('stock-info-panel');
    const notFound       = document.getElementById('stock-not-found');
    const infoInStock    = document.getElementById('info-in-stock');
    const infoAllocation = document.getElementById('info-allocation');
    const infoMaxFetch   = document.getElementById('info-max-fetch');
    const infoEligibility= document.getElementById('info-eligibility-msg');
    const hintEl         = document.getElementById('transfer-limit-hint');
    const quantityInput  = document.getElementById('quantity');

    async function fetchStockInfo() {
        const productId = productSelect.value;
        const branchId  = branchSelect.value;

        // Reset UI
        panel.style.display    = 'none';
        notFound.style.display = 'none';
        hintEl.textContent     = '';
        quantityInput.max      = '';

        if (!productId || !branchId) return;

        try {
            const res  = await fetch(`${stockInfoUrl}?product_id=${productId}&source_branch_id=${branchId}`);
            const data = await res.json();

            if (!data.available) {
                notFound.style.display = 'block';
                return;
            }

            infoInStock.textContent    = data.quantity_in_stock;
            infoAllocation.textContent = data.initial_allocation;
            infoMaxFetch.textContent   = data.max_fetch;

            if (data.eligible) {
                infoEligibility.innerHTML = `<span class="text-success"><i class="bi bi-check-circle"></i> This branch is eligible for transfer.</span>`;
                hintEl.textContent        = `You can fetch up to ${data.max_fetch} units from this branch.`;
                quantityInput.max         = data.max_fetch;
            } else {
                infoEligibility.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle"></i> This branch is below the 75% threshold and cannot be fetched from.</span>`;
            }

            // Prevent source = destination for Super Admin
            if (targetSelect) {
                Array.from(targetSelect.options).forEach(opt => {
                    opt.disabled = opt.value === branchId;
                    if (opt.disabled && opt.selected) {
                        opt.selected  = false;
                        targetSelect.value = '';
                    }
                });
            }

            panel.style.display = 'block';

        } catch (e) {
            console.error('Failed to fetch stock info', e);
        }
    }

    productSelect.addEventListener('change', fetchStockInfo);
    branchSelect.addEventListener('change',  fetchStockInfo);

    // Also re-run when destination changes to keep source options in sync
    if (targetSelect) {
        targetSelect.addEventListener('change', () => {
            const sourceOptions = branchSelect.options;
            const targetVal     = targetSelect.value;
            Array.from(sourceOptions).forEach(opt => {
                opt.disabled = opt.value === targetVal;
                if (opt.disabled && opt.selected) {
                    opt.selected      = false;
                    branchSelect.value = '';
                    // Reset info panel since source changed
                    panel.style.display    = 'none';
                    notFound.style.display = 'none';
                    hintEl.textContent     = '';
                }
            });
        });
    }
</script>
@endpush