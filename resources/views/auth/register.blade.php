<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wing POS - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --secondary-gradient: linear-gradient(135deg, #ff6b35 0%, #ff8555 100%);
        }

        /* Fixed Navbar for Auth Pages */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: transparent;
            backdrop-filter: blur(0px);
            padding: 15px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0);
            z-index: 1000;
            transition: all 0.4s ease;
        }

        .navbar.scrolled {
            background: rgba(15, 15, 30, 0.9);
            backdrop-filter: blur(15px);
            padding: 10px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            color: #fff !important;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 8px 20px !important;
            border-radius: 8px;
        }

        .nav-link:hover, .nav-link.active {
            color: #fff !important;
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-nav-primary {
            background: var(--primary-gradient);
            color: white !important;
            border: none;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
            transition: all 0.3s ease;
        }

        .btn-nav-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.6);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #0f0f1e 50%, #1a1a3e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            padding: 100px 20px 40px 20px;
        }

        /* Animated background elements */
        body::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 107, 53, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            top: -100px;
            right: -100px;
            animation: float 6s ease-in-out infinite;
            z-index: 0;
        }

        body::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(147, 51, 234, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -150px;
            left: -100px;
            animation: float 8s ease-in-out infinite reverse;
            z-index: 0;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(30px); }
        }

        .register-wrapper {
            z-index: 1;
            width: 100%;
            max-width: 1100px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 60px;
        }

        /* Cute Lamp Character */
        .lamp-container {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            flex: 1;
            min-height: 500px;
            position: relative;
        }

        .lamp {
            position: relative;
            width: 120px;
            height: 180px;
            animation: lampGlow 3s ease-in-out infinite;
        }

        .lamp-bulb {
            position: absolute;
            width: 100px;
            height: 90px;
            background: linear-gradient(135deg, #fff9e6 0%, #ffe6cc 100%);
            border-radius: 50% 50% 50% 40%;
            top: 0;
            left: 10px;
            box-shadow: 0 0 40px rgba(255, 200, 100, 0.8), 
                        0 0 60px rgba(255, 150, 0, 0.5),
                        inset -2px -2px 10px rgba(0,0,0,0.1),
                        inset 8px 8px 20px rgba(255, 255, 255, 0.3);
        }

        .lamp-face {
            position: absolute;
            top: 20px;
            left: 15px;
            width: 90px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: space-around;
        }

        .lamp-eye {
            width: 18px;
            height: 18px;
            background: #2c2c2c;
            border-radius: 50%;
            position: relative;
            animation: blink 3s ease-in-out infinite;
        }

        .lamp-eye::after {
            content: '';
            position: absolute;
            width: 6px;
            height: 6px;
            background: white;
            border-radius: 50%;
            top: 3px;
            left: 5px;
        }

        @keyframes blink {
            0%, 49%, 51%, 100% { height: 18px; }
            50% { height: 3px; }
        }

        .lamp-smile {
            position: absolute;
            width: 30px;
            height: 15px;
            border: 2px solid #2c2c2c;
            border-top: none;
            border-radius: 0 0 30px 30px;
            bottom: 10px;
            left: 35px;
        }

        .lamp-pole {
            position: absolute;
            width: 8px;
            height: 120px;
            background: linear-gradient(to bottom, #f5f5f5 0%, #e8e8e8 100%);
            left: 56px;
            top: 85px;
            border-radius: 4px;
            box-shadow: 2px 2px 8px rgba(0,0,0,0.2);
        }

        .lamp-pole::before {
            content: '';
            position: absolute;
            width: 2px;
            height: 60px;
            background: linear-gradient(to bottom, #ffaa00 0%, #ff8800 100%);
            left: 3px;
            top: -30px;
            border-radius: 2px;
            box-shadow: 0 0 8px rgba(255, 170, 0, 0.8);
            animation: cordSway 2s ease-in-out infinite;
        }

        @keyframes cordSway {
            0%, 100% { transform: rotate(0deg); transform-origin: center top; }
            50% { transform: rotate(2deg); transform-origin: center top; }
        }

        .lamp-base {
            position: absolute;
            width: 80px;
            height: 60px;
            background: radial-gradient(ellipse at center, #444 0%, #222 100%);
            border-radius: 50%;
            top: 180px;
            left: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        /* Lamp Switch */
        .lamp-switch {
            position: absolute;
            width: 20px;
            height: 35px;
            background: linear-gradient(to right, #333, #555);
            border-radius: 10px;
            top: 50px;
            right: -25px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.5);
            border: 1px solid #444;
        }

        .lamp-switch:hover {
            background: linear-gradient(to right, #444, #666);
            transform: scale(1.05);
        }

        .lamp-switch::before {
            content: '';
            position: absolute;
            width: 14px;
            height: 14px;
            background: #888;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.5);
        }

        .lamp-switch.on::after {
            content: '';
            position: absolute;
            width: 2px;
            height: 15px;
            background: #ffaa00;
            left: 50%;
            top: -18px;
            transform: translateX(-50%);
            animation: cordPull 0.3s ease;
        }

        @keyframes cordPull {
            0% { height: 0; }
            100% { height: 15px; }
        }

        @keyframes lampGlow {
            0%, 100% { 
                filter: drop-shadow(0 0 30px rgba(255, 150, 0, 0.6)) drop-shadow(0 0 60px rgba(255, 100, 0, 0.3));
            }
            50% { 
                filter: drop-shadow(0 0 50px rgba(255, 180, 0, 0.8)) drop-shadow(0 0 80px rgba(255, 120, 0, 0.5));
            }
        }

        /* Lamp OFF state */
        .lamp.off .lamp-bulb {
            background: linear-gradient(135deg, #4a4a4a 0%, #333333 100%);
            box-shadow: 0 0 10px rgba(100, 100, 100, 0.3), inset -2px -2px 10px rgba(0,0,0,0.3);
        }

        .lamp.off {
            animation: none;
            filter: drop-shadow(0 0 0px rgba(255, 150, 0, 0));
        }

        /* Register Card */
        .register-card {
            flex: 1;
            background: rgba(20, 20, 35, 0.85);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 107, 53, 0.6);
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 0 40px rgba(255, 107, 53, 0.4), 0 20px 60px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            animation: slideUp 0.6s ease-out, cardGlow 3s ease-in-out infinite;
            max-height: 90vh;
            overflow-y: auto;
        }

        .register-card::-webkit-scrollbar {
            width: 6px;
        }

        .register-card::-webkit-scrollbar-track {
            background: rgba(255, 107, 53, 0.1);
            border-radius: 10px;
        }

        .register-card::-webkit-scrollbar-thumb {
            background: rgba(255, 107, 53, 0.5);
            border-radius: 10px;
        }

        @keyframes cardGlow {
            0%, 100% { 
                box-shadow: 0 0 40px rgba(255, 107, 53, 0.4), 0 20px 60px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            }
            50% { 
                box-shadow: 0 0 60px rgba(255, 107, 53, 0.6), 0 20px 60px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.15);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-title {
            text-align: center;
            margin-bottom: 10px;
        }

        .register-title h1 {
            color: #fff;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .register-subtitle {
            color: #b0b0b0;
            font-size: 14px;
            text-align: center;
            margin-bottom: 35px;
        }

        .form-label {
            font-weight: 600;
            color: #e0e0e0;
            font-size: 13px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.08);
            border: 1.5px solid rgba(255, 107, 53, 0.3);
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 14px;
            color: #fff;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: #ff6b35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.15);
            outline: none;
            color: #fff;
        }

        .form-check-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1.5px solid rgba(255, 107, 53, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-check-input:checked {
            background: #ff6b35;
            border-color: #ff6b35;
            box-shadow: 0 0 10px rgba(255, 107, 53, 0.5);
        }

        .form-check-label {
            color: #b0b0b0;
            font-size: 13px;
            margin-left: 8px;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .form-check-input:checked ~ .form-check-label {
            color: #ff6b35;
        }

        .btn-register {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 700;
            font-size: 1rem;
            color: white;
            margin-top: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
            width: 100%;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.4);
            filter: brightness(1.1);
            color: white;
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .footer-text {
            text-align: center;
            margin-top: 25px;
            color: #909090;
            font-size: 13px;
        }

        .login-link {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 700;
            transition: color 0.3s ease;
        }

        .login-link:hover {
            color: #4338ca;
            text-decoration: underline;
        }

        .alert {
            border-radius: 12px;
            font-size: 0.875rem;
            border: none;
            padding: 15px 18px;
            margin-bottom: 20px;
            animation: slideDown 0.4s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.15);
            border: 1px solid rgba(76, 175, 80, 0.3);
            color: #81c784;
        }

        .alert-danger {
            background: rgba(244, 67, 54, 0.15);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #ef5350;
        }

        .btn-close {
            filter: invert(1) brightness(2);
            opacity: 0.7;
        }

        .btn-close:hover {
            opacity: 1;
        }

        .divider-text {
            text-align: center;
            color: #606060;
            font-size: 12px;
            font-weight: 600;
            margin: 20px 0;
            position: relative;
        }

        .divider-text::before,
        .divider-text::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(255, 107, 53, 0.3));
        }

        .divider-text::before {
            left: 0;
        }

        .divider-text::after {
            right: 0;
            background: linear-gradient(to left, transparent, rgba(255, 107, 53, 0.3));
        }

        .btn-mpesa {
            background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%);
            border: none;
            border-radius: 12px;
            padding: 14px 20px;
            font-weight: 700;
            font-size: 14px;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(76, 175, 80, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            width: 100%;
        }

        .btn-mpesa:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(76, 175, 80, 0.5);
            background: linear-gradient(135deg, #58D463 0%, #7EC97F 100%);
            color: white;
        }

        .disabled-message {
            text-align: center;
            padding: 60px 20px;
            color: #fff;
        }

        .disabled-message i {
            font-size: 4rem;
            color: #ff6b35;
            margin-bottom: 20px;
        }

        .disabled-message h4 {
            color: #fff;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .disabled-message p {
            color: #b0b0b0;
            margin-bottom: 30px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .register-wrapper {
                flex-direction: column;
                gap: 30px;
            }

            .lamp-container {
                min-height: 300px;
                order: -1;
            }

            .register-card {
                padding: 40px 30px;
            }

            .register-title h1 {
                font-size: 28px;
            }

            .lamp {
                width: 100px;
                height: 150px;
            }

            .lamp-bulb {
                width: 80px;
                height: 70px;
            }

            .lamp-pole {
                height: 100px;
                top: 70px;
            }

            .lamp-base {
                width: 70px;
                height: 50px;
                top: 150px;
            }
        }
    </style>
</head>
<body>
    @include('layouts.partials.guest-nav')


    <div class="register-wrapper">
        <!-- Cute Lamp Character -->
        <div class="lamp-container">
            <div class="lamp" id="lamp">
                <div class="lamp-bulb">
                    <div class="lamp-face">
                        <div class="lamp-eye"></div>
                        <div class="lamp-eye"></div>
                    </div>
                    <div class="lamp-smile"></div>
                </div>
                <div class="lamp-pole">
                    <div class="lamp-switch" id="lampSwitch"></div>
                </div>
                <div class="lamp-base"></div>
            </div>
        </div>

        <!-- Register Form -->
        <div class="register-card">
            @if ($registrationDisabled ?? false)
                <div class="disabled-message">
                    <i class="bi bi-shield-lock"></i>
                    <h4>Registration Closed</h4>
                    <p>The maximum number of Super Admin accounts (2) has been reached. New account registration is currently disabled for security.</p>
                    <a href="{{ route('login') }}" class="btn btn-register">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Back to Login
                    </a>
                </div>
            @else
                <div class="register-title">
                    <h1>Join Us</h1>
                </div>
                <p class="register-subtitle">Create your Wing POS account to get started</p>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill me-2" style="font-size: 18px;"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-circle-fill me-2" style="font-size: 18px; flex-shrink: 0; margin-top: 2px;"></i>
                            <div style="flex-grow: 1;">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form action="{{ route('register') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. John Doe" value="{{ old('name') }}" required autofocus>
                        @error('name')
                            <div class="invalid-feedback d-block" style="color: #ef5350; font-size: 12px; margin-top: 6px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               placeholder="john@example.com" value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback d-block" style="color: #ef5350; font-size: 12px; margin-top: 6px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                       placeholder="••••••••" required>
                                @error('password')
                                    <div class="invalid-feedback d-block" style="color: #ef5350; font-size: 12px; margin-top: 6px;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" 
                                       class="form-control @error('password_confirmation') is-invalid @enderror" 
                                       placeholder="••••••••" required>
                                @error('password_confirmation')
                                    <div class="invalid-feedback d-block" style="color: #ef5350; font-size: 12px; margin-top: 6px;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="role" class="form-label">Account Role</label>
                        <select id="role" name="role" class="form-control @error('role') is-invalid @enderror" required>
                            <option value="super_admin" selected>Super Admin</option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback d-block" style="color: #ef5350; font-size: 12px; margin-top: 6px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-register">
                        <i class="bi bi-person-plus-fill me-2"></i>Create Account
                    </button>
                </form>

                <div class="footer-text">
                    Already have an account? <a href="{{ route('login') }}" class="login-link">Sign in here</a>
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const lamp = document.querySelector('#lamp');
        const lampSwitch = document.querySelector('#lampSwitch');
        const form = document.querySelector('form');
        let lampIsOn = true;

        // Toggle lamp on/off with switch
        if (lampSwitch) {
            lampSwitch.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                lampIsOn = !lampIsOn;
                
                if (lampIsOn) {
                    lamp.classList.remove('off');
                    lampSwitch.classList.add('on');
                } else {
                    lamp.classList.add('off');
                    lampSwitch.classList.remove('on');
                }
            });
        }

        // Lamp glows when form is focused
        if (form) {
            form.addEventListener('focus', () => {
                if (lampIsOn) {
                    lamp.style.filter = 'drop-shadow(0 0 50px rgba(255, 150, 0, 0.8))';
                }
            }, true);

            form.addEventListener('blur', () => {
                if (lampIsOn) {
                    lamp.style.filter = 'drop-shadow(0 0 20px rgba(255, 150, 0, 0.4))';
                }
            }, true);
        }

        // Add CSRF token to meta tag if not exists
        if (!document.querySelector('meta[name="csrf-token"]')) {
            const token = document.querySelector('input[name="_token"]')?.value;
            if (token) {
                const meta = document.createElement('meta');
                meta.name = 'csrf-token';
                meta.content = token;
                document.head.appendChild(meta);
            }
        }

        // Navbar Scroll Effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
