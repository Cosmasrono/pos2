@extends('layouts.app')

@section('title', 'Expenses')
@section('page-title', 'Expense Management')

@section('content')
<div class="container-fluid px-4">
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Today's Expenses</p>
                            <h4 class="mb-0">KES {{ $todayExpenses }}</h4>
                        </div>
                        <div style="font-size: 2.5rem;">💸</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Pending Approval</p>
                            <h4 class="mb-0">{{ $pendingCount }}</h4>
                        </div>
                        <div style="font-size: 2.5rem;">⏳</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">This Month</p>
                            <h4 class="mb-0">KES {{ $monthExpenses }}</h4>
                        </div>
                        <div style="font-size: 2.5rem;">📊</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <a href="{{ route('expenses.create') }}" class="card border-0 shadow-sm text-decoration-none" style="height: 100%; cursor: pointer;">
                <div class="card-body d-flex align-items-center justify-content-center text-center">
                    <div>
                        <div style="font-size: 2.5rem; margin-bottom: 10px;">➕</div>
                        <p class="text-muted mb-0">Record New Expense</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Main Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold">📋 Recent Expenses</h6>
                <a href="{{ route('expenses.create') }}" class="btn btn-sm btn-success">+ New Expense</a>
            </div>
        </div>
        <div class="card-body p-0">
            @if($expenses->count())
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold">Category</th>
                                <th class="fw-bold">Description</th>
                                <th class="fw-bold text-end">Amount</th>
                                <th class="fw-bold">Date</th>
                                <th class="fw-bold">Method</th>
                                <th class="fw-bold">Status</th>
                                <th class="fw-bold text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenses as $expense)
                                <tr>
                                    <td>
                                        <span class="badge bg-info">{{ $expense->category_name ?? ($expense->category->name ?? 'N/A') }}</span>
                                    </td>
                                    <td>
                                        <small>{{ Str::limit($expense->description, 40) }}</small>
                                    </td>
                                    <td class="text-end fw-bold">KES {{ number_format($expense->amount, 2) }}</td>
                                    <td><small>{{ $expense->expense_date->format('M d, Y') }}</small></td>
                                    <td>
                                        @switch($expense->payment_method)
                                            @case('cash')
                                                💵 Cash
                                                @break
                                            @case('mpesa')
                                                📱 M-Pesa
                                                @break
                                            @case('bank_transfer')
                                                🏦 Bank
                                                @break
                                            @case('cheque')
                                                📄 Cheque
                                                @break
                                            @default
                                                {{ $expense->payment_method }}
                                        @endswitch
                                    </td>
                                    <td>
                                        @if($expense->isPending())
                                            <span class="badge bg-warning">⏳ Pending</span>
                                        @elseif($expense->isApproved())
                                            <span class="badge bg-success">✓ Approved</span>
                                        @else
                                            <span class="badge bg-danger">✗ Rejected</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($expense->isPending() && auth()->user()->isManager())
                                            <form action="{{ route('expenses.approve', $expense) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-xs btn-success" title="Approve">✓</button>
                                            </form>
                                            <button class="btn btn-xs btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $expense->id }}" title="Reject">✗</button>
                                        @else
                                            <a href="#" class="btn btn-xs btn-secondary" disabled>View</a>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Reject Modal -->
                                @if($expense->isPending() && auth()->user()->isManager())
                                    <div class="modal fade" id="rejectModal{{ $expense->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reject Expense</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('expenses.reject', $expense) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div class="modal-body">
                                                        <label class="form-label">Rejection Reason</label>
                                                        <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Reject</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <small class="text-muted">Showing {{ $expenses->count() }} of {{ $expenses->total() }} expenses</small>
                    {{ $expenses->links('pagination::bootstrap-4') }}
                </div>
            @else
                <div class="p-5 text-center">
                    <div style="font-size: 4rem; margin-bottom: 10px;">📋</div>
                    <p class="text-muted">No expenses recorded yet</p>
                    <a href="{{ route('expenses.create') }}" class="btn btn-sm btn-success">Record First Expense</a>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .btn-xs {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
</style>
@endsection
