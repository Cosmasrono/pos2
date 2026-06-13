@extends('layouts.app')
@section('title', 'AI Promotion Suggestions')
@section('page-title', 'AI Promotion Suggestions')

@section('content')
<div class="container-fluid px-4">

    @if(!empty($ai_summary))
    <div class="alert alert-info d-flex gap-2 mb-4">
        <span style="font-size:1.3rem;">🤖</span>
        <div>{{ $ai_summary }}</div>
    </div>
    @endif

    @if(empty($promotions))
        <div class="card border-0 shadow-sm"><div class="card-body text-center py-5 text-success">
            <p class="mt-3 fw-bold">All products are moving well. No promotions needed right now.</p>
        </div></div>
    @else
    <div class="row g-4">
        @foreach($promotions as $promo)
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-warning text-dark fw-bold d-flex justify-content-between">
                    <span>{{ $promo['label'] ?? strtoupper($promo['type']) }}</span>
                    <span>{{ $promo['type'] }}</span>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">{{ $promo['product'] }}</h6>
                    <div class="small text-muted mb-1">Current Price: KSh {{ number_format($promo['selling_price'], 2) }}</div>
                    <div class="small text-muted mb-2">Stock: {{ $promo['stock'] }} units</div>
                    <div class="alert alert-warning py-2 small mb-3">{{ $promo['reason'] ?? '' }}</div>
                    <a href="{{ route('promotions.create') }}" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-plus-circle me-1"></i> Create This Promotion
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
