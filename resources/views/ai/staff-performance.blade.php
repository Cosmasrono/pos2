@extends('layouts.app')
@section('title', 'Staff Performance')
@section('page-title', 'Weekly Staff Performance')

@section('content')
<div class="container-fluid px-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="text-muted mb-0">Period: <strong>{{ $period }}</strong></p>
        <a href="{{ route('ai.staff-performance') }}" class="btn btn-sm btn-outline-secondary">Refresh</a>
    </div>

    @if(!empty($team_summary))
    <div class="alert alert-info d-flex gap-2 mb-4">
        <span style="font-size:1.3rem;">🤖</span>
        <div>{{ $team_summary }}</div>
    </div>
    @endif

    @if(empty($report))
        <div class="card border-0 shadow-sm"><div class="card-body text-center py-5 text-muted">No sales data for this week yet.</div></div>
    @else
    <div class="row g-4">
        @foreach($report as $i => $staff)
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                             style="width:36px;height:36px;">{{ strtoupper(substr($staff['name'],0,1)) }}</div>
                        <div>
                            <div class="fw-bold">{{ $staff['name'] }}</div>
                            @if($i === 0)<span class="badge bg-warning text-dark">Top Performer</span>@endif
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-primary">KSh {{ number_format($staff['total_revenue'], 0) }}</div>
                        <div class="small text-muted">{{ $staff['total_sales'] }} sales</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="text-muted small">Avg Transaction</div>
                            <div class="fw-bold">KSh {{ number_format($staff['avg_transaction'], 0) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Discount Rate</div>
                            <div class="fw-bold {{ $staff['discount_rate'] > 20 ? 'text-danger' : 'text-success' }}">
                                {{ $staff['discount_rate'] }}%
                            </div>
                        </div>
                    </div>
                    @if(!empty($staff['ai_note']))
                    <div class="alert alert-light mb-0 py-2 small">
                        🤖 {{ $staff['ai_note'] }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
