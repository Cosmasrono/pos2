@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
    :root {
        --primary: #4f46e5;
        --primary-light: rgba(79, 70, 229, 0.1);
        --success: #10b981;
        --success-light: rgba(16, 185, 129, 0.1);
        --info: #0ea5e9;
        --info-light: rgba(14, 165, 233, 0.1);
        --warning: #f59e0b;
        --warning-light: rgba(245, 158, 11, 0.1);
        --danger: #ef4444;
        --surface: #ffffff;
        --surface-2: #f8fafc;
        --border: #e2e8f0;
        --text-primary: #0f172a;
        --text-muted: #64748b;
        --radius: 16px;
        --radius-sm: 10px;
        --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
        --shadow-md: 0 4px 6px rgba(0,0,0,0.05), 0 10px 30px rgba(0,0,0,0.08);
    }

    /* ── STAT CARDS ── */
    .wing-stat {
        background: var(--surface);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 1rem 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.9rem;
        border: 1px solid var(--border);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
        overflow: hidden;
        position: relative;
    }
    .wing-stat::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 4px; height: 100%;
        border-radius: 4px 0 0 4px;
    }
    .wing-stat.sales::before  { background: var(--primary); }
    .wing-stat.profit::before { background: var(--success); }
    .wing-stat.revenue::before{ background: var(--info); }
    .wing-stat.stock::before  { background: var(--warning); }

    .wing-stat:active { transform: scale(0.98); }

    .stat-icon {
        width: 44px; height: 44px; min-width: 44px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem;
    }
    .stat-icon.sales   { background: var(--primary-light); color: var(--primary); }
    .stat-icon.profit  { background: var(--success-light); color: var(--success); }
    .stat-icon.revenue { background: var(--info-light);    color: var(--info); }
    .stat-icon.stock   { background: var(--warning-light); color: var(--warning); }

    .stat-body { flex: 1; min-width: 0; }
    .stat-label {
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        margin-bottom: 0.2rem;
        white-space: nowrap;
    }
    .stat-value {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .stat-value.danger { color: var(--danger); }
    .stat-value.warning { color: var(--warning); }

    .stat-toggle {
        background: none; border: none; padding: 0 0 0 0.3rem;
        color: var(--text-muted); cursor: pointer; line-height: 1;
        font-size: 1rem; flex-shrink: 0;
    }

    /* ── SUBSCRIPTION BANNER ── */
    .sub-banner {
        border-radius: var(--radius-sm);
        padding: 0.65rem 1rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
        justify-content: space-between;
        font-size: 0.82rem;
        font-weight: 600;
        border: 1px solid transparent;
    }
    .sub-banner.active {
        background: rgba(79, 70, 229, 0.07);
        border-color: rgba(79, 70, 229, 0.2);
        color: var(--primary);
    }
    .sub-banner.expired {
        background: rgba(239, 68, 68, 0.07);
        border-color: rgba(239, 68, 68, 0.2);
        color: var(--danger);
    }

    /* ── SYSTEM MASTER SWITCH ── */
    .master-switch-card {
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1rem 1.1rem;
    }

    /* ── QUICK ACTIONS ── */
    .quick-actions-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
    }
    .qa-header {
        padding: 0.8rem 1rem;
        border-bottom: 1px solid var(--border);
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--text-primary);
        background: var(--surface-2);
    }
    .qa-body { padding: 1rem; }

    .btn-wing-primary {
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: var(--radius-sm);
        padding: 0.7rem 1rem;
        font-weight: 600;
        font-size: 0.88rem;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: background 0.15s, transform 0.12s;
        text-decoration: none;
    }
    .btn-wing-primary:hover  { background: #4338ca; color: #fff; }
    .btn-wing-primary:active { transform: scale(0.97); }

    .btn-wing-danger {
        background: rgba(239,68,68,0.08);
        color: var(--danger);
        border: 1.5px solid rgba(239,68,68,0.25);
        border-radius: var(--radius-sm);
        padding: 0.7rem 1rem;
        font-weight: 600;
        font-size: 0.88rem;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: background 0.15s, transform 0.12s;
        background-color: transparent;
        cursor: pointer;
    }
    .btn-wing-danger:hover  { background: rgba(239,68,68,0.12); color: var(--danger); }
    .btn-wing-danger:active { transform: scale(0.97); }

    .btn-wing-success {
        background: var(--success);
        color: #fff;
        border: none;
        border-radius: var(--radius-sm);
        padding: 0.7rem 1rem;
        font-weight: 600;
        font-size: 0.88rem;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: background 0.15s, transform 0.12s;
        cursor: pointer;
    }
    .btn-wing-success:hover  { background: #059669; color: #fff; }
    .btn-wing-success:active { transform: scale(0.97); }

    .btn-wing-warning {
        background: rgba(245,158,11,0.1);
        color: var(--warning);
        border: 1.5px solid rgba(245,158,11,0.3);
        border-radius: var(--radius-sm);
        padding: 0.55rem 1rem;
        font-weight: 600;
        font-size: 0.82rem;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        text-decoration: none;
        transition: background 0.15s;
    }
    .btn-wing-warning:hover { background: rgba(245,158,11,0.18); color: var(--warning); }

    .no-shift-msg {
        background: var(--surface-2);
        border: 1px dashed var(--border);
        border-radius: var(--radius-sm);
        padding: 0.7rem;
        text-align: center;
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 0.6rem;
    }

    /* ── TABLE CARD ── */
    .wing-table-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
    }
    .wing-table-header {
        padding: 0.8rem 1rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--surface-2);
    }
    .wing-table-header h5 {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }
    .wing-table-card .table {
        margin: 0;
        font-size: 0.83rem;
    }
    .wing-table-card .table th {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--text-muted);
        background: var(--surface-2);
        border-color: var(--border);
        padding: 0.6rem 0.9rem;
        white-space: nowrap;
    }
    .wing-table-card .table td {
        vertical-align: middle;
        padding: 0.65rem 0.9rem;
        border-color: var(--border);
        color: var(--text-primary);
    }
    .wing-table-card .table tbody tr:last-child td { border-bottom: none; }

    .receipt-link {
        font-weight: 600;
        color: var(--primary);
        text-decoration: none;
        font-family: 'Courier New', monospace;
        font-size: 0.8rem;
    }
    .receipt-link:hover { text-decoration: underline; }

    .badge-method {
        padding: 0.25em 0.65em;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    .badge-cash   { background: var(--success-light); color: var(--success); }
    .badge-mpesa  { background: var(--primary-light);  color: var(--primary); }
    .badge-credit { background: var(--warning-light);  color: var(--warning); }

    /* ── MODALS ── */
    .modal-content {
        border-radius: var(--radius);
        border: none;
        box-shadow: var(--shadow-md);
    }
    .modal-header { border-bottom: 1px solid var(--border); padding: 1rem 1.25rem; }
    .modal-footer { border-top:   1px solid var(--border); padding: 0.8rem 1.25rem; }
    .modal-body   { padding: 1.25rem; }
    .modal-title  { font-size: 1rem; font-weight: 700; }
    .form-control {
        border-radius: var(--radius-sm);
        border: 1.5px solid var(--border);
        font-size: 0.88rem;
        padding: 0.6rem 0.85rem;
    }
    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(79,70,229,0.12);
    }
    .form-label { font-size: 0.82rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.35rem; }

    /* ── DIVIDER ── */
    .qa-divider {
        border: none;
        border-top: 1px solid var(--border);
        margin: 0.9rem 0;
    }
    .qa-section-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        margin-bottom: 0.55rem;
    }

    /* ── EMPTY STATE ── */
    .empty-state {
        padding: 2rem 1rem;
        text-align: center;
        color: var(--text-muted);
        font-size: 0.83rem;
    }
    .empty-state i { font-size: 1.8rem; margin-bottom: 0.5rem; opacity: 0.4; display: block; }

    /* ── RESPONSIVE TWEAKS ── */
    @media (max-width: 575px) {
        .stat-value { font-size: 1rem; }
        .wing-table-card .table th,
        .wing-table-card .table td { padding: 0.55rem 0.65rem; }
    }
</style>
@endpush

@section('content')

{{-- ── SUBSCRIPTION BANNER ── --}}
@if(auth()->user()->isOwner())
<div class="mb-3">
    <div class="sub-banner {{ $subscriptionStatus === 'active' ? 'active' : 'expired' }}">
        <div class="d-flex align-items-center gap-2">
            <i class="bi {{ $subscriptionStatus === 'active' ? 'bi-calendar-check' : 'bi-calendar-x' }}"></i>
            <span>Subscription: <span class="text-uppercase">{{ $subscriptionStatus }}</span>
                @if($subscriptionExpiresAt)
                    &nbsp;·&nbsp; Expires {{ $subscriptionExpiresAt->format('M d, Y') }} ({{ $subscriptionExpiresAt->diffForHumans() }})
                @endif
            </span>
        </div>
        <a href="{{ route('system.control') }}" class="d-flex align-items-center gap-1 fw-bold text-decoration-none" style="color:inherit;">
            Manage <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</div>
@endif

{{-- ── STAT CARDS ── --}}
<div class="row g-2 mb-3">

    {{-- Today's Sales --}}
    <div class="col-6 col-md-3">
        <div class="wing-stat sales">
            <div class="stat-icon sales"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-body">
                <div class="stat-label">Today's Sales</div>
                <div class="stat-value" id="salesValue">
                    <span id="salesMasked">KES •••••</span>
                    <span id="salesActual" style="display:none">KES {{ number_format($todaySales, 2) }}</span>
                </div>
            </div>
            <button class="stat-toggle" id="toggleSalesBtn" title="Toggle visibility">
                <i class="bi bi-eye-slash" id="salesEyeIcon"></i>
            </button>
        </div>
    </div>

    {{-- MTD Profit (Owner only) --}}
    @if(auth()->user()->isOwner())
    <div class="col-6 col-md-3">
        <div class="wing-stat profit">
            <div class="stat-icon profit"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-body">
                <div class="stat-label">MTD Profit</div>
                <div class="stat-value {{ $mtdProfit < 0 ? 'danger' : '' }}">
                    KES {{ number_format($mtdProfit, 2) }}
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(auth()->user()->isSuperAdmin() || auth()->user()->isOwner() || auth()->user()->isManager())
    {{-- MTD Revenue --}}
    <div class="col-6 col-md-3">
        <div class="wing-stat revenue">
            <div class="stat-icon revenue"><i class="bi bi-currency-dollar"></i></div>
            <div class="stat-body">
                <div class="stat-label">MTD Revenue</div>
                <div class="stat-value">KES {{ number_format($mtdRevenue, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- Low Stock --}}
    <div class="col-6 col-md-3">
        <div class="wing-stat stock">
            <div class="stat-icon stock"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-body">
                <div class="stat-label">Low Stock</div>
                <div class="stat-value warning">{{ $lowStockProducts }} items</div>
            </div>
        </div>
    </div>
    @endif

</div>

{{-- ── SYSTEM MASTER SWITCH (Owner) ── --}}
@if(auth()->user()->isOwner())
<div class="master-switch-card mb-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <div>
        <div class="fw-bold small mb-1"><i class="bi bi-shield-check me-2 text-primary"></i>System Master Switch</div>
        <div>
            @if($isSystemActive)
                <span class="badge" style="background:var(--success-light);color:var(--success);font-size:0.75rem;">● ACTIVE — All users can access</span>
            @else
                <span class="badge" style="background:rgba(239,68,68,0.1);color:var(--danger);font-size:0.75rem;">● DEACTIVATED — Only you can access</span>
            @endif
        </div>
    </div>
    @if($isSystemActive)
        <button type="button" class="btn btn-sm btn-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#deactivateSystemModal">
            <i class="bi bi-power me-1"></i> Deactivate
        </button>
    @else
        <button type="button" class="btn btn-sm btn-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#activateSystemModal">
            <i class="bi bi-play-fill me-1"></i> Activate
        </button>
    @endif
</div>
@endif

{{-- ── MAIN CONTENT GRID ── --}}
<div class="row g-3">

    {{-- Sales Table --}}
    <div class="col-12 col-lg-8 order-2 order-lg-1">
        <div class="wing-table-card">
            <div class="wing-table-header">
                @if(auth()->user()->isSuperAdmin() && $branchSales)
                    <h5>Branch Sales Today</h5>
                @else
                    <h5>Recent Sales</h5>
                    <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary" style="font-size:0.78rem;padding:0.3rem 0.75rem;border-radius:20px;">View All</a>
                @endif
            </div>

            @if(auth()->user()->isSuperAdmin() && $branchSales)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>Count</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($branchSales as $branch)
                                <tr>
                                    <td><strong>{{ $branch['name'] }}</strong></td>
                                    <td>{{ $branch['sales_count'] }}</td>
                                    <td><strong>KES {{ number_format($branch['total_amount'], 2) }}</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><div class="empty-state"><i class="bi bi-shop"></i>No branch sales today</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Receipt</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th class="d-none d-sm-table-cell">Method</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentSales as $sale)
                                <tr>
                                    <td>
                                        <a href="{{ route('sales.show', $sale) }}" class="receipt-link">
                                            #{{ $sale->receipt_number }}
                                        </a>
                                    </td>
                                    <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                                    <td><strong>KES {{ number_format($sale->total_amount, 2) }}</strong></td>
                                    <td class="d-none d-sm-table-cell">
                                        @php $method = strtolower($sale->primary_payment_method) @endphp
                                        <span class="badge-method badge-{{ $method }}">{{ ucfirst($sale->primary_payment_method) }}</span>
                                    </td>
                                    <td style="color:var(--text-muted);">{{ $sale->created_at->format('H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="empty-state"><i class="bi bi-receipt"></i>No sales yet today</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="col-12 col-lg-4 order-1 order-lg-2">
        <div class="quick-actions-card">
            <div class="qa-header"><i class="bi bi-lightning-charge me-2 text-warning"></i>Quick Actions</div>
            <div class="qa-body">
                @if ($activeShift)
                    <a href="{{ route('sales.create') }}" class="btn-wing-primary mb-2">
                        <i class="bi bi-plus-circle"></i> New Sale
                    </a>
                    <button class="btn-wing-danger" data-bs-toggle="modal" data-bs-target="#closeShiftModal">
                        <i class="bi bi-x-circle"></i> Close Shift
                    </button>
                @else
                    <button class="btn-wing-success" data-bs-toggle="modal" data-bs-target="#openShiftModal">
                        <i class="bi bi-play-circle"></i> Open Shift
                    </button>
                    <div class="no-shift-msg">
                        <i class="bi bi-info-circle me-1"></i> No active shift. Open one to start selling.
                    </div>
                @endif

                <hr class="qa-divider">

                <div class="qa-section-label">Inventory</div>
                @if ($lowStockProducts > 0)
                    <a href="{{ route('products.index') }}" class="btn-wing-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        {{ $lowStockProducts }} low stock item{{ $lowStockProducts > 1 ? 's' : '' }} — review now
                    </a>
                @else
                    <div class="d-flex align-items-center gap-2" style="font-size:0.82rem;color:var(--success);">
                        <i class="bi bi-check-circle-fill"></i> All products well stocked
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>


{{-- ════════════════════════════════
     MODALS
════════════════════════════════ --}}

{{-- Open Shift --}}
<div class="modal fade" id="openShiftModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-play-circle me-2 text-success"></i>Open New Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('shifts.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Opening Cash (KES)</label>
                        <input type="number" step="0.01" name="opening_cash" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="opening_notes" class="form-control" rows="2" placeholder="Any opening notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-4">Open Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Close Shift --}}
<div class="modal fade" id="closeShiftModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-x-circle me-2 text-danger"></i>Close Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('shifts.close') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Closing Cash (KES)</label>
                        <input type="number" step="0.01" name="closing_cash" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="closing_notes" class="form-control" rows="2" placeholder="Any closing notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4">Close Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Deactivate System --}}
@if(auth()->user()->isOwner() && $isSystemActive)
<div class="modal fade" id="deactivateSystemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-power me-2"></i>Deactivate System</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('system.toggle') }}" method="POST">
                @csrf
                <input type="hidden" name="status" value="deactivate">
                <div class="modal-body">
                    <div class="alert alert-danger py-2 small mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        This will lock out all users except you.
                    </div>
                    <div>
                        <label class="form-label">Security Password</label>
                        <input type="password" name="system_password" class="form-control" placeholder="Enter password to confirm" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4">Verify & Deactivate</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Activate System --}}
@if(auth()->user()->isOwner() && !$isSystemActive)
<div class="modal fade" id="activateSystemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-success"><i class="bi bi-play-fill me-2"></i>Activate System</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('system.toggle') }}" method="POST">
                @csrf
                <input type="hidden" name="status" value="activate">
                <div class="modal-body">
                    <p class="small mb-3 text-muted">All users will regain access to the POS once activated.</p>
                    <div>
                        <label class="form-label">Security Password</label>
                        <input type="password" name="system_password" class="form-control" placeholder="Enter password to confirm" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-4">Verify & Activate</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn     = document.getElementById('toggleSalesBtn');
    const icon    = document.getElementById('salesEyeIcon');
    const masked  = document.getElementById('salesMasked');
    const actual  = document.getElementById('salesActual');
    let hidden = true;

    if (btn) {
        btn.addEventListener('click', function () {
            hidden = !hidden;
            masked.style.display = hidden ? 'inline' : 'none';
            actual.style.display = hidden ? 'none'   : 'inline';
            icon.className = hidden ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }
});
</script>
@endpush