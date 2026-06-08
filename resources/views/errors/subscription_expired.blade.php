<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Expired - Wing POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; padding: 2rem; max-width: 550px; width: 100%; }
        .icon-circle { width: 70px; height: 70px; background: #f8d7da; color: #dc3545; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 35px; margin: 0 auto 20px; }
        @media (min-width: 768px) {
            .card { padding: 3.5rem; }
            .icon-circle { width: 90px; height: 90px; font-size: 45px; }
        }
        h1 { font-weight: 800; color: #1a1a1a; margin-bottom: 15px; font-size: 1.75rem; }
        @media (min-width: 768px) { h1 { font-size: 2.25rem; } }
        p { color: #6c757d; line-height: 1.6; font-size: 1rem; }
        .btn-primary { border-radius: 50px; padding: 12px 30px; font-weight: 600; background-color: #0d6efd; border: none; width: 100%; }
        @media (min-width: 768px) { .btn-primary { width: auto; } }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-circle">
            <i class="bi bi-clock-history"></i>
        </div>
        <h1>Subscription Expired</h1>
        <p>Your annual system subscription has expired. Please contact the system administrator or the service provider to renew your license and regain access.</p>
        <div class="mt-4">
            <a href="{{ route('login') }}" class="btn btn-primary">Administrator Login</a>
        </div>
        <div class="mt-3">
            <small class="text-muted">&copy; {{ date('Y') }} Wing POS. All rights reserved.</small>
        </div>
    </div>
</body>
</html>
