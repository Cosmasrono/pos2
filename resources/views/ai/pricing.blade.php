@extends('layouts.app')

@section('title', 'AI Pricing Optimization')
@section('page-title', 'AI Pricing Optimization')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg bg-gradient-success text-white overflow-hidden glass-card">
                <div class="card-body p-4 position-relative">
                    <h3 class="fw-bold mb-2">Pricing Opportunities</h3>
                    <p class="mb-0 opacity-75">AI-driven price adjustments based on demand trends, stock levels, and historical performance.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 glass-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Product</th>
                                    <th>Current Price</th>
                                    <th>Suggested Price</th>
                                    <th>Change</th>
                                    <th>Reasoning</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recommendations as $item)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold">{{ $item['product']->name }}</div>
                                            <small class="text-muted">Stock: {{ $item['product']->quantity_in_stock }}</small>
                                        </td>
                                        <td><strong>KSh {{ number_format($item['pricing']['current_price'], 2) }}</strong></td>
                                        <td>
                                            <strong class="{{ $item['pricing']['price_change'] > 0 ? 'text-success' : 'text-danger' }}">
                                                KSh {{ number_format($item['pricing']['suggested_price'], 2) }}
                                            </strong>
                                        </td>
                                        <td>
                                            <span class="badge {{ $item['pricing']['price_change'] > 0 ? 'bg-success' : 'bg-danger' }} rounded-pill px-3">
                                                {{ $item['pricing']['price_change'] > 0 ? '+' : '' }}{{ $item['pricing']['price_change_percentage'] }}%
                                            </span>
                                        </td>
                                        <td style="max-width: 300px;">
                                            <small class="text-muted lh-base d-block">{{ $item['pricing']['reason'] }}</small>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-success rounded-pill px-4 shadow-sm" onclick="applyPricing({{ $item['product']->id }}, {{ $item['pricing']['suggested_price'] }})">
                                                Apply New Price
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">No pricing adjustments recommended at this time.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function applyPricing(productId, newPrice) {
    if (confirm(`Update price to KSh ${newPrice}?`)) {
        fetch('{{ route("ai.execute") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                action_type: 'price_change',
                product_id: productId,
                parameters: { new_price: newPrice }
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            location.reload();
        });
    }
}
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

body {
    background-color: #f8fafc;
    font-family: 'Inter', sans-serif;
}

h1, h2, h3, h4, h5, h6 {
    font-family: 'Outfit', sans-serif;
}

.glass-card {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
}

.bg-gradient-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
}
</style>
@endsection

