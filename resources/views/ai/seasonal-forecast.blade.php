@extends('layouts.app')
@section('title', 'Seasonal Forecast')
@section('page-title', 'Seasonal Demand Forecast')

@section('content')
<div class="container-fluid px-4">

    <p class="text-muted mb-4">Comparing <strong>{{ $month }}</strong> with <strong>{{ $last_year_month }}</strong></p>

    @if(!empty($ai_summary))
    <div class="alert alert-info d-flex gap-2 mb-4">
        <span style="font-size:1.3rem;">🤖</span>
        <div><strong>Claude's Forecast:</strong> {{ $ai_summary }}</div>
    </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">Top Products — Same Period Last Year</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Last Year Qty</th>
                        <th class="text-center">Projected This Month</th>
                        <th class="text-center">Current Stock</th>
                        <th class="text-center">Stock Gap</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($forecasts as $f)
                    <tr class="{{ $f['needs_attention'] ? 'table-warning' : '' }}">
                        <td class="fw-bold">{{ $f['product_name'] }}</td>
                        <td class="text-center">{{ $f['last_year_qty'] }}</td>
                        <td class="text-center">{{ $f['projected_qty'] }}</td>
                        <td class="text-center">{{ $f['current_stock'] }}</td>
                        <td class="text-center {{ $f['stock_gap'] > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                            {{ $f['stock_gap'] > 0 ? '+' . $f['stock_gap'] . ' needed' : 'OK' }}
                        </td>
                        <td class="text-center">
                            @if($f['needs_attention'])
                                <span class="badge bg-warning text-dark">Order Now</span>
                            @else
                                <span class="badge bg-success">Sufficient</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
