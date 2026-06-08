@extends('layouts.app')

@section('title', 'Branch Sales Analysis')
@section('page-title', 'Branch Sales Analysis')

@section('content')
<div class="container-fluid px-4 py-4">

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card text-white shadow-lg border-0 overflow-hidden glass-card" style="background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%) !important;">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="fw-bold mb-1"><i class="bi bi-shop me-2"></i>Branch Sales Intelligence</h3>
                            <p class="mb-0 opacity-75">AI-powered comparison of product performance across all branches</p>
                            <div class="mt-3 p-3 rounded bg-white bg-opacity-10 border border-white border-opacity-20">
                                <i class="bi bi-stars me-2"></i><strong>AI Insight:</strong> {{ $aiInsight }}
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="badge bg-white text-primary px-4 py-3 rounded-pill shadow-sm fs-6">
                                <i class="bi bi-building me-1"></i> {{ count($analysis['branch_summary']) }} Branches Analysed
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Branch Revenue Summary Cards -->
    <div class="row mb-4">
        @foreach($analysis['branch_summary'] as $index => $branch)
        @php
            $colors = ['primary', 'success', 'warning', 'info', 'danger'];
            $color = $colors[$index % count($colors)];
        @endphp
        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm h-100 glass-card hover-lift">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-{{ $color }} bg-opacity-10 text-{{ $color }} rounded-circle p-3 me-3" style="width:55px;height:55px;display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-shop fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 font-outfit">{{ $branch['branch_name'] }}</h6>
                            <small class="text-muted">{{ $branch['total_transactions'] }} transactions</small>
                        </div>
                    </div>
                    <h4 class="fw-bold text-{{ $color }} font-outfit mb-1">KSh {{ number_format($branch['total_revenue'], 2) }}</h4>
                    <small class="text-muted">Total Revenue</small>
                    <hr class="my-2 opacity-10">
                    <div class="d-flex justify-content-between small text-muted">
                        <span><i class="bi bi-cash me-1"></i>Cash: <strong>KSh {{ number_format($branch['cash_revenue'], 0) }}</strong></span>
                        <span><i class="bi bi-phone me-1"></i>Mpesa: <strong>KSh {{ number_format($branch['mpesa_revenue'], 0) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Overall Top Products + Revenue Chart -->
    <div class="row mb-4">
        <!-- Overall Top Products -->
        <div class="col-xl-5 mb-4">
            <div class="card shadow-sm border-0 h-100 glass-card">
                <div class="card-header bg-white bg-opacity-50 border-0 py-3">
                    <h5 class="mb-0 fw-bold font-outfit">
                        <i class="bi bi-trophy text-warning me-2"></i>Overall Best Sellers
                        <span class="badge bg-warning bg-opacity-10 text-warning ms-2">All Branches</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush bg-transparent">
                        @forelse($analysis['overall_top'] as $rank => $product)
                        <li class="list-group-item d-flex align-items-center px-4 py-3 bg-transparent border-opacity-10">
                            <div class="me-3">
                                @if($rank === 0)
                                    <span class="badge bg-warning text-dark rounded-circle p-2" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">🥇</span>
                                @elseif($rank === 1)
                                    <span class="badge bg-secondary rounded-circle p-2" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">🥈</span>
                                @elseif($rank === 2)
                                    <span class="badge bg-danger bg-opacity-75 rounded-circle p-2" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">🥉</span>
                                @else
                                    <span class="badge bg-light text-dark rounded-circle fw-bold" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">{{ $rank + 1 }}</span>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark">{{ $product['product_name'] }}</div>
                                <small class="text-muted">KSh {{ number_format($product['total_revenue'], 2) }} revenue</small>
                            </div>
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 fw-bold">
                                {{ number_format($product['total_qty_sold']) }} sold
                            </span>
                        </li>
                        @empty
                        <li class="list-group-item text-center py-4 text-muted bg-transparent">No sales data found</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <!-- Branch Revenue Bar Chart -->
        <div class="col-xl-7 mb-4">
            <div class="card shadow-sm border-0 h-100 glass-card">
                <div class="card-header bg-white bg-opacity-50 border-0 py-3">
                    <h5 class="mb-0 fw-bold font-outfit">
                        <i class="bi bi-bar-chart text-primary me-2"></i>Branch Revenue Comparison
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 300px;">
                        <canvas id="branchRevenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Per Branch Top Products -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 glass-card">
                <div class="card-header bg-white bg-opacity-50 border-0 py-3">
                    <h5 class="mb-0 fw-bold font-outfit">
                        <i class="bi bi-grid text-info me-2"></i>Top Products Per Branch
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @forelse($analysis['by_branch'] as $index => $branch)
                        @php $colors = ['primary','success','warning','info','danger']; $color = $colors[$index % count($colors)]; @endphp
                        <div class="col-xl-4 col-lg-6 mb-4">
                            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid var(--bs-{{ $color }}) !important; background: rgba(255,255,255,0.6);">
                                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                                    <h6 class="fw-bold text-{{ $color }} font-outfit mb-0">
                                        <i class="bi bi-shop me-2"></i>{{ $branch['branch_name'] }}
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush bg-transparent">
                                        @foreach($branch['top_products'] as $rank => $product)
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2 bg-transparent border-opacity-10">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }} me-2 fw-bold" style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;border-radius:50%;">{{ $rank + 1 }}</span>
                                                <div>
                                                    <div class="fw-semibold small text-dark">{{ $product['product_name'] }}</div>
                                                    <small class="text-muted">KSh {{ number_format($product['total_revenue'], 0) }}</small>
                                                </div>
                                            </div>
                                            <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }} rounded-pill px-2 small">
                                                {{ number_format($product['total_qty_sold']) }} sold
                                            </span>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12 text-center text-muted py-4">No branch data found. Ensure sales have a branch_id.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const branchLabels = @json(collect($analysis['branch_summary'])->pluck('branch_name'));
    const branchRevenue = @json(collect($analysis['branch_summary'])->pluck('total_revenue'));
    const branchCash = @json(collect($analysis['branch_summary'])->pluck('cash_revenue'));
    const branchMpesa = @json(collect($analysis['branch_summary'])->pluck('mpesa_revenue'));

    new Chart(document.getElementById('branchRevenueChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: branchLabels,
            datasets: [
                {
                    label: 'Cash (KSh)',
                    data: branchCash,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderRadius: 6,
                },
                {
                    label: 'Mpesa (KSh)',
                    data: branchMpesa,
                    backgroundColor: 'rgba(99, 102, 241, 0.7)',
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { font: { family: 'Outfit', size: 13 }, usePointStyle: true } },
                tooltip: {
                    backgroundColor: 'rgba(255,255,255,0.95)',
                    titleColor: '#1f2937',
                    bodyColor: '#4b5563',
                    borderColor: 'rgba(0,0,0,0.05)',
                    borderWidth: 1,
                    callbacks: {
                        label: ctx => ` KSh ${ctx.parsed.y.toLocaleString()}`
                    }
                }
            },
            scales: {
                x: { stacked: true, grid: { display: false }, ticks: { font: { family: 'Outfit' } } },
                y: { stacked: true, beginAtZero: true, ticks: { callback: v => 'KSh ' + v.toLocaleString(), font: { family: 'Inter' } }, grid: { color: 'rgba(0,0,0,0.04)' } }
            }
        }
    });
});
</script>

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