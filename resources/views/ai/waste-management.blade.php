@extends('layouts.app')

@section('title', 'AI Waste Management')
@section('page-title', 'AI Waste Management')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-lg border-0 mb-4 bg-gradient-warning text-white overflow-hidden glass-card">
                <div class="card-body p-4 position-relative">
                    <h3 class="fw-bold mb-2"><i class="bi bi-recycle me-2"></i>Waste Risk Dashboard</h3>
                    <p class="mb-0 opacity-75">Predictive analysis targeting items near expiry that are unlikely to sell at current rates.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @forelse($risks as $item)
            <div class="col-md-6 col-xl-4 mb-4">
                <div class="card border-0 shadow-sm h-100 glass-card hover-lift">
                    <div class="card-header bg-white bg-opacity-50 border-0 py-3 d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">{{ $item['product']->name }}</h6>
                            <span class="badge bg-{{ $item['risk']['risk_level'] === 'critical' ? 'danger' : ($item['risk']['risk_level'] === 'high' ? 'warning' : 'info') }} bg-opacity-10 text-{{ $item['risk']['risk_level'] === 'critical' ? 'danger' : ($item['risk']['risk_level'] === 'high' ? 'warning' : 'info') }} rounded-pill px-3">
                                {{ strtoupper($item['risk']['risk_level']) }} RISK
                            </span>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block fw-bold">Expiry Date</small>
                            <span class="fw-bold text-danger font-outfit">{{ \Carbon\Carbon::parse($item['product']->expiry_date)->format('M d, Y') }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="d-flex justify-content-between small mb-2 fw-bold">
                                <span class="text-muted">Units at Risk:</span>
                                <span class="text-danger">{{ $item['risk']['units_at_risk'] }} units</span>
                            </div>
                            <div class="progress" style="height: 10px; border-radius: 5px;">
                                @php $riskPercent = min(100, ($item['risk']['units_at_risk'] / ($item['product']->quantity_in_stock ?: 1)) * 100); @endphp
                                <div class="progress-bar bg-danger shadow-sm" style="width: {{ $riskPercent }}%"></div>
                            </div>
                        </div>
                        <div class="alert alert-light bg-opacity-50 border-0 small mb-0 p-3 rounded-4">
                            <i class="bi bi-info-circle text-primary me-2"></i> <span class="text-muted">{{ $item['risk']['action'] }}</span>
                        </div>
                    </div>
                    <div class="card-footer bg-white bg-opacity-20 border-0 py-4">
                        <button class="btn btn-outline-danger w-100 rounded-pill py-2 fw-bold shadow-sm" onclick="alert('Redirecting to Promotion Creation...')">
                            <i class="bi bi-megaphone me-2"></i> Initiate Clearance Sale
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm glass-card">
                    <div class="card-body text-center py-5">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle p-4 d-inline-flex mb-3">
                            <i class="bi bi-check2-circle fs-1"></i>
                        </div>
                        <h4 class="fw-bold">No significant waste risks detected</h4>
                        <p class="text-muted">All expiring items are projected to sell before their expiry date.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

body {
    background-color: #f8fafc;
    font-family: 'Inter', sans-serif;
}

h1, h2, h3, h4, h5, h6 {
    font-family: 'Outfit', sans-serif;
}

.font-outfit {
    font-family: 'Outfit', sans-serif;
}

.glass-card {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
}

.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
}
</style>
@endsection

