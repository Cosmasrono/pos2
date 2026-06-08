@extends('layouts.app')

@section('title', 'Pricing Health Analysis')
@section('page-title', 'Pricing Health Analysis')

@section('content')
<div class="container-fluid px-4 py-4">

    <!-- Header & AI Summary -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card text-white shadow-lg border-0 overflow-hidden glass-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <h3 class="fw-bold mb-1"><i class="bi bi-currency-dollar me-2"></i>Pricing Health & Kenyan Market Analysis</h3>
                            <p class="mb-0 opacity-75">AI-powered price comparison against typical Kenyan market rates</p>
                            <div class="mt-3 p-3 rounded bg-white bg-opacity-10 border border-white border-opacity-20">
                                <i class="bi bi-stars me-2"></i><strong>AI Strategy:</strong> {{ $ai_summary }}
                            </div>
                        </div>
                        <div class="col-md-3 text-md-end mt-3 mt-md-0">
                            <div class="badge bg-white text-success px-4 py-3 rounded-pill shadow-sm fs-6">
                                <i class="bi bi-check-circle me-1"></i> {{ $summary['total'] }} Products Analysed
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row mb-4 text-center">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm glass-card mb-3">
                <div class="card-body">
                    <h2 class="fw-bold text-danger mb-0">{{ $summary['overpriced_count'] }}</h2>
                    <small class="text-muted text-uppercase fw-semibold">Overpriced Items</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm glass-card mb-3">
                <div class="card-body">
                    <h2 class="fw-bold text-warning mb-0">{{ $summary['underpriced_count'] }}</h2>
                    <small class="text-muted text-uppercase fw-semibold">Underpriced Items</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm glass-card mb-3">
                <div class="card-body">
                    <h2 class="fw-bold text-success mb-0">{{ $summary['fair_count'] }}</h2>
                    <small class="text-muted text-uppercase fw-semibold">Market Fair Prices</small>
                </div>
            </div>
        </div>
    </div>

    @if(count($overpriced) > 0)
    <!-- Overpriced Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 glass-card">
                <div class="card-header bg-white bg-opacity-50 border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold font-outfit text-danger">
                        <i class="bi bi-arrow-up-circle-fill me-2"></i>Overpriced Products
                        <small class="text-muted fw-normal ms-2">(Higher than Kenyan Market Average)</small>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light bg-opacity-50">
                                <tr>
                                    <th class="ps-4">Product</th>
                                    <th>Your Price</th>
                                    <th>Market Price</th>
                                    <th>Difference</th>
                                    <th>Gap %</th>
                                    <th>AI Insight</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($overpriced as $item)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $item['product']->name }}</td>
                                    <td>KSh {{ number_format($item['current_price'], 0) }}</td>
                                    <td class="text-muted">KSh {{ number_format($item['market_price'], 0) }}</td>
                                    <td class="text-danger fw-bold">+KSh {{ number_format($item['difference'], 0) }}</td>
                                    <td><span class="badge bg-danger bg-opacity-10 text-danger">+{{ $item['difference_pct'] }}%</span></td>
                                    <td class="small text-muted" style="max-width: 300px;">{{ $item['reason'] }}</td>
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

    @if(count($underpriced) > 0)
    <!-- Underpriced Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 glass-card">
                <div class="card-header bg-white bg-opacity-50 border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold font-outfit text-warning">
                        <i class="bi bi-arrow-down-circle-fill me-2"></i>Underpriced Products
                        <small class="text-muted fw-normal ms-2">(Potential Profit Left on Table)</small>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light bg-opacity-50">
                                <tr>
                                    <th class="ps-4">Product</th>
                                    <th>Your Price</th>
                                    <th>Market Price</th>
                                    <th>Difference</th>
                                    <th>Gap %</th>
                                    <th>AI Insight</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($underpriced as $item)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $item['product']->name }}</td>
                                    <td>KSh {{ number_format($item['current_price'], 0) }}</td>
                                    <td class="text-muted">KSh {{ number_format($item['market_price'], 0) }}</td>
                                    <td class="text-warning fw-bold">-KSh {{ number_format(abs($item['difference']), 0) }}</td>
                                    <td><span class="badge bg-warning bg-opacity-10 text-warning">{{ $item['difference_pct'] }}%</span></td>
                                    <td class="small text-muted" style="max-width: 300px;">{{ $item['reason'] }}</td>
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

</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
.font-outfit { font-family: 'Outfit', sans-serif; }
body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
.glass-card {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
}
.hover-lift { transition: all 0.3s ease; }
.hover-lift:hover { transform: translateY(-6px); box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important; }
</style>
@endsection
