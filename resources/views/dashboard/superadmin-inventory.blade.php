@extends('layouts.app')

@section('title', 'Inventory Overview')
@section('page-title', 'Inventory Overview')

@push('styles')
<style>
/* ── Page Header ─────────────────────────────────────────── */
.inv-hero {
    background: linear-gradient(135deg, hsl(243,75%,59%) 0%, hsl(262,83%,58%) 100%);
    border-radius: 20px;
    color: white;
    padding: 28px 32px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
}
.inv-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
}
.inv-hero::after {
    content: '';
    position: absolute;
    bottom: -60px; right: 60px;
    width: 140px; height: 140px;
    border-radius: 50%;
    background: rgba(255,255,255,.05);
}
.inv-hero .hero-icon {
    width: 56px; height: 56px;
    border-radius: 16px;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 26px;
    backdrop-filter: blur(6px);
}
.inv-hero .breadcrumb-item, .inv-hero .breadcrumb-item a {
    color: rgba(255,255,255,.75);
    font-size: .85rem;
    text-decoration: none;
}
.inv-hero .breadcrumb-item.active { color: white; }
.inv-hero .breadcrumb-divider { color: rgba(255,255,255,.5); }

/* ── KPI Cards ───────────────────────────────────────────── */
.kpi-card {
    border-radius: 18px;
    border: none;
    padding: 22px 24px;
    position: relative;
    overflow: hidden;
    transition: transform .25s ease, box-shadow .25s ease;
    cursor: default;
}
.kpi-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,.12) !important; }
.kpi-card .kpi-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.kpi-card .kpi-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; opacity: .75; margin-bottom: 4px; }
.kpi-card .kpi-value { font-size: 1.75rem; font-weight: 800; line-height: 1.1; }
.kpi-card .kpi-sub { font-size: .78rem; opacity: .7; margin-top: 6px; }
.kpi-card .kpi-action { font-size: .78rem; font-weight: 600; text-decoration: none; opacity: .85; }
.kpi-card .kpi-action:hover { opacity: 1; }

/* ── Section Headers ─────────────────────────────────────── */
.section-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 14px;
}
.section-header h6 {
    font-size: .9rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .05em; color: var(--text-muted);
    display: flex; align-items: center; gap: 8px; margin: 0;
}
.section-header h6 i { font-size: 1rem; }


/* ── Alert Banner ────────────────────────────────────────── */
.alert-banner {
    border-radius: 14px;
    border: none;
    padding: 14px 20px;
    display: flex; align-items: center; gap: 14px;
    animation: slideDown .4s ease;
}
@keyframes slideDown { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }

/* ── Progress Bar ────────────────────────────────────────── */
.stock-progress { height: 6px; border-radius: 4px; background: #e9ecef; overflow: hidden; }
.stock-progress-bar { height: 100%; border-radius: 4px; background: var(--gradient-primary); transition: width .6s ease; }

/* ── Empty State ─────────────────────────────────────────── */
.empty-state { padding: 48px 24px; text-align: center; }
.empty-state .empty-icon { font-size: 3rem; margin-bottom: 12px; opacity: .35; }
.empty-state p { color: var(--text-muted); font-size: .9rem; margin: 0; }

/* ── Table tweaks ────────────────────────────────────────── */
.inv-table thead th { background: hsl(210,40%,98%); font-size: .72rem; }
.inv-table tbody tr { transition: background .15s; }
.inv-table tbody tr:hover { background: hsl(243,75%,98%); }
.qty-pill {
    display: inline-block;
    min-width: 44px; text-align: center;
    padding: 3px 10px;
    border-radius: 20px;
    font-weight: 700; font-size: .8rem;
}

/* ── Pulse for critical ──────────────────────────────────── */
@keyframes pulse-red { 0%,100%{opacity:1} 50%{opacity:.6} }
.pulse-red { animation: pulse-red 1.4s ease infinite; }
</style>
@endpush

@section('content')

{{-- ══════════════════════════════════════════════════════════
     HERO HEADER
══════════════════════════════════════════════════════════ --}}
<div class="inv-hero shadow-lg">
    <div class="d-flex align-items-center gap-3 mb-3">
        <div class="hero-icon">
            <i class="bi bi-bar-chart-steps"></i>
        </div>
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Inventory Overview</li>
                </ol>
            </nav>
            <h4 class="mb-0 fw-bold">Inventory Command Center</h4>
        </div>
        <div class="ms-auto d-none d-md-block text-end">
            <div class="small opacity-75">Last updated</div>
            <div class="fw-semibold">{{ now()->format('d M Y, H:i') }}</div>
        </div>
    </div>
    <p class="mb-0 opacity-75" style="max-width:520px;">
        Monitor stock levels, track movements, and manage inventory across all branches from one place.
    </p>
</div>

{{-- ══════════════════════════════════════════════════════════
     CRITICAL OUT-OF-STOCK BANNER
══════════════════════════════════════════════════════════ --}}
@php $outOfStock = $lowStockItems->where('quantity_in_stock', 0); @endphp
@if($outOfStock->count() > 0)
<div class="alert-banner bg-danger bg-opacity-10 border border-danger border-opacity-25 mb-4">
    <i class="bi bi-exclamation-octagon-fill text-danger fs-4 pulse-red"></i>
    <div class="flex-grow-1">
        <strong class="text-danger">{{ $outOfStock->count() }} product{{ $outOfStock->count() > 1 ? 's' : '' }} completely out of stock!</strong>
        <div class="text-muted small mt-1">
            {{ $outOfStock->take(3)->map(fn($i) => $i->product->name ?? 'N/A')->join(', ') }}{{ $outOfStock->count() > 3 ? ' and more…' : '' }}
        </div>
    </div>
    <a href="#low-stock-section" class="btn btn-sm btn-danger rounded-pill px-3">View Alerts</a>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════
     KPI CARDS
══════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">

    {{-- Active Products --}}
    <div class="col-6 col-lg-3">
        <div class="kpi-card shadow-sm" style="background:linear-gradient(135deg,hsl(243,75%,97%) 0%,hsl(262,83%,96%) 100%);">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="kpi-icon" style="background:linear-gradient(135deg,rgba(99,102,241,.15),rgba(139,92,246,.08));">
                    <i class="bi bi-box-seam" style="background:var(--gradient-primary);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"></i>
                </div>
                <span class="badge rounded-pill" style="background:rgba(99,102,241,.12);color:hsl(243,75%,50%);font-size:.7rem;">SKUs</span>
            </div>
            <div class="kpi-label" style="color:hsl(243,75%,50%);">Active Products</div>
            <div class="kpi-value" style="color:hsl(243,75%,35%);">{{ number_format($totalActiveProducts) }}</div>
            <div class="mt-3">
                <a href="{{ route('products.index') }}" class="kpi-action" style="color:hsl(243,75%,50%);">
                    Manage Products <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Total Stock Units --}}
    <div class="col-6 col-lg-3">
        <div class="kpi-card shadow-sm" style="background:linear-gradient(135deg,hsl(199,89%,97%) 0%,hsl(217,91%,96%) 100%);">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="kpi-icon" style="background:linear-gradient(135deg,rgba(14,165,233,.15),rgba(56,189,248,.08));">
                    <i class="bi bi-layers" style="background:var(--gradient-info);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"></i>
                </div>
                <span class="badge rounded-pill" style="background:rgba(14,165,233,.12);color:hsl(199,89%,35%);font-size:.7rem;">Units</span>
            </div>
            <div class="kpi-label" style="color:hsl(199,89%,40%);">Total Stock</div>
            <div class="kpi-value" style="color:hsl(199,89%,28%);">{{ number_format($totalStockUnits) }}</div>
            <div class="kpi-sub" style="color:hsl(199,89%,45%);">Across {{ $branches->count() }} branch{{ $branches->count() != 1 ? 'es' : '' }}</div>
        </div>
    </div>

    {{-- Cost Value --}}
    <div class="col-6 col-lg-3">
        <div class="kpi-card shadow-sm" style="background:linear-gradient(135deg,hsl(38,92%,97%) 0%,hsl(45,93%,96%) 100%);">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="kpi-icon" style="background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(251,191,36,.08));">
                    <i class="bi bi-wallet2" style="background:var(--gradient-warning);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"></i>
                </div>
                <span class="badge rounded-pill" style="background:rgba(245,158,11,.12);color:hsl(38,92%,35%);font-size:.7rem;">Cost</span>
            </div>
            <div class="kpi-label" style="color:hsl(38,92%,40%);">Inventory Cost</div>
            <div class="kpi-value" style="color:hsl(38,92%,28%);font-size:1.35rem;">KES {{ number_format($inventoryCostValue, 0) }}</div>
            <div class="kpi-sub" style="color:hsl(38,92%,45%);">Total at cost price</div>
        </div>
    </div>

    {{-- Selling Value --}}
    <div class="col-6 col-lg-3">
        <div class="kpi-card shadow-sm" style="background:linear-gradient(135deg,hsl(142,71%,97%) 0%,hsl(158,64%,96%) 100%);">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="kpi-icon" style="background:linear-gradient(135deg,rgba(16,185,129,.15),rgba(52,211,153,.08));">
                    <i class="bi bi-graph-up-arrow" style="background:var(--gradient-success);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"></i>
                </div>
                @php $potentialProfit = $inventorySellingValue - $inventoryCostValue; @endphp
                <span class="badge rounded-pill" style="background:rgba(16,185,129,.12);color:hsl(142,71%,30%);font-size:.7rem;">
                    +KES {{ number_format($potentialProfit, 0) }} margin
                </span>
            </div>
            <div class="kpi-label" style="color:hsl(142,71%,35%);">Selling Value</div>
            <div class="kpi-value" style="color:hsl(142,71%,28%);font-size:1.35rem;">KES {{ number_format($inventorySellingValue, 0) }}</div>
            <div class="kpi-sub" style="color:hsl(142,71%,40%);">If all stock sold today</div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     TODAY'S BRANCH SALES
══════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">

    {{-- Today's Branch Sales --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="section-header">
                    <h6><i class="bi bi-cash-stack text-success"></i> Today's Branch Sales</h6>
                    <span class="badge bg-light text-muted border">{{ now()->format('d M Y') }}</span>
                </div>
                @if($branchSalesToday->sum('sales_count') > 0)
                <div class="table-responsive">
                    <table class="table inv-table mb-0">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th class="text-center">Transactions</th>
                                <th class="text-end">Revenue</th>
                                <th class="text-end" style="width:120px;">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalSalesAmt = $branchSalesToday->sum('total_amount'); @endphp
                            @foreach($branchSalesToday as $bs)
                                @php $sharePct = $totalSalesAmt > 0 ? round(($bs['total_amount'] / $totalSalesAmt) * 100) : 0; @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $bs['name'] }}</td>
                                    <td class="text-center">
                                        <span class="qty-pill" style="background:rgba(14,165,233,.1);color:hsl(199,89%,35%);">{{ $bs['sales_count'] }}</span>
                                    </td>
                                    <td class="text-end fw-bold text-success">KES {{ number_format($bs['total_amount'], 2) }}</td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center gap-2 justify-content-end">
                                            <div class="stock-progress flex-grow-1" style="max-width:60px;">
                                                <div class="stock-progress-bar" style="width:{{ $sharePct }}%;background:var(--gradient-success);"></div>
                                            </div>
                                            <small class="text-muted" style="min-width:28px;">{{ $sharePct }}%</small>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background:hsl(142,71%,97%);">
                                <td class="fw-bold">Total</td>
                                <td class="text-center fw-bold">{{ $branchSalesToday->sum('sales_count') }}</td>
                                <td class="text-end fw-bold text-success">KES {{ number_format($totalSalesAmt, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-calendar-x"></i></div>
                    <p>No sales recorded today yet.<br>Sales will appear here as they come in.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     BRANCH STOCK OVERVIEW
══════════════════════════════════════════════════════════ --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="section-header">
            <h6><i class="bi bi-building text-primary"></i> Branch Stock Overview</h6>
            <a href="{{ route('branches.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                <i class="bi bi-gear me-1"></i> Manage
            </a>
        </div>
        @if($branches->count() > 0)
        <div class="table-responsive">
            <table class="table inv-table mb-0">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th class="text-center">Products</th>
                        <th class="text-center">Units</th>
                        <th class="text-end">Cost Value</th>
                        <th class="text-end">Selling Value</th>
                        <th style="width:160px;">Stock Share</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($branches as $branch)
                        @php
                            $hasLowStock = $lowStockItems->where('branch_id', $branch['id'])->count();
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $branch['name'] }}</div>
                                @if($hasLowStock)
                                    <small class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $hasLowStock }} low stock</small>
                                @else
                                    <small class="text-success"><i class="bi bi-check-circle-fill me-1"></i>All stocked</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="qty-pill" style="background:rgba(99,102,241,.08);color:hsl(243,75%,50%);">{{ number_format($branch['product_count']) }}</span>
                            </td>
                            <td class="text-center fw-bold">{{ number_format($branch['total_units']) }}</td>
                            <td class="text-end text-muted">{{ number_format($branch['cost_value'], 0) }}</td>
                            <td class="text-end fw-semibold text-success">{{ number_format($branch['selling_value'], 0) }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="stock-progress flex-grow-1">
                                        <div class="stock-progress-bar" style="width:{{ $branch['pct_of_total'] }}%;"></div>
                                    </div>
                                    <small class="text-muted fw-semibold" style="min-width:36px;">{{ $branch['pct_of_total'] }}%</small>
                                </div>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:hsl(243,75%,98%);font-weight:700;">
                        <td>All Branches</td>
                        <td class="text-center">{{ number_format($branches->sum('product_count')) }}</td>
                        <td class="text-center">{{ number_format($branches->sum('total_units')) }}</td>
                        <td class="text-end text-muted">{{ number_format($branches->sum('cost_value'), 0) }}</td>
                        <td class="text-end text-success">{{ number_format($branches->sum('selling_value'), 0) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @else
        <div class="empty-state">
            <div class="empty-icon"><i class="bi bi-building-slash"></i></div>
            <p>No active branches found.<br><a href="{{ route('branches.index') }}">Create a branch</a> to get started.</p>
        </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     LOW STOCK ALERTS
══════════════════════════════════════════════════════════ --}}
<div class="card border-0 shadow-sm mb-4" id="low-stock-section">
    <div class="card-body p-3">
        <div class="section-header">
            <h6>
                <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                Low Stock Alerts
                @if($lowStockItems->count() > 0)
                    <span class="badge bg-danger rounded-pill ms-1">{{ $lowStockItems->count() }}</span>
                @endif
            </h6>
            <a href="{{ route('products.index', ['filter' => 'active']) }}" class="btn btn-sm btn-outline-danger rounded-pill px-3">All Products</a>
        </div>

        @if($lowStockItems->count() > 0)
        <div class="table-responsive">
            <table class="table inv-table mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Branch</th>
                        <th class="text-center">Current Qty</th>
                        <th class="text-center">Reorder Level</th>
                        <th class="text-center">Deficit</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowStockItems as $item)
                        @php
                            $deficit = ($item->product->reorder_level ?? 0) - $item->quantity_in_stock;
                            $isCritical = $item->quantity_in_stock == 0;
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $item->product->name ?? 'N/A' }}</div>
                                <code class="text-muted" style="font-size:.75rem;">{{ $item->product->sku ?? '' }}</code>
                                @if($isCritical)
                                    <span class="badge bg-danger ms-1 pulse-red" style="font-size:.65rem;">OUT OF STOCK</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $item->branch->name ?? 'N/A' }}</span>
                            </td>
                            <td class="text-center">
                                <span class="qty-pill {{ $isCritical ? 'bg-danger text-white' : '' }}"
                                      style="{{ !$isCritical ? 'background:rgba(245,158,11,.12);color:hsl(38,92%,30%);' : '' }}">
                                    {{ $item->quantity_in_stock }}
                                </span>
                            </td>
                            <td class="text-center text-muted">{{ $item->product->reorder_level ?? 0 }}</td>
                            <td class="text-center">
                                <span class="fw-bold text-danger">−{{ $deficit }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('products.show', $item->product_id) }}"
                                   class="btn btn-sm btn-primary rounded-pill px-3">
                                    <i class="bi bi-plus-circle me-1"></i>Restock
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="empty-state">
            <div class="empty-icon text-success"><i class="bi bi-check-circle-fill"></i></div>
            <p class="fw-semibold text-success mb-1">All products are well stocked!</p>
            <p class="text-muted">No items are below their reorder level right now.</p>
        </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     RECENT STOCK MOVEMENTS
══════════════════════════════════════════════════════════ --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="section-header">
            <h6><i class="bi bi-arrow-left-right text-info"></i> Recent Stock Movements</h6>
            <span class="badge bg-light text-muted border">Last 20</span>
        </div>

        @if($recentMovements->count() > 0)
        <div class="table-responsive">
            <table class="table inv-table mb-0">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Product</th>
                        <th>Branch</th>
                        <th class="text-center">Type</th>
                        <th class="text-center">Qty</th>
                        <th>By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentMovements as $mv)
                        @php
                            $typeMap = [
                                'in'         => ['bg-success',  'Stock In'],
                                'out'        => ['bg-danger',   'Stock Out'],
                                'transfer'   => ['bg-info',     'Transfer'],
                                'adjustment' => ['bg-warning',  'Adjustment'],
                                'purchase'   => ['bg-primary',  'Purchase'],
                                'sale'       => ['bg-danger',   'Sale'],
                                'return'     => ['bg-secondary','Return'],
                            ];
                            $tm = $typeMap[$mv->type] ?? ['bg-secondary', ucfirst($mv->type ?? 'N/A')];
                            $isOut = in_array($mv->type, ['out','sale']);
                        @endphp
                        <tr>
                            <td>
                                <div class="small fw-semibold">{{ $mv->created_at?->format('d M') }}</div>
                                <div class="text-muted" style="font-size:.72rem;">{{ $mv->created_at?->format('H:i') }}</div>
                            </td>
                            <td class="fw-semibold">{{ Str::limit($mv->product->name ?? 'N/A', 28) }}</td>
                            <td>
                                <span class="badge bg-light text-dark border" style="font-size:.72rem;">{{ $mv->branch_name ?? 'N/A' }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $tm[0] }}" style="font-size:.72rem;">{{ $tm[1] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold {{ $isOut ? 'text-danger' : 'text-success' }}">
                                    {{ $isOut ? '−' : '+' }}{{ abs($mv->quantity) }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ $mv->user->name ?? 'System' }}</td>
                            <td class="text-muted small">{{ Str::limit($mv->notes ?? '—', 35) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="empty-state">
            <div class="empty-icon"><i class="bi bi-arrow-left-right"></i></div>
            <p>No stock movements recorded yet.<br>Movements will appear here as stock is added, sold, or transferred.</p>
        </div>
        @endif
    </div>
</div>

@endsection
