@extends('layouts.app')

@section('title', 'AI Analysis: ' . $product->name)
@section('page-title', 'AI Inventory Analysis')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('ai.dashboard') }}">AI Insights</a></li>
                    <li class="breadcrumb-item active text-dark fw-bold">{{ $product->name }}</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 font-outfit">Analysis: {{ $product->name }}</h3>
        </div>
        <div class="badge bg-primary bg-opacity-10 text-primary px-4 py-3 shadow-sm rounded-pill border border-primary border-opacity-10">
            <i class="bi bi-robot me-1"></i> AI Confidence: {{ $demandForecast['confidence_score'] }}%
        </div>
    </div>

    <!-- Product Analytics Grid -->
    <div class="row">
        <!-- Sales History & Forecast -->
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card shadow-sm border-0 h-100 glass-card">
                <div class="card-header bg-white bg-opacity-50 border-0 py-3">
                    <h5 class="mb-0 fw-bold font-outfit">Sales History & 30-Day Forecast</h5>
                </div>
                <div class="card-body">
                    <div style="height: 350px;">
                        <canvas id="salesForecastChart"></canvas>
                    </div>
                </div>
                <div class="card-footer bg-white bg-opacity-50 border-0 py-4">
                    <div class="row text-center">
                        <div class="col-4 border-end">
                            <h6 class="text-muted small fw-bold mb-1">Avg Daily Sales</h6>
                            <h4 class="fw-bold mb-0 text-dark font-outfit">{{ $demandForecast['average_daily_sales'] }}</h4>
                        </div>
                        <div class="col-4 border-end">
                            <h6 class="text-muted small fw-bold mb-1">30-Day Forecast</h6>
                            <h4 class="fw-bold mb-0 text-primary font-outfit">{{ $demandForecast['predicted_qty'] }}</h4>
                        </div>
                        <div class="col-4">
                            <h6 class="text-muted small fw-bold mb-1">Demand Trend</h6>
                            <h4 class="fw-bold mb-0 {{ $demandForecast['trend_percentage'] >= 0 ? 'text-success' : 'text-danger' }} font-outfit">
                                {{ $demandForecast['trend_percentage'] > 0 ? '+' : '' }}{{ $demandForecast['trend_percentage'] }}%
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Optimization -->
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card shadow-sm border-0 h-100 glass-card overflow-hidden">
                <div class="card-header bg-white bg-opacity-50 border-0 py-3">
                    <h5 class="mb-0 fw-bold font-outfit">Stock Optimization</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="icon-shape bg-primary bg-opacity-10 text-primary rounded-circle p-4 me-3 shadow-sm" style="width: 70px; height: 70px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-box fs-2"></i>
                        </div>
                        <div>
                            <h6 class="text-muted small fw-bold mb-0">Current Stock</h6>
                            <h3 class="fw-bold mb-0 text-dark font-outfit">{{ $product->quantity_in_stock }} <small class="fs-6 opacity-50">{{ $product->unit ?? 'Units' }}</small></h3>
                        </div>
                    </div>

                    <div class="stock-levels-box bg-light bg-opacity-50 rounded-4 p-4 mb-4 border border-white border-opacity-50">
                        <div class="d-flex justify-content-between mb-3 align-items-center">
                            <span class="text-muted small fw-bold">Optimal Reorder Point:</span>
                            <span class="badge bg-dark bg-opacity-10 text-dark rounded-pill px-3">{{ $reorderInfo['reorder_point'] }} units</span>
                        </div>
                        <div class="progress mb-2 m-0 p-0" style="height: 12px; border-radius: 6px;">
                            @php
                                $percent = min(100, ($product->quantity_in_stock / ($reorderInfo['reorder_point'] * 2 ?: 1)) * 100);
                                $color = $reorderInfo['urgency'] === 'high' ? 'bg-danger' : ($reorderInfo['urgency'] === 'medium' ? 'bg-warning' : 'bg-success');
                            @endphp
                            <div class="progress-bar {{ $color }} shadow-sm" style="width: {{ $percent }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted fw-bold">Inventory Health</small>
                            <small class="fw-bold {{ $color === 'bg-danger' ? 'text-danger' : 'text-success' }}">
                                {{ $reorderInfo['days_to_stockout'] }} Days Remaining
                            </small>
                        </div>
                    </div>

                    @if($reorderInfo['needs_reorder'])
                        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-0 p-3 rounded-4 bg-warning bg-opacity-10">
                            <i class="bi bi-exclamation-octagon fs-3 text-warning me-3"></i>
                            <div>
                                <small class="d-block fw-bold text-dark">Action Recommended</small>
                                <p class="mb-0 small text-muted">Reorder <strong>{{ $reorderInfo['recommended_qty'] }} units</strong> now to ensure continuous availability.</p>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-0 p-3 rounded-4 bg-success bg-opacity-10">
                            <i class="bi bi-check-circle fs-3 text-success me-3"></i>
                            <div>
                                <small class="d-block fw-bold text-dark">Stock Level Healthy</small>
                                <p class="mb-0 small text-muted">Current inventory exceeds safety requirements for the next cycle.</p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Gemini Smart Insight Card -->
                <div class="card-body p-4 pt-0">
                    <div class="p-4 rounded-4 shadow-sm border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                        <div class="text-white">
                            <h5 class="fw-bold mb-3 d-flex align-items-center font-outfit">
                                <i class="bi bi-stars me-2"></i> Gemini Smart Strategy
                            </h5>
                            <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(5px); border: 1px solid rgba(255, 255, 255, 0.1);">
                                <p class="mb-0 fs-6 italic line-height-lg font-outfit" style="font-style: italic;">
                                    "{{ $smartInsight }}"
                                </p>
                            </div>
                            <div class="mt-3 small opacity-75">
                                <i class="bi bi-info-circle me-1"></i> AI analysis based on KSh pricing models.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesForecastChart').getContext('2d');
    
    // Prepare Data
    const labels = @json($salesHistory->pluck('date'));
    const data = @json($salesHistory->pluck('total_qty'));
    
    // Forecast Data
    const lastDate = labels[labels.length - 1] ? new Date(labels[labels.length - 1]) : new Date();
    const forecastLabels = [];
    const forecastData = [];
    
    // Connect history to forecast
    forecastLabels.push(labels[labels.length - 1]);
    forecastData.push(data[data.length - 1]);
    
    const avgDailySales = {{ $demandForecast['average_daily_sales'] }};
    for(let i = 1; i <= 7; i++) {
        const nextDate = new Date(lastDate);
        nextDate.setDate(nextDate.getDate() + i);
        forecastLabels.push(nextDate.toISOString().split('T')[0]);
        forecastData.push(avgDailySales);
    }

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [...labels, ...forecastLabels.slice(1)],
            datasets: [
                {
                    label: 'Actual Sales',
                    data: data,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                },
                {
                    label: 'AI Forecast',
                    data: [...Array(data.length - 1).fill(null), ...forecastData],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.05)',
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            family: 'Outfit',
                            size: 13,
                            weight: '500'
                        }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#1f2937',
                    bodyColor: '#4b5563',
                    borderColor: 'rgba(0,0,0,0.05)',
                    borderWidth: 1,
                    padding: 12,
                    boxPadding: 4,
                    titleFont: {
                        family: 'Outfit',
                        weight: '700'
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.03)'
                    },
                    ticks: {
                        font: {
                            family: 'Inter'
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'Inter'
                        }
                    }
                }
            }
        }
    });
});
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

.font-outfit {
    font-family: 'Outfit', sans-serif;
}

body {
    background-color: #f8fafc;
    font-family: 'Inter', sans-serif;
}

.glass-card {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
}

.icon-shape {
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endsection
