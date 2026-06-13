@extends('layouts.app')
@section('title', 'Financial Health Score')
@section('page-title', 'Monthly Financial Health')

@section('content')
<div class="container-fluid px-4">

    @php
        $scoreColor = $score >= 75 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
        $gradeColor = $score >= 75 ? '#22c55e' : ($score >= 50 ? '#f59e0b' : '#ef4444');
    @endphp

    <div class="row g-4 mb-4">
        {{-- Score Card --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center py-4">
                <div style="font-size:4rem; font-weight:900; color:{{ $gradeColor }};">{{ $score }}</div>
                <div style="font-size:2rem; color:{{ $gradeColor }}; font-weight:700;">{{ $grade }}</div>
                <div class="text-muted small mt-1">Health Score — {{ now()->format('F Y') }}</div>
                <div class="mt-2 mx-3">
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-{{ $scoreColor }}" style="width:{{ $score }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        {{-- KPIs --}}
        <div class="col-md-9">
            <div class="row g-3 h-100">
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100 p-3 text-center">
                        <div class="text-muted small">Revenue</div>
                        <div class="fw-bold">KSh {{ number_format($revenue, 0) }}</div>
                        <div class="small {{ $revenue_growth >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $revenue_growth >= 0 ? '+' : '' }}{{ $revenue_growth }}% vs last month
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100 p-3 text-center">
                        <div class="text-muted small">Net Profit</div>
                        <div class="fw-bold {{ $net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                            KSh {{ number_format($net_profit, 0) }}
                        </div>
                        <div class="small text-muted">Margin: {{ $gross_margin }}%</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100 p-3 text-center">
                        <div class="text-muted small">Expenses</div>
                        <div class="fw-bold">KSh {{ number_format($expenses, 0) }}</div>
                        <div class="small {{ $expense_growth <= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $expense_growth >= 0 ? '+' : '' }}{{ $expense_growth }}% vs last month
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100 p-3 text-center">
                        <div class="text-muted small">Overdue Invoices</div>
                        <div class="fw-bold {{ $overdue_invoices > 0 ? 'text-danger' : 'text-success' }}">
                            KSh {{ number_format($overdue_invoices, 0) }}
                        </div>
                        <div class="small text-muted">{{ $low_stock_count }} low stock items</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- AI Summary --}}
    @if(!empty($summary))
    <div class="alert alert-info d-flex gap-2 mb-4">
        <span style="font-size:1.3rem;">🤖</span>
        <div><strong>Claude's Assessment:</strong> {{ $summary }}</div>
    </div>
    @endif

    <div class="row g-4">
        {{-- Strengths --}}
        @if(!empty($strengths))
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-success text-white fw-bold">Strengths</div>
                <ul class="list-group list-group-flush">
                    @foreach($strengths as $s)
                    <li class="list-group-item d-flex gap-2"><span class="text-success">✓</span> {{ $s }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        {{-- Risks --}}
        @if(!empty($risks))
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-danger text-white fw-bold">Risks</div>
                <ul class="list-group list-group-flush">
                    @foreach($risks as $r)
                    <li class="list-group-item d-flex gap-2"><span class="text-danger">⚠</span> {{ $r }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        {{-- Action --}}
        @if(!empty($action))
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-primary text-white fw-bold">This Week's Action</div>
                <div class="card-body d-flex align-items-center">
                    <p class="mb-0 fs-6">{{ $action }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
