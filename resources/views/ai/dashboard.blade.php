@extends('layouts.app')

@section('title', 'AI Decision Center')
@section('page-title', 'AI Decision Center')

@section('content')

<!-- Toast Notification Container -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <div id="actionToast" class="toast align-items-center border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fw-medium" id="toastMessage">Action completed.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="confirmModalTitle">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2 pb-3">
                <p class="text-muted mb-0" id="confirmModalBody"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn rounded-pill px-4 fw-semibold" id="confirmModalBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-4 py-4">
    <!-- Summary Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white shadow-lg border-0 overflow-hidden glass-card">
                <div class="card-body p-4 position-relative">
                    <div class="row align-items-center g-3">
                        <div class="col-lg-8">
                            <h3 class="fw-bold mb-1"><i class="bi bi-robot me-2"></i>AI Decision Center</h3>
                            <p class="mb-0 opacity-75">Autonomous inventory optimization powered by artificial intelligence</p>
                            <div class="mt-3 p-3 rounded bg-white bg-opacity-10 border border-white border-opacity-20 backdrop-blur">
                                <i class="bi bi-chat-left-dots me-2"></i><strong>AI Executive Summary:</strong> {{ $globalAIInsight }}
                            </div>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <div class="d-flex flex-column align-items-lg-end gap-2">
                                <div class="badge bg-white text-primary px-3 px-md-4 py-2 py-md-3 rounded-pill shadow-sm fs-6">
                                    <i class="bi bi-cpu me-1"></i> {{ count($reorderRecommendations) + count($pricingRecommendations) + count($wasteRisks) }} Decisions
                                </div>
                                <small class="opacity-60"><i class="bi bi-clock me-1"></i>Updated {{ now()->format('g:i A') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Briefing -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm glass-card" style="border-left: 5px solid #6366f1 !important;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                            <i class="bi bi-briefcase fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Daily AI Executive Briefing</h5>
                            <small class="text-muted">{{ now()->format('l, F j, Y') }}</small>
                        </div>
                    </div>
                    <div class="bg-light bg-opacity-50 p-4 rounded-4 border border-info border-opacity-10">
                        <p class="mb-0 fs-5 lh-lg font-outfit text-dark">{{ $dailyBriefing }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Decision Categories Navigation -->
    <div class="row mb-4 g-3">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 hover-lift glass-card">
                <div class="card-body text-center p-4">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-3 d-inline-flex mb-3">
                        <i class="bi bi-box-seam fs-1"></i>
                    </div>
                    <h2 class="fw-bold text-gradient-danger">{{ count($reorderRecommendations) }}</h2>
                    <p class="text-muted mb-0 fw-medium">Reorder Decisions</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 hover-lift glass-card">
                <div class="card-body text-center p-4">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 d-inline-flex mb-3">
                        <i class="bi bi-tag fs-1"></i>
                    </div>
                    <h2 class="fw-bold text-gradient-success">{{ count($pricingRecommendations) }}</h2>
                    <p class="text-muted mb-0 fw-medium">Pricing Opportunities</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 hover-lift glass-card">
                <div class="card-body text-center p-4">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-3 d-inline-flex mb-3">
                        <i class="bi bi-exclamation-triangle fs-1"></i>
                    </div>
                    <h2 class="fw-bold text-gradient-warning">{{ count($wasteRisks) }}</h2>
                    <p class="text-muted mb-0 fw-medium">Waste Risks</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 hover-lift glass-card">
                <div class="card-body text-center p-4">
                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-3 d-inline-flex mb-3">
                        <i class="bi bi-box2-heart fs-1"></i>
                    </div>
                    <h2 class="fw-bold text-gradient-info">{{ count($bundleSuggestions) }}</h2>
                    <p class="text-muted mb-0 fw-medium">Bundle Suggestions</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== BRANCH SALES LINK CARD ===================== -->
    <div class="row mb-4">
        <div class="col-12">
            <a href="{{ route('ai.branch-sales') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm glass-card hover-lift" style="background: linear-gradient(135deg, rgba(14,165,233,0.08) 0%, rgba(99,102,241,0.08) 100%) !important;">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="rounded-4 p-2 p-md-3 shadow-sm text-white" style="background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%);">
                                    <i class="bi bi-shop fs-3 fs-md-2"></i>
                                </div>
                            </div>
                            <div class="col">
                                <h5 class="fw-bold mb-1 text-dark font-outfit">Branch Sales Intelligence</h5>
                                <p class="mb-0 text-muted small">View top-selling products per branch, revenue comparisons, Cash vs Mpesa breakdown, and AI-powered branch performance insights.</p>
                            </div>
                            <div class="col-auto">
                                <span class="badge rounded-pill px-4 py-2 text-white fw-semibold" style="background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%);">
                                    <i class="bi bi-arrow-right me-1"></i> View Analysis
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <!-- ================================================================= -->

    <!-- Reorder Recommendations -->
    @if(count($reorderRecommendations) > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 glass-card">
                <div class="card-header bg-white bg-opacity-50 border-0 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-box-seam text-danger me-2"></i>Smart Reorder Decisions
                        <span class="badge bg-danger bg-opacity-10 text-danger ms-2">{{ count($reorderRecommendations) }} urgent</span>
                    </h5>
                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Click Auto-Reorder to create a purchase order automatically</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Product</th>
                                    <th>Stock</th>
                                    <th class="d-none d-md-table-cell">Reorder Point</th>
                                    <th class="d-none d-md-table-cell">Suggested Qty</th>
                                    <th>Stockout In</th>
                                    <th class="d-none d-sm-table-cell">Urgency</th>
                                    <th class="text-end pe-4">Decision</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reorderRecommendations as $item)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold">{{ $item['product']->name }}</div>
                                            <small class="text-muted">SKU: {{ $item['product']->sku }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $item['recommendation']['urgency'] === 'high' ? 'bg-danger' : 'bg-warning' }} fs-6 px-3">
                                                {{ $item['product']->quantity_in_stock }}
                                            </span>
                                        </td>
                                        <td class="d-none d-md-table-cell text-muted">{{ $item['recommendation']['reorder_point'] }}</td>
                                        <td class="d-none d-md-table-cell">
                                            <span class="badge bg-primary bg-opacity-10 text-primary fs-6 px-3">
                                                +{{ $item['recommendation']['recommended_qty'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-bold {{ $item['recommendation']['days_to_stockout'] <= 3 ? 'text-danger' : 'text-warning' }}">
                                                {{ $item['recommendation']['days_to_stockout'] }}d
                                            </span>
                                        </td>
                                        <td class="d-none d-sm-table-cell">
                                            @if($item['recommendation']['urgency'] === 'high')
                                                <span class="badge bg-danger"><i class="bi bi-exclamation-circle me-1"></i>Critical</span>
                                            @elseif($item['recommendation']['urgency'] === 'medium')
                                                <span class="badge bg-warning text-dark"><i class="bi bi-dash-circle me-1"></i>Medium</span>
                                            @else
                                                <span class="badge bg-info"><i class="bi bi-info-circle me-1"></i>Low</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-danger rounded-pill px-3 me-1 action-btn"
                                                data-action="reorder"
                                                data-product-id="{{ $item['product']->id }}"
                                                data-qty="{{ $item['recommendation']['recommended_qty'] }}"
                                                data-product-name="{{ $item['product']->name }}">
                                                <span class="btn-label"><i class="bi bi-cart-plus me-1"></i>Auto-Reorder</span>
                                                <span class="btn-spinner d-none"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Processing…</span>
                                            </button>
                                            <a href="{{ route('ai.product', $item['product']) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                <i class="bi bi-eye me-1"></i>Details
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Pricing Optimization -->
    @if(count($pricingRecommendations) > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 glass-card">
                <div class="card-header bg-white bg-opacity-50 border-0 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-tag text-success me-2"></i>AI Pricing Optimization
                        <span class="badge bg-success bg-opacity-10 text-success ms-2">{{ count($pricingRecommendations) }} opportunities</span>
                    </h5>
                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Price changes take effect immediately upon applying</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Product</th>
                                    <th>Current Price</th>
                                    <th>Suggested Price</th>
                                    <th class="d-none d-sm-table-cell">Change</th>
                                    <th class="d-none d-md-table-cell">Reason</th>
                                    <th class="d-none d-lg-table-cell">Confidence</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pricingRecommendations as $item)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold">{{ $item['product']->name }}</div>
                                            <small class="text-muted">{{ $item['product']->quantity_in_stock }} in stock</small>
                                        </td>
                                        <td class="text-muted">KSh {{ number_format($item['pricing']['current_price'], 2) }}</td>
                                        <td>
                                            <strong class="{{ $item['pricing']['price_change'] > 0 ? 'text-success' : 'text-danger' }}">
                                                KSh {{ number_format($item['pricing']['suggested_price'], 2) }}
                                            </strong>
                                        </td>
                                        <td class="d-none d-sm-table-cell">
                                            <span class="badge {{ $item['pricing']['price_change'] > 0 ? 'bg-success' : 'bg-danger' }} bg-opacity-10 {{ $item['pricing']['price_change'] > 0 ? 'text-success' : 'text-danger' }} rounded-pill px-3">
                                                {{ $item['pricing']['price_change'] > 0 ? '+' : '' }}{{ $item['pricing']['price_change_percentage'] }}%
                                            </span>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <small class="text-muted">{{ $item['pricing']['reason'] }}</small>
                                        </td>
                                        <td class="d-none d-lg-table-cell" style="min-width: 130px;">
                                            <div class="progress mb-1" style="height: 6px; border-radius: 3px;">
                                                <div class="progress-bar bg-success" style="width: {{ $item['pricing']['confidence'] }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ $item['pricing']['confidence'] }}% confidence</small>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-success rounded-pill px-4 shadow-sm action-btn"
                                                data-action="pricing"
                                                data-product-id="{{ $item['product']->id }}"
                                                data-new-price="{{ $item['pricing']['suggested_price'] }}"
                                                data-product-name="{{ $item['product']->name }}"
                                                data-change-pct="{{ ($item['pricing']['price_change'] > 0 ? '+' : '') . $item['pricing']['price_change_percentage'] }}">
                                                <span class="btn-label"><i class="bi bi-check2 me-1"></i>Apply</span>
                                                <span class="btn-spinner d-none"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving…</span>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row mb-4">
        <!-- Trending Analysis -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100 glass-card">
                <div class="card-header bg-white bg-opacity-50 border-0 py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-graph-up-arrow text-success me-2"></i>Trending Up
                    </h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush bg-transparent">
                        @forelse($trendingUp as $item)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3 bg-transparent border-opacity-10">
                                <div>
                                    <div class="fw-bold text-dark">{{ $item['product']->name }}</div>
                                    <small class="text-muted">High demand predicted</small>
                                </div>
                                <span class="badge bg-success bg-opacity-10 text-success fw-bold p-2 px-3 rounded-pill">
                                    <i class="bi bi-arrow-up"></i> +{{ number_format($item['trend'], 1) }}%
                                </span>
                            </li>
                        @empty
                            <li class="list-group-item text-center py-5 bg-transparent">
                                <i class="bi bi-graph-down text-muted fs-2 mb-2 d-block"></i>
                                <span class="text-muted">No upward trends detected</span>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <!-- Slow Moving Items -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100 glass-card">
                <div class="card-header bg-white bg-opacity-50 border-0 py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-hourglass text-warning me-2"></i>Slow-Moving Items
                    </h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush bg-transparent">
                        @forelse($slowMoving as $item)
                            <li class="list-group-item px-4 py-3 bg-transparent border-opacity-10">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-dark">{{ $item->name }}</div>
                                        <small class="text-muted">{{ $item->quantity_in_stock }} in stock</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-warning rounded-pill px-3 action-btn"
                                        data-action="promotion"
                                        data-product-id="{{ $item->id }}"
                                        data-product-name="{{ $item->name }}">
                                        <i class="bi bi-megaphone me-1"></i>Promote
                                    </button>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center py-5 bg-transparent">
                                <i class="bi bi-check-circle text-success fs-2 mb-2 d-block"></i>
                                <span class="text-muted">All items moving well</span>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <!-- Waste Risk Management -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100 glass-card">
                <div class="card-header bg-white bg-opacity-50 border-0 py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-exclamation-triangle text-danger me-2"></i>Waste Risks
                    </h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush bg-transparent">
                        @forelse($wasteRisks as $item)
                            <li class="list-group-item px-4 py-3 bg-transparent border-opacity-10">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark">{{ $item['product']->name }}</div>
                                        <small class="text-danger">
                                            <i class="bi bi-calendar-x"></i>
                                            {{ $item['risk']['days_until_expiry'] }} days to expiry
                                        </small>
                                    </div>
                                    <span class="badge bg-{{ $item['risk']['risk_level'] === 'critical' ? 'danger' : ($item['risk']['risk_level'] === 'high' ? 'warning' : 'info') }} bg-opacity-10 text-{{ $item['risk']['risk_level'] === 'critical' ? 'danger' : ($item['risk']['risk_level'] === 'high' ? 'warning' : 'info') }} rounded-pill px-3">
                                        {{ strtoupper($item['risk']['risk_level']) }}
                                    </span>
                                </div>
                                <div class="p-3 bg-danger bg-opacity-5 rounded-3 border border-danger border-opacity-10">
                                    <small class="text-danger d-block fw-bold mb-1"><i class="bi bi-box me-1"></i>{{ $item['risk']['units_at_risk'] }} units at risk</small>
                                    <small class="text-muted">{{ $item['risk']['action'] }}</small>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center py-5 bg-transparent">
                                <i class="bi bi-shield-check text-success fs-2 mb-2 d-block"></i>
                                <span class="text-muted">No waste risks detected</span>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>



    <!-- Pricing Health Analysis Link -->
    <div class="row mb-4">
        <div class="col-12">
            <a href="{{ route('ai.pricing-health') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm glass-card hover-lift" style="background: linear-gradient(135deg, rgba(16,185,129,0.08) 0%, rgba(245,158,11,0.08) 100%) !important;">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="rounded-4 p-3 shadow-sm text-white" style="background: linear-gradient(135deg, #10b981 0%, #f59e0b 100%);">
                                    <i class="bi bi-currency-dollar fs-2"></i>
                                </div>
                            </div>
                            <div class="col">
                                <h5 class="fw-bold mb-1 text-dark font-outfit">Pricing Health Analysis</h5>
                                <p class="mb-0 text-muted small">Identify overpriced and underpriced products based on sales velocity, margin, and demand trends.</p>
                            </div>
                            <div class="col-auto">
                                <span class="badge rounded-pill px-4 py-2 text-white fw-semibold" style="background: linear-gradient(135deg, #10b981 0%, #f59e0b 100%);">
                                    <i class="bi bi-arrow-right me-1"></i> View Analysis
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <!-- Product Bundle Suggestions -->
    @if(count($bundleSuggestions) > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 glass-card overflow-hidden">
                <div class="card-header bg-white bg-opacity-50 border-0 py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-box2-heart text-info me-2"></i>AI Bundle Suggestions
                        <span class="badge bg-info bg-opacity-10 text-info ms-2">Boost revenue 15-25%</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Bundle Components</th>
                                    <th class="d-none d-sm-table-cell">Bought Together</th>
                                    <th class="d-none d-md-table-cell">Individual Price</th>
                                    <th>Bundle Price</th>
                                    <th class="d-none d-md-table-cell">Savings</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bundleSuggestions as $bundle)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark">{{ $bundle['product1'] }}</div>
                                            <div class="text-muted small"><i class="bi bi-plus-sm"></i> {{ $bundle['product2'] }}</div>
                                        </td>
                                        <td class="d-none d-sm-table-cell">
                                            <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3">{{ $bundle['frequency'] }}×</span>
                                        </td>
                                        <td class="d-none d-md-table-cell text-muted">KSh {{ number_format($bundle['individual_total'], 2) }}</td>
                                        <td>
                                            <strong class="text-success">KSh {{ number_format($bundle['suggested_bundle_price'], 2) }}</strong>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">
                                                <i class="bi bi-piggy-bank me-1"></i>KSh {{ number_format($bundle['discount_amount'], 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-info text-white rounded-pill px-4 shadow-sm action-btn"
                                                data-action="bundle"
                                                data-product1="{{ $bundle['product1'] }}"
                                                data-product2="{{ $bundle['product2'] }}">
                                                <i class="bi bi-plus-circle me-1"></i>Create Bundle
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- AI Insights & Tips -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 glass-card" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);">
                <div class="card-body p-4">
                    <div class="d-flex">
                        <div class="me-3">
                            <div class="bg-primary text-white rounded-4 p-3 shadow-sm">
                                <i class="bi bi-lightbulb fs-2"></i>
                            </div>
                        </div>
                        <div>
                            <h5 class="fw-bold text-primary mb-2">AI Optimization Tip</h5>
                            <p class="mb-0 text-muted lh-base">Products with upward trends should have safety stock increased by 20% to prevent stockouts during demand spikes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 glass-card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%);">
                <div class="card-body p-4">
                    <div class="d-flex">
                        <div class="me-3">
                            <div class="bg-success text-white rounded-4 p-3 shadow-sm">
                                <i class="bi bi-graph-up fs-2"></i>
                            </div>
                        </div>
                        <div>
                            <h5 class="fw-bold text-success mb-2">Revenue Opportunity</h5>
                            <p class="mb-0 text-muted lh-base">Implementing AI-suggested bundles and dynamic pricing can increase overall revenue by 18-30% based on current data.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// ── Toast helper ────────────────────────────────────────────────────────────
function showToast(message, type = 'success') {
    const toastEl = document.getElementById('actionToast');
    const toastMsg = document.getElementById('toastMessage');
    toastEl.className = `toast align-items-center border-0 shadow-lg text-white bg-${type}`;
    toastMsg.textContent = message;
    bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4000 }).show();
}

// ── Modal confirm helper ─────────────────────────────────────────────────────
function showConfirm({ title, body, btnClass, btnLabel, onConfirm }) {
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    document.getElementById('confirmModalTitle').textContent = title;
    document.getElementById('confirmModalBody').textContent = body;
    const btn = document.getElementById('confirmModalBtn');
    btn.className = `btn rounded-pill px-4 fw-semibold btn-${btnClass}`;
    btn.textContent = btnLabel;
    const clone = btn.cloneNode(true);
    btn.parentNode.replaceChild(clone, btn);
    clone.addEventListener('click', () => { modal.hide(); onConfirm(); });
    modal.show();
}

// ── Button loading state ─────────────────────────────────────────────────────
function setLoading(btn, loading) {
    btn.querySelector('.btn-label').classList.toggle('d-none', loading);
    btn.querySelector('.btn-spinner').classList.toggle('d-none', !loading);
    btn.disabled = loading;
}

// ── Reorder ──────────────────────────────────────────────────────────────────
function executeReorder(btn) {
    const { productId, qty, productName } = btn.dataset;
    showConfirm({
        title: 'Confirm Auto-Reorder',
        body: `Create a purchase order for ${qty} units of "${productName}"?`,
        btnClass: 'danger',
        btnLabel: `Reorder ${qty} units`,
        onConfirm() {
            setLoading(btn, true);
            fetch('/api/ai/execute-recommendation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ action_type: 'reorder', product_id: productId, parameters: { quantity: qty } })
            })
            .then(r => r.json())
            .then(data => {
                showToast(data.message || 'Reorder placed successfully.', 'success');
                btn.closest('tr').style.opacity = '0.4';
                btn.disabled = true;
            })
            .catch(() => {
                showToast('Something went wrong. Please try again.', 'danger');
                setLoading(btn, false);
            });
        }
    });
}

// ── Pricing ──────────────────────────────────────────────────────────────────
function applyPricing(btn) {
    const { productId, newPrice, productName, changePct } = btn.dataset;
    showConfirm({
        title: 'Confirm Price Change',
        body: `Update "${productName}" price to KSh ${parseFloat(newPrice).toLocaleString('en-KE', {minimumFractionDigits:2})} (${changePct}%)?`,
        btnClass: 'success',
        btnLabel: 'Apply New Price',
        onConfirm() {
            setLoading(btn, true);
            fetch('/api/ai/execute-recommendation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ action_type: 'price_change', product_id: productId, parameters: { new_price: newPrice } })
            })
            .then(r => r.json())
            .then(data => {
                showToast(data.message || 'Price updated successfully.', 'success');
                btn.closest('tr').style.opacity = '0.4';
                btn.disabled = true;
            })
            .catch(() => {
                showToast('Something went wrong. Please try again.', 'danger');
                setLoading(btn, false);
            });
        }
    });
}

// ── Bundle ───────────────────────────────────────────────────────────────────
function createBundle(btn) {
    showToast(`Bundle feature coming soon: "${btn.dataset.product1}" + "${btn.dataset.product2}"`, 'info');
}

// ── Promotion ────────────────────────────────────────────────────────────────
function createPromotion(btn) {
    showToast(`Promotion feature coming soon for "${btn.dataset.productName}".`, 'info');
}

// ── Wire up all action buttons via event delegation ──────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.action-btn');
    if (!btn) return;
    const action = btn.dataset.action;
    if (action === 'reorder')   executeReorder(btn);
    if (action === 'pricing')   applyPricing(btn);
    if (action === 'bundle')    createBundle(btn);
    if (action === 'promotion') createPromotion(btn);
});
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

.font-outfit { font-family: 'Outfit', sans-serif; }

.glass-card {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
}

.backdrop-blur { backdrop-filter: blur(5px); }

.hover-lift { transition: transform 0.25s ease, box-shadow 0.25s ease; }
.hover-lift:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.1) !important;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
}

.text-gradient-danger {
    background: linear-gradient(135deg, #ef4444 0%, #991b1b 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.text-gradient-success {
    background: linear-gradient(135deg, #10b981 0%, #065f46 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.text-gradient-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #92400e 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.text-gradient-info {
    background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
h1, h2, h3, h4, h5, h6 { font-family: 'Outfit', sans-serif; }

.table > :not(caption) > * > * { padding-top: 0.85rem; padding-bottom: 0.85rem; }

.opacity-60 { opacity: 0.6; }
</style>
@endsection