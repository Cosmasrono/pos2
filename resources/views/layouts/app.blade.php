<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Wing POS') - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6366f1">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --gradient-primary: linear-gradient(135deg, #6366f1, #8b5cf6);
            --gradient-success: linear-gradient(135deg, hsl(142,71%,45%), hsl(158,64%,52%));
            --gradient-danger: linear-gradient(135deg, hsl(0,84%,60%), hsl(340,82%,52%));
            --gradient-warning: linear-gradient(135deg, hsl(38,92%,50%), hsl(45,93%,47%));
            --gradient-info: linear-gradient(135deg, hsl(199,89%,48%), hsl(217,91%,60%));
            --success: hsl(142,71%,45%);
            --success-light: hsl(142,71%,95%);
            --danger: hsl(0,84%,60%);
            --danger-light: hsl(0,84%,95%);
            --warning: hsl(38,92%,50%);
            --warning-light: hsl(38,92%,95%);
            --info: hsl(199,89%,48%);
            --info-light: hsl(199,89%,95%);
            --background: hsl(210,40%,98%);
            --surface: #fff;
            --text-main: hsl(215,25%,27%);
            --text-muted: hsl(215,16%,47%);
            --border-color: hsl(214,32%,91%);
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            --transition-fast: 150ms cubic-bezier(0.4,0,0.2,1);
            --transition-base: 200ms cubic-bezier(0.4,0,0.2,1);
        }

        * { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, hsl(210,40%,98%) 0%, hsl(220,40%,96%) 100%);
            color: var(--text-main);
            letter-spacing: -0.01em;
            line-height: 1.6;
        }

        /* ══════════════════════════════
           SIDEBAR
        ══════════════════════════════ */
        .sidebar {
            background: #0f1117;
            color: white;
            position: fixed;
            z-index: 1050;
            border-right: 1px solid rgba(255,255,255,0.06);
            transition: transform var(--transition-base);
            display: flex;
            flex-direction: column;
        }

        @media (min-width: 769px) {
            .sidebar { position: sticky; top: 0; }
        }

        .sidebar .brand {
            padding: 22px 18px;
            display: flex;
            align-items: center;
            gap: 11px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            flex-shrink: 0;
        }

        .brand-icon {
            width: 34px;
            height: 34px;
            background: var(--gradient-primary);
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 16px;
            color: white;
        }

        .brand-text {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .brand-sub {
            font-size: 10px;
            color: rgba(255,255,255,0.3);
            letter-spacing: 0.1em;
            margin-top: 2px;
        }

        .sidebar .nav-scroll {
            flex: 1;
            overflow-y: auto;
            padding-bottom: 8px;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.1) transparent;
        }

        .sidebar .nav-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar .nav-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar .nav-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

        .nav-section-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.1em;
            color: rgba(255,255,255,0.25);
            text-transform: uppercase;
            padding: 16px 20px 5px;
        }

        .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.06);
            margin: 6px 10px;
        }

        .sidebar .nav-item { padding: 2px 10px; }

        .sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 9px 12px;
            border-radius: 8px;
            color: rgba(255,255,255,0.5);
            font-size: 13.5px;
            font-weight: 500;
            transition: all var(--transition-fast);
            position: relative;
            text-decoration: none;
            margin: 0;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
        }

        .sidebar .nav-link:hover {
            color: rgba(255,255,255,0.9);
            background: rgba(255,255,255,0.06);
            transform: none;
        }

        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(99,102,241,0.18);
            box-shadow: none;
        }

        .sidebar .nav-link.active::before {
            content: '';
            position: absolute;
            left: -10px;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 18px;
            background: var(--gradient-primary);
            border-radius: 0 3px 3px 0;
        }

        .nav-icon {
            font-size: 15px;
            opacity: 0.7;
            flex-shrink: 0;
            width: 16px;
            text-align: center;
        }

        .sidebar .nav-link.active .nav-icon,
        .sidebar .nav-link:hover .nav-icon { opacity: 1; }


        .nav-badge {
            margin-left: auto;
            font-size: 10px;
            font-weight: 600;
            background: rgba(99,102,241,0.25);
            color: #a5b4fc;
            padding: 2px 7px;
            border-radius: 10px;
        }

        /* Sidebar user section */
        .sidebar-user-section {
            padding: 12px 10px;
            border-top: 1px solid rgba(255,255,255,0.06);
            flex-shrink: 0;
        }

        .sidebar-user-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            background: rgba(255,255,255,0.04);
            margin-bottom: 4px;
        }

        .sidebar-avatar {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
        }

        .sidebar-user-name {
            font-size: 13px;
            font-weight: 600;
            color: rgba(255,255,255,0.8);
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-user-role {
            font-size: 11px;
            color: rgba(255,255,255,0.3);
            margin-top: 1px;
        }

        .sidebar-logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 8px 12px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: rgba(239,68,68,0.6);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-align: left;
            transition: all var(--transition-fast);
        }

        .sidebar-logout-btn:hover {
            color: rgba(239,68,68,0.9);
            background: rgba(239,68,68,0.08);
        }

        /* ══════════════════════════════
           TOP NAVBAR
        ══════════════════════════════ */
        .navbar-top {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border-bottom: 1px solid var(--border-color);
            flex-shrink: 0;
        }

        .navbar-top h5 {
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ══════════════════════════════
           MAIN CONTENT
        ══════════════════════════════ */
        .main-content {
            padding: 1.5rem;
            width: 100%;
            overflow-x: hidden;
            overflow-y: auto;
            flex: 1;
        }

        @media (max-width: 768px) {
            .main-content { padding: 1rem; }
        }

        /* ══════════════════════════════
           CARDS
        ══════════════════════════════ */
        .card {
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            border-radius: 16px;
            transition: all var(--transition-base);
            background: var(--surface);
            overflow: hidden;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .card-header {
            background: linear-gradient(135deg, rgba(99,102,241,0.05) 0%, rgba(139,92,246,0.03) 100%);
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
            padding: 1rem 1.5rem;
        }

        /* Stat Cards */
        .stat-card {
            border: none;
            position: relative;
            overflow: hidden;
            background: var(--surface);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .stat-card.border-success::before { background: var(--gradient-success); }
        .stat-card.border-info::before    { background: var(--gradient-info); }
        .stat-card.border-warning::before { background: var(--gradient-warning); }
        .stat-card.border-danger::before  { background: var(--gradient-danger); }

        .stat-card h6 {
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }

        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.2;
        }

        /* ══════════════════════════════
           TABLES
        ══════════════════════════════ */
        .table { border-collapse: separate; border-spacing: 0; }

        .table thead th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border-color);
            padding: 1rem;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }

        .table-hover tbody tr { transition: all var(--transition-fast); }

        .table-hover tbody tr:hover {
            background: linear-gradient(90deg, rgba(99,102,241,0.05) 0%, rgba(139,92,246,0.03) 100%);
        }

        /* ══════════════════════════════
           BADGES
        ══════════════════════════════ */
        .badge {
            font-weight: 600;
            padding: 0.35em 0.75em;
            border-radius: 6px;
            font-size: 0.8rem;
            letter-spacing: 0.02em;
        }

        .badge.bg-info    { background: var(--gradient-info) !important; }
        .badge.bg-success { background: var(--gradient-success) !important; }
        .badge.bg-warning { background: var(--gradient-warning) !important; }
        .badge.bg-danger  { background: var(--gradient-danger) !important; }
        .badge.bg-primary { background: var(--gradient-primary) !important; }

        /* ══════════════════════════════
           ALERTS
        ══════════════════════════════ */
        .alert {
            border: none;
            border-radius: 12px;
            border-left: 4px solid;
            box-shadow: var(--shadow-sm);
        }

        .alert-success { background: var(--success-light); border-left-color: var(--success); color: hsl(142,71%,25%); }
        .alert-danger  { background: var(--danger-light);  border-left-color: var(--danger);  color: hsl(0,84%,30%); }
        .alert-info    { background: var(--info-light);    border-left-color: var(--info);    color: hsl(199,89%,28%); }
        .alert-warning { background: var(--warning-light); border-left-color: var(--warning); color: hsl(38,92%,25%); }

        /* ══════════════════════════════
           BUTTONS
        ══════════════════════════════ */
        .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all var(--transition-base);
            border: none;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.02em;
        }

        .btn-primary {
            background: var(--gradient-primary);
            box-shadow: 0 4px 12px rgba(99,102,241,0.3);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(99,102,241,0.4); color: white; }

        .btn-success {
            background: var(--gradient-success);
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
            color: white;
        }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16,185,129,0.4); color: white; }

        .btn-danger {
            background: var(--gradient-danger);
            box-shadow: 0 4px 12px rgba(239,68,68,0.3);
            color: white;
        }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(239,68,68,0.4); color: white; }

        .btn-warning {
            background: var(--gradient-warning);
            box-shadow: 0 4px 12px rgba(245,158,11,0.3);
            color: white;
        }
        .btn-warning:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(245,158,11,0.4); color: white; }

        .btn-outline-primary {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }
        .btn-outline-primary:hover { background: var(--gradient-primary); color: white; transform: translateY(-2px); }

        .btn-outline-secondary {
            border: 2px solid var(--border-color);
            color: var(--text-muted);
            background: transparent;
        }
        .btn-outline-secondary:hover { background: var(--background); color: var(--text-main); }

        .btn-sm { padding: 6px 14px; font-size: 0.875rem; }

        /* ══════════════════════════════
           FORMS
        ══════════════════════════════ */
        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 10px 16px;
            transition: all var(--transition-base);
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99,102,241,0.1);
            outline: none;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        /* ══════════════════════════════
           MODALS
        ══════════════════════════════ */
        .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-xl);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, rgba(99,102,241,0.05) 0%, rgba(139,92,246,0.03) 100%);
            border-radius: 16px 16px 0 0;
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            background: var(--background);
            border-radius: 0 0 16px 16px;
        }

        /* ══════════════════════════════
           SCROLLBAR
        ══════════════════════════════ */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--background); }
        ::-webkit-scrollbar-thumb { background: #6366f1; border-radius: 3px; }

        /* ══════════════════════════════
           RESPONSIVE LAYOUT
        ══════════════════════════════ */

        /* Desktop: sidebar sticks full height */
        @media (min-width: 992px) {
            .sidebar {
                position: sticky;
                top: 0;
                height: 100dvh;
                min-height: 100dvh;
            }
            .mobile-bottom-nav { display: none !important; }
        }

        /* Tablet + Mobile: sidebar slides in, bottom nav appears */
        @media (max-width: 991px) {
            .sidebar {
                position: fixed;
                left: -270px;
                top: 0;
                bottom: 0;
                height: 100%;
                z-index: 1050;
                transition: left 0.25s ease;
                width: 270px !important;
            }
            .sidebar.show { left: 0; }

            #sidebarOverlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1049;
                backdrop-filter: blur(2px);
            }
            #sidebarOverlay.show { display: block; }

            /* Push content down so bottom nav doesn't overlap */
            .main-content { padding-bottom: 80px; }

            /* App wrapper takes full dynamic viewport height */
            .app-wrapper { height: 100dvh; }
        }

        /* ── BOTTOM NAVIGATION ── */
        .mobile-bottom-nav {
            display: flex;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 2px solid var(--border-color);
            z-index: 1000;
            padding: 6px 0 max(6px, env(safe-area-inset-bottom));
            box-shadow: 0 -4px 24px rgba(0,0,0,0.10);
        }

        .bottom-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            padding: 6px 4px;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 600;
            background: none;
            border: none;
            cursor: pointer;
            transition: color var(--transition-fast);
            -webkit-tap-highlight-color: transparent;
        }

        .bottom-nav-item i { font-size: 20px; }

        .bottom-nav-item.active,
        .bottom-nav-item:hover { color: var(--primary); }

        .bottom-nav-item.active i {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Mobile toggle button (hamburger) */
        .mobile-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: white;
            border: 1px solid var(--border-color);
            color: var(--text-main);
            cursor: pointer;
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>

    {{-- Offline POS: sync toast + pending badge --}}
    <style>
        .wingpos-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #fff;
            border: 1px solid var(--border-color, #e5e7eb);
            border-left: 4px solid var(--success, #16a34a);
            border-radius: 12px;
            padding: 14px 20px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: var(--shadow-xl, 0 20px 40px rgba(0,0,0,.15));
            z-index: 9999;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
            max-width: 360px;
        }
        .wingpos-toast.show { transform: translateY(0); opacity: 1; }

        .nav-pending-dot {
            display: none;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            background: var(--warning, #f59e0b);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            border-radius: 9px;
            padding: 0 5px;
            margin-left: auto;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div id="sidebarOverlay"></div>

    <div class="d-flex app-wrapper" style="height: 100dvh;">

        <!-- ══ SIDEBAR ══ -->
        <nav class="sidebar" id="sidebar" style="width: 260px;">

            {{-- Brand --}}
            <div class="brand">
                <div class="brand-icon">
                    <i class="bi bi-shop"></i>
                </div>
                <div>
                    <div class="brand-text">Wing POS</div>
                    <div class="brand-sub">RETAIL SYSTEM</div>
                </div>
            </div>

            {{-- Nav Links --}}
            <div class="nav-scroll">

                {{-- ── EVERYONE ── --}}
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('sales.create') || request()->routeIs('sales.pos') ? 'active' : '' }}"
                       href="{{ route('sales.create') }}">
                        <i class="bi bi-bag-check nav-icon"></i> Sell
                        <span class="nav-badge">POS</span>
                        {{-- Pending offline sales counter --}}
                        <span id="pendingSalesBadge" class="nav-pending-dot ms-1">0</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('sales.index') ? 'active' : '' }}"
                       href="{{ route('sales.index') }}">
                        <i class="bi bi-receipt nav-icon"></i> Sales
                    </a>
                </div>

                {{-- ── MANAGERS & ABOVE ── --}}
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isManager() || auth()->user()->isOwner())

                <div class="nav-divider"></div>

                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('products.*') && !request()->routeIs('stock.*') && !request()->routeIs('categories.*') ? 'active' : '' }}"
                       href="{{ route('products.index') }}">
                        <i class="bi bi-box-seam nav-icon"></i> Products
                    </a>
                </div>

                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('stock.*') ? 'active' : '' }}"
                       href="{{ route('stock.receive') }}">
                        <i class="bi bi-box-arrow-in-down nav-icon"></i> Receive Delivery
                    </a>
                </div>

                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('expenses.*') || request()->routeIs('expense-categories.*') ? 'active' : '' }}"
                       href="{{ route('expenses.index') }}">
                        <i class="bi bi-cash-stack nav-icon"></i> Expenses
                    </a>
                </div>

                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                       href="{{ route('reports.sales') }}">
                        <i class="bi bi-graph-up nav-icon"></i> Reports
                    </a>
                </div>

                {{-- ── OWNERS & SUPERADMIN ── --}}
                @if(auth()->user()->isOwner() || auth()->user()->isSuperAdmin())

                <div class="nav-divider"></div>

                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('ai.*') ? 'active' : '' }}"
                       href="{{ route('ai.dashboard') }}">
                        <i class="bi bi-robot nav-icon"></i> AI Insights
                    </a>
                </div>

                {{-- Settings — always visible --}}
                <div class="nav-section-label mt-2">Settings</div>

                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                       href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2 nav-icon"></i> Dashboard
                    </a>
                </div>

                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('stock-transfers.*') ? 'active' : '' }}"
                       href="{{ route('stock-transfers.index') }}">
                        <i class="bi bi-arrow-left-right nav-icon"></i> Stock Transfers
                    </a>
                </div>

                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('purchase-orders.*') || request()->routeIs('suppliers.*') ? 'active' : '' }}"
                       href="{{ route('purchase-orders.index') }}">
                        <i class="bi bi-cart nav-icon"></i> Order Purchases
                    </a>
                </div>

                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}"
                       href="{{ route('invoices.index') }}">
                        <i class="bi bi-file-earmark-text nav-icon"></i> Invoices
                    </a>
                </div>

                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('loans.*') ? 'active' : '' }}"
                       href="{{ route('loans.index') }}">
                        <i class="bi bi-credit-card nav-icon"></i> Loans
                    </a>
                </div>

                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('promotions.*') ? 'active' : '' }}"
                       href="{{ route('promotions.index') }}">
                        <i class="bi bi-ticket-perforated nav-icon"></i> Promotions
                    </a>
                </div>

                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}"
                       href="{{ route('branches.index') }}">
                        <i class="bi bi-building nav-icon"></i> Branches
                    </a>
                </div>

                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                       href="{{ route('users.index') }}">
                        <i class="bi bi-people nav-icon"></i> Users
                    </a>
                </div>

                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}"
                       href="{{ route('audit-logs.index') }}">
                        <i class="bi bi-shield-lock nav-icon"></i> Audit Trail
                    </a>
                </div>

                @if(auth()->user()->isOwner())
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('system.control') ? 'active' : '' }}"
                       href="{{ route('system.control') }}">
                        <i class="bi bi-gear-wide-connected nav-icon"></i> System Control
                    </a>
                </div>
                @endif

                @endif
                @endif

            </div>

            {{-- User + Logout --}}
            <div class="sidebar-user-section">
                <div class="sidebar-user-card">
                    <div class="sidebar-avatar">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div style="overflow: hidden;">
                        <div class="sidebar-user-name">{{ auth()->user()->name }}</div>
                        <div class="sidebar-user-role">
                            @if(auth()->user()->isSuperAdmin()) Super Admin
                            @elseif(auth()->user()->isOwner()) Owner
                            @elseif(auth()->user()->isManager()) Manager
                            @else Cashier
                            @endif
                        </div>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="sidebar-logout-btn">
                        <i class="bi bi-box-arrow-right"></i> Sign out
                    </button>
                </form>
            </div>

        </nav>
        {{-- END SIDEBAR --}}

        <!-- ══ MAIN AREA ══ -->
        <div style="flex: 1; display: flex; flex-direction: column; min-height: 0; overflow: hidden;">

            <!-- Top Navbar -->
            <nav class="navbar-top">
                <div class="container-fluid px-4 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <button class="mobile-toggle d-lg-none" id="sidebarToggle">
                                <i class="bi bi-list fs-4"></i>
                            </button>
                            <h5 class="mb-0">@yield('page-title', 'Dashboard')</h5>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            {{-- Pending offline sales — click to view --}}
                            <span id="pendingTopBadge" class="badge bg-warning text-dark"
                                  style="display:none; cursor:pointer;"
                                  onclick="window.location='{{ route('offline') }}'">
                                <i class="bi bi-clock-history"></i> 0 pending
                            </span>
                            <div id="connectionStatus" class="badge bg-success">
                                <i class="bi bi-wifi"></i> Online
                            </div>
                            @if(auth()->check())
                                <span class="badge bg-primary">{{ auth()->user()->name }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <div class="main-content">

                @yield('content')

                <div class="px-2 py-3 mt-4 text-center border-top bg-white" style="border-radius: 12px;">
                    <small class="text-muted">Made by <strong>@cossi technologies</strong></small>
                </div>

            </div>
        </div>
    </div>

    {{-- ══ TOAST NOTIFICATIONS ══ --}}
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">

        @if ($errors->any())
        <div class="toast align-items-center text-bg-danger border-0 show" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Oops! Please fix:</strong>
                    @foreach ($errors->all() as $error)
                        <div class="small mt-1">• {{ $error }}</div>
                    @endforeach
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        @endif

        @if (session('success'))
        <div class="toast align-items-center text-bg-success border-0 show" role="alert" data-bs-autohide="true" data-bs-delay="4000">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        @endif

        @if (session('error'))
        <div class="toast align-items-center text-bg-danger border-0 show" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-x-circle-fill me-2"></i>{{ session('error') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        @endif

        @if (session('warning'))
        <div class="toast align-items-center text-bg-warning border-0 show" role="alert" data-bs-autohide="true" data-bs-delay="5000">
            <div class="d-flex">
                <div class="toast-body text-dark">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('warning') }}
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        @endif

        @if (session('ai_shift_summary'))
        <div class="toast align-items-center text-bg-info border-0 show" role="alert" data-bs-autohide="true" data-bs-delay="8000">
            <div class="d-flex">
                <div class="toast-body">
                    🤖 <strong>AI Shift Summary:</strong> {{ session('ai_shift_summary') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        @endif

    </div>

    {{-- ══ MOBILE BOTTOM NAV ══ --}}
    <nav class="mobile-bottom-nav d-lg-none" role="navigation">
        <a href="{{ route('sales.create') }}"
           class="bottom-nav-item {{ request()->routeIs('sales.create', 'sales.pos') ? 'active' : '' }}">
            <i class="bi bi-bag-check"></i><span>Sell</span>
        </a>
        <a href="{{ route('sales.index') }}"
           class="bottom-nav-item {{ request()->routeIs('sales.index') ? 'active' : '' }}">
            <i class="bi bi-receipt"></i><span>Sales</span>
        </a>
        @if(auth()->user()->isSuperAdmin() || auth()->user()->isManager() || auth()->user()->isOwner())
        <a href="{{ route('products.index') }}"
           class="bottom-nav-item {{ request()->routeIs('products.*') && !request()->routeIs('stock.*') ? 'active' : '' }}">
            <i class="bi bi-box-seam"></i><span>Products</span>
        </a>
        <a href="{{ route('expenses.index') }}"
           class="bottom-nav-item {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
            <i class="bi bi-cash-stack"></i><span>Expenses</span>
        </a>
        @endif
        <button class="bottom-nav-item" id="sidebarToggleBottom">
            <i class="bi bi-grid-3x3-gap"></i><span>Menu</span>
        </button>
    </nav>

    <script>
        window.addEventListener('online',  updateStatus);
        window.addEventListener('offline', updateStatus);

        function updateStatus() {
            const status = document.getElementById('connectionStatus');
            if (navigator.onLine) {
                status.className = 'badge bg-success';
                status.innerHTML = '<i class="bi bi-wifi"></i> Online';
            } else {
                status.className = 'badge bg-danger';
                status.innerHTML = '<i class="bi bi-wifi-off"></i> Offline';
            }
        }

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(() => console.log('SW Registered'))
                    .catch(err => console.log('SW Failed', err));
            });
            // Note: offline-pos.js already replays queued sales on the 'online' event.
        }

        document.addEventListener('DOMContentLoaded', function () {
            updateStatus();

            // Auto-init all toasts (Bootstrap 5)
            document.querySelectorAll('.toast').forEach(el => {
                const toast = new bootstrap.Toast(el);
                toast.show();
            });

            const sidebar       = document.getElementById('sidebar');
            const overlay       = document.getElementById('sidebarOverlay');
            const toggle        = document.getElementById('sidebarToggle');
            const toggleBottom  = document.getElementById('sidebarToggleBottom');

            function openSidebar() {
                sidebar.classList.add('show');
                overlay.classList.add('show');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }

            function toggleSidebar() {
                sidebar.classList.contains('show') ? closeSidebar() : openSidebar();
            }

            if (toggle)       toggle.addEventListener('click', toggleSidebar);
            if (toggleBottom) toggleBottom.addEventListener('click', openSidebar);
            if (overlay)      overlay.addEventListener('click', closeSidebar);

            // Close sidebar on nav link click (mobile)
            sidebar.querySelectorAll('a.nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 992) closeSidebar();
                });
            });
        });
    </script>
    {{-- Offline POS manager — must load before pushed page scripts use it --}}
    <script src="/js/offline-pos.js"></script>
    @stack('scripts')
</body>
</html>