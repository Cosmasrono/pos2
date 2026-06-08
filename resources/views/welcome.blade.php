<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wing POS - Modern Retail Management System</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --secondary-gradient: linear-gradient(135deg, #ff6b35 0%, #ff8555 100%);
            --navbar-bg: rgba(15, 15, 30, 0.95);
            --navbar-border: rgba(255, 255, 255, 0.1);
            --nav-link-color: rgba(255, 255, 255, 0.75);
            --nav-link-hover: rgba(255, 255, 255, 1);
            --nav-link-active: #ff6b35;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.75) 0%, rgba(15, 15, 30, 0.85) 100%), url('{{ asset("images/money-bg.png") }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
            color: #fff;
            overflow-x: hidden;
        }

        /* Animated background elements */
        body::before {
            content: '';
            position: fixed;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            top: -200px;
            left: -100px;
            animation: float 8s ease-in-out infinite;
            z-index: 0;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 107, 53, 0.06) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -150px;
            right: -100px;
            animation: float 10s ease-in-out infinite reverse;
            z-index: 0;
            pointer-events: none;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(30px) scale(1.05); }
        }

        /* ===================================
           PROFESSIONAL NAVIGATION
           =================================== */
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
            margin-right: 6px;
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

        .btn-nav-primary i {
            font-size: 1.1rem;
            margin-right: 8px;
        }

        /* Main Content Wrapper */
        .main-wrapper {
            position: relative;
            z-index: 1;
            padding-top: 100px;
        }

        /* Hero Section */
        .hero-section {
            min-height: 90vh;
            display: flex;
            align-items: center;
            padding: 60px 0;
        }

        .hero-content {
            flex: 1;
            max-width: 650px;
            animation: fadeInLeft 1s ease-out;
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 107, 53, 0.15);
            border: 1px solid rgba(255, 107, 53, 0.3);
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #ff8555;
            margin-bottom: 30px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 25px;
            background: linear-gradient(to right, #fff, #d0d0d0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-title .highlight {
            background: var(--secondary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: #b0b0b0;
            margin-bottom: 40px;
            line-height: 1.7;
            max-width: 580px;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .btn-hero {
            padding: 16px 32px;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }

        .btn-primary-glow {
            background: var(--secondary-gradient);
            color: white;
            border: none;
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.4);
        }

        .btn-primary-glow:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(255, 107, 53, 0.6);
            color: white;
        }

        .btn-outline-glow {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(5px);
        }

        .btn-outline-glow:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: #fff;
            transform: translateY(-3px);
            color: white;
        }

        /* Hero Visual */
        .hero-visual {
            flex: 1;
            position: relative;
            display: flex;
            justify-content: center;
            animation: fadeInRight 1s ease-out;
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .glass-card {
            background: rgba(20, 20, 35, 0.85);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            animation: floatCard 6s ease-in-out infinite;
        }

        @keyframes floatCard {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            transition: all 0.3s ease;
            padding: 12px;
            border-radius: 12px;
        }

        .feature-item:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(5px);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        /* Sections */
        .section-spacing {
            padding: 100px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-tag {
            text-transform: uppercase;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 2px;
            color: #ff6b35;
            margin-bottom: 15px;
            display: inline-block;
            background: rgba(255, 107, 53, 0.1);
            padding: 8px 20px;
            border-radius: 50px;
            border: 1px solid rgba(255, 107, 53, 0.2);
        }

        .section-title {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(to right, #fff, #d0d0d0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-description {
            color: rgba(255, 255, 255, 0.6);
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.7;
        }

        /* Module Grid */
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .module-card {
            background: rgba(20, 20, 35, 0.80);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 35px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .module-card:hover {
            transform: translateY(-10px);
            background: rgba(25, 25, 40, 0.90);
            border-color: rgba(255, 107, 53, 0.4);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .module-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 24px;
            color: #ff6b35;
            transition: all 0.3s ease;
        }

        .module-card:hover .module-icon {
            background: var(--secondary-gradient);
            color: white;
            transform: rotate(10deg) scale(1.1);
        }

        .module-name {
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 14px;
            color: #fff;
        }

        .module-description {
            color: rgba(255, 255, 255, 0.65);
            font-size: 0.95rem;
            line-height: 1.7;
        }

        /* Stats */
        .stat-card {
            background: rgba(20, 20, 35, 0.75);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 107, 53, 0.3);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* WhatsApp Float */
        .whatsapp-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 65px;
            height: 65px;
            background: linear-gradient(135deg, #25d366 0%, #20BA5A 100%);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 10px 30px rgba(37, 211, 102, 0.5);
            z-index: 999;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .whatsapp-float:hover {
            transform: scale(1.15) rotate(5deg);
            box-shadow: 0 15px 40px rgba(37, 211, 102, 0.7);
            color: #fff;
        }

        .whatsapp-tooltip {
            position: absolute;
            right: 80px;
            background: rgba(15, 15, 30, 0.95);
            color: #fff;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .whatsapp-float:hover .whatsapp-tooltip {
            opacity: 1;
            visibility: visible;
            transform: translateX(-10px);
        }

        /* Footer */
        .footer {
            background: rgba(0, 0, 0, 0.3);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 40px 0;
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.95rem;
        }

        /* Mobile Responsive */
        @media (max-width: 991.98px) {
            .navbar {
                padding: 12px 0;
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

            .hero-section {
                flex-direction: column;
                text-align: center;
                min-height: auto;
                padding: 40px 0;
            }

            .hero-content {
                max-width: 100%;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
                max-width: 100%;
            }

            .hero-buttons {
                justify-content: center;
            }

            .section-title {
                font-size: 2rem;
            }

            .module-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .btn-hero {
                width: 100%;
                justify-content: center;
            }

            .section-title {
                font-size: 1.75rem;
            }

            .whatsapp-float {
                width: 55px;
                height: 55px;
                font-size: 26px;
                bottom: 20px;
                right: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
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
                        <a class="nav-link active" href="{{ url('/') }}">
                            <i class="bi bi-house-door"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#modules">
                            <i class="bi bi-grid-3x3-gap"></i>Modules
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">
                            <i class="bi bi-info-circle"></i>About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">
                            <i class="bi bi-envelope"></i>Contact
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    @if (Route::has('login'))
                        @auth
                            <li class="nav-item">
                                <a href="{{ url('/dashboard') }}" class="btn-nav-primary">
                                    <i class="bi bi-speedometer2"></i>Dashboard
                                </a>
                            </li>
                        @else
                            <li class="nav-item">
                                <a href="{{ route('login') }}" class="nav-link">
                                    <i class="bi bi-box-arrow-in-right"></i>Log in
                                </a>
                            </li>
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a href="{{ route('register') }}" class="btn-nav-primary">
                                        <i class="bi bi-person-plus"></i>Register
                                    </a>
                                </li>
                            @endif
                        @endauth
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-wrapper">
        <div class="container">
            <!-- Hero Section -->
            <section class="hero-section">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <div class="hero-content">
                            <div class="hero-badge">
                                <i class="bi bi-stars"></i>
                                AI-Powered Retail Solution
                            </div>
                            <h1 class="hero-title">
                                Empower Your
                                <span class="highlight">Retail Business</span>
                            </h1>
                            <p class="hero-subtitle">
                                Wing POS provides a modern, seamless, and powerful point of sale solution to streamline your operations and boost your sales with intelligent insights.
                            </p>
                            <div class="hero-buttons">
                                @if (Route::has('login'))
                                    @auth
                                        <a href="{{ url('/dashboard') }}" class="btn-hero btn-primary-glow">
                                            Go to Dashboard <i class="bi bi-arrow-right"></i>
                                        </a>
                                    @else
                                        <a href="{{ route('login') }}" class="btn-hero btn-primary-glow">
                                            Get Started <i class="bi bi-arrow-right"></i>
                                        </a>
                                        <a href="#modules" class="btn-hero btn-outline-glow">
                                            View Modules
                                        </a>
                                    @endauth
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="hero-visual">
                            <div class="glass-card">
                                <div class="feature-item">
                                    <div class="feature-icon" style="color: #ff6b35;">
                                        <i class="bi bi-graph-up-arrow"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-bold">Real-time Analytics</h5>
                                        <small class="text-white-50">Track sales as they happen</small>
                                    </div>
                                </div>
                                <div class="feature-item">
                                    <div class="feature-icon" style="color: #4f46e5;">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-bold">Smart Inventory</h5>
                                        <small class="text-white-50">Never run out of stock</small>
                                    </div>
                                </div>
                                <div class="feature-item">
                                    <div class="feature-icon" style="color: #10b981;">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-bold">Customer Profiles</h5>
                                        <small class="text-white-50">Build lasting relationships</small>
                                    </div>
                                </div>
                                <div class="feature-item">
                                    <div class="feature-icon" style="color: #a855f7;">
                                        <i class="bi bi-cpu-fill"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-bold">AI Driven System</h5>
                                        <small class="text-white-50">Smart business insights</small>
                                    </div>
                                </div>
                                <div class="feature-item">
                                    <div class="feature-icon" style="color: #f59e0b;">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-bold">Trade-in System</h5>
                                        <small class="text-white-50">Device swap & value tracking</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Modules Section -->
            <section id="modules" class="section-spacing">
                <div class="section-header">
                    <span class="section-tag">Powerful Modules</span>
                    <h2 class="section-title">Everything You Need to Scale</h2>
                    <p class="section-description">Explore the specialized tools designed to handle every aspect of your retail operations with precision and efficiency.</p>
                </div>

                <div class="module-grid">
                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-display"></i>
                        </div>
                        <h4 class="module-name">Real-time POS</h4>
                        <p class="module-description">Fast and intuitive checkout interface with support for multi-payments, discounts, and holds.</p>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <h4 class="module-name">Smart Inventory</h4>
                        <p class="module-description">Advanced stock tracking with reorder alerts, movement history, and automated cost calculation.</p>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-receipt-cutoff"></i>
                        </div>
                        <h4 class="module-name">Invoice Management</h4>
                        <p class="module-description">Professional invoicing with receipt printing, digital delivery, and status tracking.</p>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-cpu-fill"></i>
                        </div>
                        <h4 class="module-name">AI Driven System</h4>
                        <p class="module-description">AI-powered demand forecasting, product analysis, and smart inventory insights to optimize your business.</p>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <h4 class="module-name">Loan Tracking</h4>
                        <p class="module-description">Comprehensive loan and credit management for trust-based customer relationships.</p>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-pie-chart-fill"></i>
                        </div>
                        <h4 class="module-name">Financial Reports</h4>
                        <p class="module-description">Detailed P&L statements, sales reports, and audit logs to keep your books clean.</p>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <h4 class="module-name">Expense Control</h4>
                        <p class="module-description">Track business expenses by category with approval workflows for transparency.</p>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-megaphone"></i>
                        </div>
                        <h4 class="module-name">Promotions</h4>
                        <p class="module-description">Dynamic pricing rules and promotional campaigns to drive more traffic to your store.</p>
                    </div>

                    <div class="module-card">
                        <div class="module-icon" style="color: #a855f7;">
                            <i class="bi bi-phone-vibrate"></i>
                        </div>
                        <h4 class="module-name">Electronics & Trade-in</h4>
                        <p class="module-description">Specialized IMEI tracking for electronics with a complete trade-in system for device swaps and value calculation.</p>
                    </div>
                </div>
            </section>

            <!-- About Section -->
            <section id="about" class="section-spacing">
                <div class="glass-card">
                    <div class="row align-items-center g-5">
                        <div class="col-lg-6">
                            <span class="section-tag">Our Vision</span>
                            <h2 class="section-title text-white mb-4">Modernizing Retail Across Africa</h2>
                            <p class="text-white-50" style="font-size: 1.05rem; line-height: 1.8;">
                                Wing POS is built with the local retailer in mind. From small electronics shops to busy supermarkets, our platform provides the tools needed to manage inventory, track sales, and grow with confidence using AI-driven insights.
                            </p>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-value" style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">99.9%</div>
                                        <div class="stat-label">Uptime</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-value" style="background: linear-gradient(135deg, #10b981 0%, #34d399 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">500+</div>
                                        <div class="stat-label">Retailers</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-value" style="background: var(--secondary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">24/7</div>
                                        <div class="stat-label">Support</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-value" style="background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">100%</div>
                                        <div class="stat-label">Satisfaction</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Contact Section -->
            <section id="contact" class="section-spacing">
                <div class="section-header">
                    <span class="section-tag">Get In Touch</span>
                    <h2 class="section-title">We're Here to Help</h2>
                    <p class="section-description">Have questions? Our support team is ready to assist you with installation, pricing, or technical support.</p>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="glass-card text-center">
                            <div class="feature-icon mx-auto mb-4" style="color: #25D366; width: 80px; height: 80px; font-size: 2.5rem;">
                                <i class="bi bi-whatsapp"></i>
                            </div>
                            <h4 class="text-white mb-3 fw-bold" style="font-size: 1.5rem;">WhatsApp Support</h4>
                            <p class="text-white-50 mb-4" style="font-size: 1.05rem;">
                                Chat with us instantly for queries about installation, pricing, or technical support. We're available 24/7 to help you succeed.
                            </p>
                            <a href="https://wa.me/254725830546?text=Hello%20Wing%20POS!%20I'm%20interested%20in%20your%20services." target="_blank" class="btn-hero btn-primary-glow" style="background: linear-gradient(135deg, #25D366 0%, #20BA5A 100%);">
                                Chat on WhatsApp <i class="bi bi-whatsapp ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <p class="mb-0">&copy; {{ date('Y') }} Wing POS. All rights reserved. Built with ❤️ for African Retailers.</p>
            </div>
        </footer>
    </div>

    <!-- Floating WhatsApp Button -->
    <a href="https://wa.me/254725830546?text=Hello%20Wing%20POS!%20I'm%20interested%20in%20your%20services." class="whatsapp-float" target="_blank" title="Chat on WhatsApp" aria-label="Contact us on WhatsApp">
        <span class="whatsapp-tooltip">Chat with us!</span>
        <i class="bi bi-whatsapp"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offset = 80;
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Active nav link on scroll
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('.nav-link');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (pageYOffset >= (sectionTop - 100)) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>