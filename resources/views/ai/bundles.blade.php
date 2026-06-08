@extends('layouts.app')

@section('title', 'AI Bundle Suggestions')
@section('page-title', 'AI Bundle Suggestions')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg bg-gradient-info text-white overflow-hidden glass-card">
                <div class="card-body p-4 position-relative">
                    <h3 class="fw-bold mb-2"><i class="bi bi-box2-heart me-2"></i>Product Affinity Analysis</h3>
                    <p class="mb-0 opacity-75">These product combinations are frequently purchased together. Creating bundles with small discounts can significantly increase your average transaction value.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @forelse($bundles as $bundle)
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100 glass-card hover-lift">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                                Bought together {{ $bundle['frequency'] }} times
                            </div>
                            <div class="text-success fw-bold font-outfit">
                                {{ number_format(($bundle['frequency'] / 100) * 100, 1) }}% Affinity
                            </div>
                        </div>
                        
                        <div class="row align-items-center text-center">
                            <div class="col-5">
                                <div class="p-4 bg-light bg-opacity-50 rounded-4 mb-2 shadow-sm">
                                    <i class="bi bi-box fs-2 text-primary opacity-75"></i>
                                </div>
                                <h6 class="fw-bold mb-0 text-dark">{{ $bundle['product1'] }}</h6>
                            </div>
                            <div class="col-2 text-muted">
                                <i class="bi bi-plus-lg fs-3 opacity-50"></i>
                            </div>
                            <div class="col-5">
                                <div class="p-4 bg-light bg-opacity-50 rounded-4 mb-2 shadow-sm">
                                    <i class="bi bi-box fs-2 text-info opacity-75"></i>
                                </div>
                                <h6 class="fw-bold mb-0 text-dark">{{ $bundle['product2'] }}</h6>
                            </div>
                        </div>

                        <hr class="my-4 opacity-10">

                        <div class="d-flex justify-content-between align-items-end">
                            <div>
                                <small class="text-muted d-block mb-1">Combined Value</small>
                                <del class="text-muted fs-5">KSh {{ number_format($bundle['individual_total'], 2) }}</del>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block mb-1">Suggested Bundle</small>
                                <h3 class="fw-bold text-success mb-0">KSh {{ number_format($bundle['suggested_bundle_price'], 2) }}</h3>
                                <div class="badge bg-success bg-opacity-10 text-success mt-2">
                                    Save KSh {{ number_format($bundle['discount_amount'], 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white bg-opacity-20 border-0 p-4 pt-0">
                        <button class="btn btn-primary w-100 py-3 rounded-pill shadow-sm" onclick="alert('Creating bundle configuration...')">
                            <i class="bi bi-magic me-2"></i> Create Smart Bundle
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm glass-card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-search text-muted fs-1 mb-3 opacity-50"></i>
                        <h5 class="fw-bold">No strong affinities detected</h5>
                        <p class="text-muted">Sell more items together to see AI bundle recommendations here.</p>
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

.bg-gradient-info {
    background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%) !important;
}
</style>
@endsection

