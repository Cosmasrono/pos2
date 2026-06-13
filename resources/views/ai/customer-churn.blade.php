@extends('layouts.app')
@section('title', 'Win Back Customers')
@section('page-title', 'Win Back Customers')

@section('content')
<div class="container-fluid px-4">

    @if(empty($customers))
        <div class="card border-0 shadow-sm"><div class="card-body text-center py-5 text-success">
            <i class="bi bi-people-fill" style="font-size:3rem;"></i>
            <p class="mt-3 fw-bold">All customers have purchased in the last 30 days. Great retention!</p>
        </div></div>
    @else
    <p class="text-muted mb-4">{{ count($customers) }} customers haven't purchased in 30+ days. Claude has written a personalised WhatsApp message for each.</p>

    <div class="row g-4">
        @foreach($customers as $c)
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="fw-bold">{{ $c['name'] }}</div>
                        <div class="small text-muted">{{ $c['phone'] }} &bull; {{ $c['total_purchases'] }} past purchases &bull; KSh {{ number_format($c['lifetime_value'], 0) }} lifetime</div>
                    </div>
                    <div class="text-end">
                        <div class="badge bg-warning text-dark">{{ $c['days_since'] ?? '??' }}d ago</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="bg-light rounded p-3 mb-3 position-relative">
                        <div class="text-muted small mb-1">Favourite: {{ $c['top_product'] }}</div>
                        <p class="mb-0" id="msg-{{ $c['id'] }}">{{ $c['whatsapp_message'] }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $c['phone']) }}?text={{ urlencode($c['whatsapp_message']) }}"
                           target="_blank" class="btn btn-success btn-sm">
                            <i class="bi bi-whatsapp me-1"></i> Send on WhatsApp
                        </a>
                        <button class="btn btn-outline-secondary btn-sm"
                                onclick="navigator.clipboard.writeText(document.getElementById('msg-{{ $c['id'] }}').innerText)">
                            <i class="bi bi-clipboard me-1"></i> Copy
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
