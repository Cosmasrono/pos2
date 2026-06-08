<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">
            <i class="bi bi-box-seam-fill"></i>
            <span>Wing POS</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('/') ? 'active' : '' }}" href="{{ url('/') }}">
                        <i class="bi bi-house-door me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/#modules') }}">
                        <i class="bi bi-grid-3x3-gap me-1"></i>Modules
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/#about') }}">
                        <i class="bi bi-info-circle me-1"></i>About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/#contact') }}">
                        <i class="bi bi-envelope me-1"></i>Contact
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                @if (Route::has('login'))
                    @auth
                        <li class="nav-item">
                            <a href="{{ url('/dashboard') }}" class="btn btn-nav-primary">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a href="{{ route('login') }}" class="nav-link">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Log in
                            </a>
                        </li>
                        @if (Route::has('register'))
                            <li class="nav-item">
                                <a href="{{ route('register') }}" class="btn btn-nav-primary">
                                    <i class="bi bi-person-plus me-2"></i>Register
                                </a>
                            </li>
                        @endif
                    @endauth
                @endif
            </ul>
        </div>
    </div>
</nav>

<style>
/* ===================================
   PROFESSIONAL GUEST NAVIGATION
   =================================== */

:root {
    --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    --secondary-gradient: linear-gradient(135deg, #ff6b35 0%, #ff8555 100%);
    --navbar-bg: rgba(15, 15, 30, 0.95);
    --navbar-border: rgba(255, 255, 255, 0.1);
    --nav-link-color: rgba(255, 255, 255, 0.75);
    --nav-link-hover: rgba(255, 255, 255, 1);
    --nav-link-active: #ff6b35;
}

/* Navbar Container */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    background: transparent;
    backdrop-filter: blur(0px);
    padding: 18px 0;
    border-bottom: 1px solid transparent;
    z-index: 1000;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.navbar.scrolled {
    background: var(--navbar-bg);
    backdrop-filter: blur(20px);
    padding: 12px 0;
    border-bottom: 1px solid var(--navbar-border);
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
}

/* Brand Styling */
.navbar-brand {
    font-weight: 800;
    font-size: 1.5rem;
    color: #fff !important;
    display: flex;
    align-items: center;
    gap: 12px;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    padding: 0;
}

.navbar-brand:hover {
    transform: translateY(-2px);
}

.navbar-brand i {
    font-size: 1.8rem;
    color: #ff6b35;
    transition: all 0.3s ease;
}

.navbar-brand:hover i {
    transform: rotate(10deg) scale(1.1);
    color: #ff8555;
}

.navbar-brand span {
    background: linear-gradient(135deg, #fff 0%, #f0f0f0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Navigation Links */
.navbar-nav {
    gap: 0.5rem;
}

.nav-link {
    color: var(--nav-link-color) !important;
    font-weight: 500;
    font-size: 0.95rem;
    padding: 10px 18px !important;
    border-radius: 10px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    display: flex;
    align-items: center;
    white-space: nowrap;
}

.nav-link i {
    font-size: 1rem;
    opacity: 0.8;
    transition: all 0.3s ease;
}

.nav-link:hover {
    color: var(--nav-link-hover) !important;
    background: rgba(255, 255, 255, 0.08);
    transform: translateY(-1px);
}

.nav-link:hover i {
    opacity: 1;
    transform: scale(1.1);
}

.nav-link.active {
    color: var(--nav-link-active) !important;
    background: rgba(255, 107, 53, 0.12);
    font-weight: 600;
}

.nav-link.active::after {
    content: '';
    position: absolute;
    bottom: 6px;
    left: 50%;
    transform: translateX(-50%);
    width: 30%;
    height: 2px;
    background: var(--secondary-gradient);
    border-radius: 2px;
}

/* Primary Button */
.btn-nav-primary {
    background: var(--primary-gradient);
    color: white !important;
    border: none;
    border-radius: 10px;
    padding: 10px 24px;
    font-weight: 600;
    font-size: 0.95rem;
    box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
    text-decoration: none;
}

.btn-nav-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(79, 70, 229, 0.5);
    filter: brightness(1.1);
    color: white !important;
}

.btn-nav-primary:active {
    transform: translateY(0);
}

.btn-nav-primary i {
    font-size: 1.1rem;
}

/* Mobile Menu Toggle */
.navbar-toggler {
    padding: 8px 12px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.navbar-toggler:focus {
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.25);
}

.navbar-toggler-icon {
    width: 24px;
    height: 24px;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.9%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

/* Navbar Collapse */
.navbar-collapse {
    transition: all 0.3s ease;
}

/* Mobile Responsive */
@media (max-width: 991.98px) {
    .navbar {
        padding: 12px 0;
    }

    .navbar.scrolled {
        padding: 10px 0;
    }

    .navbar-brand {
        font-size: 1.35rem;
    }

    .navbar-brand i {
        font-size: 1.6rem;
    }

    .navbar-collapse {
        background: rgba(15, 15, 30, 0.98);
        backdrop-filter: blur(15px);
        margin-top: 20px;
        padding: 25px 20px;
        border-radius: 16px;
        border: 1px solid var(--navbar-border);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
    }

    .navbar-nav {
        gap: 0.75rem;
    }

    .nav-link {
        padding: 12px 16px !important;
        border-radius: 10px;
    }

    .nav-link.active::after {
        display: none;
    }

    .btn-nav-primary {
        width: 100%;
        justify-content: center;
        margin-top: 8px;
    }

    .navbar-nav.ms-auto {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
}

@media (max-width: 575.98px) {
    .navbar-brand {
        font-size: 1.25rem;
    }

    .navbar-brand i {
        font-size: 1.5rem;
    }

    .nav-link {
        font-size: 0.9rem;
    }

    .btn-nav-primary {
        font-size: 0.9rem;
        padding: 10px 20px;
    }
}

/* Smooth Animations */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.navbar-collapse.show,
.navbar-collapse.collapsing {
    animation: fadeInDown 0.3s ease-out;
}

/* Accessibility */
.nav-link:focus,
.btn-nav-primary:focus {
    outline: 2px solid rgba(79, 70, 229, 0.5);
    outline-offset: 2px;
}

/* Performance Optimization */
.navbar,
.nav-link,
.btn-nav-primary {
    will-change: transform;
}
</style>