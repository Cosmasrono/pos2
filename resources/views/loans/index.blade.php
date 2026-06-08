@extends('layouts.app')

@section('title', 'Loans')
@section('page-title', 'Loan Management')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="card-title text-muted small mb-1">Active Loans</h6>
                            <div class="stat-value h4 fw-bold mb-0">{{ $stats['total_active'] }}</div>
                        </div>
                        <div class="icon-wrapper" style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-credit-card" style="font-size: 24px; background: linear-gradient(135deg, hsl(243, 75%, 59%) 0%, hsl(262, 83%, 58%) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm border-0 border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="card-title text-muted small mb-1">Total Outstanding</h6>
                            <div class="stat-value h4 fw-bold mb-0 text-warning">KES {{ number_format($stats['total_outstanding'], 2) }}</div>
                        </div>
                        <div class="icon-wrapper" style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(251, 191, 36, 0.05) 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-cash-stack" style="font-size: 24px; background: linear-gradient(135deg, hsl(38, 92%, 50%) 0%, hsl(45, 93%, 47%) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm border-0 border-start border-danger border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="card-title text-muted small mb-1">Overdue Loans</h6>
                            <div class="stat-value h4 fw-bold mb-0 text-danger">{{ $stats['total_overdue'] }}</div>
                        </div>
                        <div class="icon-wrapper" style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(248, 113, 113, 0.05) 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-exclamation-triangle" style="font-size: 24px; background: linear-gradient(135deg, hsl(0, 84%, 60%) 0%, hsl(340, 82%, 52%) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm border-0 border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="card-title text-muted small mb-1">MTD Collected</h6>
                            <div class="stat-value h4 fw-bold mb-0 text-success">KES {{ number_format($stats['mtd_collected'], 2) }}</div>
                        </div>
                        <div class="icon-wrapper" style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(52, 211, 153, 0.05) 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-graph-up-arrow" style="font-size: 24px; background: linear-gradient(135deg, hsl(142, 71%, 45%) 0%, hsl(158, 64%, 52%) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs and Create Button -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link {{ $status === 'all' ? 'active' : '' }}" href="{{ route('loans.index', ['status' => 'all']) }}">All</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $status === 'active' ? 'active' : '' }}" href="{{ route('loans.index', ['status' => 'active']) }}">Active</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $status === 'overdue' ? 'active' : '' }}" href="{{ route('loans.index', ['status' => 'overdue']) }}">Overdue</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $status === 'paid' ? 'active' : '' }}" href="{{ route('loans.index', ['status' => 'paid']) }}">Paid</a>
                    </li>
                </ul>
            </div>
            <a href="{{ route('loans.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> New Loan
            </a>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Loan #</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Amount Paid</th>
                            <th>Balance</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loans as $loan)
                            <tr>
                                <td>
                                    <a href="{{ route('loans.show', $loan) }}" class="text-decoration-none fw-bold">
                                        {{ $loan->loan_number }}
                                    </a>
                                </td>
                                <td>{{ $loan->customer->name }}</td>
                                <td><strong>KES {{ number_format($loan->total_amount, 2) }}</strong></td>
                                <td>KES {{ number_format($loan->amount_paid, 2) }}</td>
                                <td>
                                    <strong class="{{ $loan->balance > 0 ? 'text-warning' : 'text-success' }}">
                                        KES {{ number_format($loan->balance, 2) }}
                                    </strong>
                                </td>
                                <td>
                                    {{ $loan->due_date->format('M d, Y') }}
                                    @if($loan->is_overdue)
                                        <span class="badge bg-danger">{{ $loan->days_overdue }} days overdue</span>
                                    @endif
                                </td>
                                <td>
                                    @if($loan->status === 'active')
                                        <span class="badge bg-info">Active</span>
                                    @elseif($loan->status === 'paid')
                                        <span class="badge bg-success">Paid</span>
                                    @elseif($loan->status === 'overdue')
                                        <span class="badge bg-danger">Overdue</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($loan->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('loans.show', $loan) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No loans found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($loans->hasPages())
        <div class="card-footer">
            {{ $loans->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
