@extends('layouts.app')
@section('title', 'Expiry Tracking')
@section('page-title', 'Expiry Tracking')

@section('content')
<div class="container-fluid px-4 py-2">

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Expired (still in stock)</div>
                    <div class="fs-3 fw-bold text-danger">{{ number_format($expiredUnits) }} <span class="fs-6 fw-normal">units</span></div>
                    <div class="small text-muted">{{ $expired->count() }} batch(es)</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Expiring within {{ $window }} days</div>
                    <div class="fs-3 fw-bold text-warning">{{ number_format($expiringUnits) }} <span class="fs-6 fw-normal">units</span></div>
                    <div class="small text-muted">{{ $expiringSoon->count() }} batch(es)</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Value at risk (expired)</div>
                    <div class="fs-3 fw-bold text-danger">KSh {{ number_format($valueAtRisk, 2) }}</div>
                    <div class="small text-muted">at cost price</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Window selector --}}
    <form method="GET" class="d-flex align-items-center gap-2 mb-3">
        <label class="small text-muted mb-0">Expiring-soon window:</label>
        <select name="window" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
            @foreach([30, 60, 90, 180] as $opt)
                <option value="{{ $opt }}" {{ $window == $opt ? 'selected' : '' }}>{{ $opt }} days</option>
            @endforeach
        </select>
    </form>

    {{-- Expired --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-danger text-white py-2">
            <i class="bi bi-exclamation-octagon-fill me-1"></i> Expired Stock
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Branch</th>
                        <th>Batch #</th>
                        <th class="text-center">Expired On</th>
                        <th class="text-center">Days Ago</th>
                        <th class="text-center">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expired as $b)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $b->product->name ?? '—' }}</div>
                                <div class="text-muted small">{{ $b->product->sku ?? '' }}</div>
                            </td>
                            <td>{{ $b->branch->name ?? '—' }}</td>
                            <td>{{ $b->batch_number ?? '—' }}</td>
                            <td class="text-center text-danger fw-bold">{{ $b->expiry_date->format('d M Y') }}</td>
                            <td class="text-center">{{ $b->expiry_date->diffInDays(now()) }}</td>
                            <td class="text-center"><span class="badge bg-danger">{{ $b->quantity }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No expired stock. 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Expiring soon --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-warning text-dark py-2">
            <i class="bi bi-clock-history me-1"></i> Expiring within {{ $window }} days
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Branch</th>
                        <th>Batch #</th>
                        <th class="text-center">Expires On</th>
                        <th class="text-center">Days Left</th>
                        <th class="text-center">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expiringSoon as $b)
                        @php $daysLeft = now()->startOfDay()->diffInDays($b->expiry_date, false); @endphp
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $b->product->name ?? '—' }}</div>
                                <div class="text-muted small">{{ $b->product->sku ?? '' }}</div>
                            </td>
                            <td>{{ $b->branch->name ?? '—' }}</td>
                            <td>{{ $b->batch_number ?? '—' }}</td>
                            <td class="text-center">{{ $b->expiry_date->format('d M Y') }}</td>
                            <td class="text-center">
                                <span class="badge {{ $daysLeft <= 30 ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $daysLeft }} days</span>
                            </td>
                            <td class="text-center fw-bold">{{ $b->quantity }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Nothing expiring in this window.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
