{{-- resources/views/sales/offline.blade.php --}}
@extends('layouts.app')

@section('title', 'Offline')
@section('page-title', 'Offline Mode')

@section('content')
<div class="text-center py-5">
    <div class="mb-4" style="font-size: 4rem;">📡</div>
    <h3 class="fw-bold text-warning mb-2">You are currently offline</h3>
    <p class="text-muted mb-4">
        Sales made while offline are saved on this device and will sync automatically when you reconnect.
    </p>

    <div id="offlineSalesList" class="mb-4" style="max-width: 500px; margin: 0 auto;"></div>

    <a href="{{ route('sales.create') }}" class="btn btn-primary me-2">
        <i class="bi bi-bag-check me-1"></i> Continue to POS
    </a>
    <button class="btn btn-outline-secondary" onclick="location.reload()">
        <i class="bi bi-arrow-clockwise me-1"></i> Try reconnecting
    </button>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const sales = await OfflinePOS.getAllPending?.() ?? [];
    const list  = document.getElementById('offlineSalesList');

    if (!sales || !sales.length) {
        list.innerHTML = '<p class="text-muted small">No pending offline sales.</p>';
        return;
    }

    list.innerHTML = `
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history me-2 text-warning"></i>
                ${sales.length} Pending Sale(s) — will sync when online
            </div>
            <ul class="list-group list-group-flush">
                ${sales.map(s => `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-receipt me-2 text-muted"></i>
                            ${s.payload.items?.length ?? 0} item(s) &mdash;
                            <strong>KSh ${Number(s.payload.total_amount ?? 0).toLocaleString()}</strong>
                        </span>
                        <small class="text-muted">${new Date(s.saved_at).toLocaleTimeString()}</small>
                    </li>
                `).join('')}
            </ul>
        </div>
    `;
});
</script>
@endpush
