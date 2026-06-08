@extends('layouts.app')

@section('title', 'Expenses')
@section('page-title', 'Expenses')

@section('content')
<div class="container-fluid px-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <h6 class="m-0 font-weight-bold text-primary">Expense List</h6>
            <div class="d-flex gap-2">
                <!-- <a href="{{ route('expense-categories.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-folder"></i> Categories
                </a> -->
                <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Record Expense
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Recorded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $expense)
                            <tr>
                                <td>{{ $expense->expense_date->format('M d, Y') }}</td>
                                <td>{{ $expense->category_name ?? ($expense->category->name ?? 'N/A') }}</td>
                                <td>{{ Str::limit($expense->description, 40) }}</td>
                                <td>KES {{ number_format($expense->amount, 2) }}</td>
                                <td><span class="badge bg-info">{{ ucfirst($expense->payment_method) }}</span></td>
                                <td>
                                    @if($expense->isPending())
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($expense->isApproved())
                                        <span class="badge bg-success">Approved</span>
                                    @else
                                        <span class="badge bg-danger">Rejected</span>
                                    @endif
                                </td>
                                <td>{{ $expense->recordedBy->name }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @if($expense->isPending() && (auth()->user()->isSuperAdmin() || auth()->user()->isManager()))
                                            <form action="{{ route('expenses.approve', $expense) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-success" title="Approve">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" 
                                                    data-bs-target="#rejectModal{{ $expense->id }}" title="Reject">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        @endif
                                        @if(!$expense->isApproved())
                                            <form action="{{ route('expenses.destroy', $expense) }}" method="POST" 
                                                  onsubmit="return confirm('Are you sure?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    <!-- Reject Modal -->
                                    <div class="modal fade" id="rejectModal{{ $expense->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('expenses.reject', $expense) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Reject Expense</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Reason for Rejection</label>
                                                            <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Reject</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No expenses found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $expenses->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
