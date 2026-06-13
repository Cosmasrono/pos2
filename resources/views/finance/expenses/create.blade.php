@extends('layouts.app')

@section('title', 'Record Expense')
@section('page-title', 'Record Expense')

@section('content')
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Expense Details</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('expenses.store') }}" method="POST">
                        @csrf
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <div class="input-group">
                                    <input type="text" name="category_name" id="category_name"
                                           class="form-control @error('category_name') is-invalid @enderror"
                                           value="{{ old('category_name') }}" required placeholder="Enter category">
                                    <button type="button" class="btn btn-outline-secondary" id="suggestCategoryBtn" title="AI Suggest">
                                        <span id="suggestBtnText">🤖 AI Suggest</span>
                                        <span id="suggestBtnSpinner" class="spinner-border spinner-border-sm d-none"></span>
                                    </button>
                                </div>
                                @error('category_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small id="categoryHint" class="text-success d-none"></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount (KES)</label>
                                <input type="number" name="amount" step="0.01" class="form-control @error('amount') is-invalid @enderror" 
                                       value="{{ old('amount') }}" required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Expense Date</label>
                                <input type="date" name="expense_date" class="form-control @error('expense_date') is-invalid @enderror" 
                                       value="{{ old('expense_date', date('Y-m-d')) }}" required>
                                @error('expense_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-control @error('payment_method') is-invalid @enderror" required>
                                    <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="mpesa" {{ old('payment_method') == 'mpesa' ? 'selected' : '' }}>M-Pesa</option>
                                    <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="cheque" {{ old('payment_method') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reference Number (Optional)</label>
                            <input type="text" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror" 
                                   value="{{ old('reference_number') }}" placeholder="Transaction ID, Cheque Number, etc.">
                            @error('reference_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                      rows="3" required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('expenses.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">Record Expense</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('suggestCategoryBtn').addEventListener('click', async function () {
    const description = document.querySelector('textarea[name="description"]').value.trim();
    const amount      = document.querySelector('input[name="amount"]').value;

    if (!description) {
        alert('Please enter a description first.');
        return;
    }

    const btnText    = document.getElementById('suggestBtnText');
    const spinner    = document.getElementById('suggestBtnSpinner');
    const hint       = document.getElementById('categoryHint');
    const catInput   = document.getElementById('category_name');

    btnText.classList.add('d-none');
    spinner.classList.remove('d-none');
    this.disabled = true;

    try {
        const res  = await fetch('{{ route("expenses.suggest-category") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ description, amount })
        });
        const data = await res.json();
        catInput.value = data.category;
        hint.textContent = 'Claude suggested: ' + data.category;
        hint.classList.remove('d-none');
    } catch (e) {
        alert('Could not get AI suggestion. Please enter manually.');
    } finally {
        btnText.classList.remove('d-none');
        spinner.classList.add('d-none');
        this.disabled = false;
    }
});
</script>
@endpush
