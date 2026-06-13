@extends('layouts.app')
@section('title', 'Loss Prevention Alerts')
@section('page-title', 'Loss Prevention Alerts')

@section('content')
<div class="container-fluid px-4">

    {{-- AI Summary --}}
    @if($ai_summary)
    <div class="alert alert-info d-flex gap-2 align-items-start mb-4">
        <span style="font-size:1.3rem;">🤖</span>
        <div><strong>Claude's Recommendation:</strong> {{ $ai_summary }}</div>
    </div>
    @endif

    {{-- KPI row --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-1 fw-bold {{ $alert_count > 0 ? 'text-danger' : 'text-success' }}">{{ $alert_count }}</div>
                <div class="text-muted small">Total Alerts (7 days)</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-1 fw-bold {{ $high_count > 0 ? 'text-danger' : 'text-success' }}">{{ $high_count }}</div>
                <div class="text-muted small">High Severity</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-1 fw-bold text-warning">{{ $alert_count - $high_count }}</div>
                <div class="text-muted small">Medium Severity</div>
            </div>
        </div>
    </div>

    {{-- Alerts table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0 fw-bold">Alerts (Last 7 Days)</h6>
            <a href="{{ route('ai.loss-prevention') }}" class="btn btn-sm btn-outline-secondary">Refresh</a>
        </div>
        <div class="card-body p-0">
            @if(empty($alerts))
                <div class="text-center py-5 text-success">
                    <i class="bi bi-shield-check" style="font-size:3rem;"></i>
                    <p class="mt-3 fw-bold">No alerts — everything looks clean!</p>
                </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Severity</th>
                            <th>Type</th>
                            <th>Details</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alerts as $alert)
                        <tr>
                            <td>
                                @if($alert['severity'] === 'high')
                                    <span class="badge bg-danger">High</span>
                                @else
                                    <span class="badge bg-warning text-dark">Medium</span>
                                @endif
                            </td>
                            <td>
                                @if($alert['type'] === 'cash_variance')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Cash Variance</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">High Discounts</span>
                                @endif
                            </td>
                            <td>{{ $alert['message'] }}</td>
                            <td class="text-muted small">{{ $alert['date'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

</div>
@endsection
