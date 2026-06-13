@extends('layouts.app')

@section('title', 'Create Loan')
@section('page-title', 'Create New Loan')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Loan Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('loans.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_id" class="form-label">Customer *</label>
                                <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                                    <option value="">Select Customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} - {{ $customer->phone }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="total_amount" class="form-label">Total Amount (KES) *</label>
                                <input type="number" step="0.01" name="total_amount" id="total_amount" 
                                       class="form-control @error('total_amount') is-invalid @enderror" 
                                       value="{{ old('total_amount') }}" required>
                                @error('total_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- AI Loan Risk Assessment -->
                        <div id="loanRiskWidget" class="d-none mb-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-3">
                                    <div id="loanRiskLoading" class="d-none text-center py-2">
                                        <span class="spinner-border spinner-border-sm text-primary me-2"></span>
                                        <span class="text-muted small">Claude is assessing loan risk...</span>
                                    </div>
                                    <div id="loanRiskResult" class="d-none">
                                        <div class="d-flex align-items-center gap-3">
                                            <span style="font-size:1.5rem;">🤖</span>
                                            <div>
                                                <div class="fw-bold small mb-1">AI Risk Assessment</div>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span id="riskBadge" class="badge"></span>
                                                    <span class="small text-muted">Score: <strong id="riskScore"></strong>/100</span>
                                                </div>
                                                <p class="mb-1 small" id="riskReason"></p>
                                                <div class="small fw-bold" id="riskRecommendation"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="product_description" class="form-label">Product/Service Description *</label>
                            <textarea name="product_description" id="product_description" rows="3" 
                                      class="form-control @error('product_description') is-invalid @enderror" 
                                      required>{{ old('product_description') }}</textarea>
                            @error('product_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="loan_date" class="form-label">Loan Date *</label>
                                <input type="date" name="loan_date" id="loan_date" 
                                       class="form-control @error('loan_date') is-invalid @enderror" 
                                       value="{{ old('loan_date', date('Y-m-d')) }}" required>
                                @error('loan_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="due_date" class="form-label">Due Date *</label>
                                <input type="date" name="due_date" id="due_date" 
                                       class="form-control @error('due_date') is-invalid @enderror" 
                                       value="{{ old('due_date') }}" required>
                                @error('due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="interest_rate" class="form-label">Interest Rate (%)</label>
                                <input type="number" step="0.01" name="interest_rate" id="interest_rate" 
                                       class="form-control @error('interest_rate') is-invalid @enderror" 
                                       value="{{ old('interest_rate') }}" placeholder="Optional">
                                @error('interest_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">Initial Payment (Optional)</h6>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="initial_payment" class="form-label">Amount (KES)</label>
                                <input type="number" step="0.01" name="initial_payment" id="initial_payment" 
                                       class="form-control @error('initial_payment') is-invalid @enderror" 
                                       value="{{ old('initial_payment') }}">
                                @error('initial_payment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                                    <option value="">Select Method</option>
                                    <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="mpesa" {{ old('payment_method') == 'mpesa' ? 'selected' : '' }}>M-Pesa</option>
                                    <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>Card</option>
                                    <option value="other" {{ old('payment_method') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="reference_number" class="form-label">Reference Number</label>
                                <input type="text" name="reference_number" id="reference_number" 
                                       class="form-control @error('reference_number') is-invalid @enderror" 
                                       value="{{ old('reference_number') }}" placeholder="e.g., M-Pesa code">
                                @error('reference_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea name="notes" id="notes" rows="2" 
                                      class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('loans.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Create Loan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const customerSel = document.getElementById('customer_id');
    const amountInput = document.getElementById('total_amount');
    const widget = document.getElementById('loanRiskWidget');
    const loading = document.getElementById('loanRiskLoading');
    const result = document.getElementById('loanRiskResult');
    let debounceTimer = null;

    function checkAndAssess() {
        const customerId = customerSel.value;
        const amount = parseFloat(amountInput.value);
        if (!customerId || !amount || amount <= 0) { widget.classList.add('d-none'); return; }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            widget.classList.remove('d-none');
            loading.classList.remove('d-none');
            result.classList.add('d-none');

            try {
                const res = await fetch('{{ route("ai.loan-risk") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ customer_id: customerId, amount: amount })
                });
                const data = await res.json();
                if (data.risk) {
                    const badge = document.getElementById('riskBadge');
                    badge.textContent = data.risk.toUpperCase();
                    badge.className = 'badge ' + (data.risk === 'low' ? 'bg-success' : data.risk === 'medium' ? 'bg-warning text-dark' : 'bg-danger');
                    document.getElementById('riskScore').textContent = data.score ?? '—';
                    document.getElementById('riskReason').textContent = data.reason ?? '';
                    const rec = document.getElementById('riskRecommendation');
                    rec.textContent = data.recommendation ? '→ ' + data.recommendation.charAt(0).toUpperCase() + data.recommendation.slice(1) : '';
                    rec.className = 'small fw-bold ' + (data.recommendation === 'approve' ? 'text-success' : data.recommendation === 'reject' ? 'text-danger' : 'text-warning');
                    loading.classList.add('d-none');
                    result.classList.remove('d-none');
                }
            } catch (e) {
                loading.classList.add('d-none');
                widget.classList.add('d-none');
            }
        }, 800);
    }

    customerSel.addEventListener('change', checkAndAssess);
    amountInput.addEventListener('input', checkAndAssess);
});
</script>
@endpush
@endsection
