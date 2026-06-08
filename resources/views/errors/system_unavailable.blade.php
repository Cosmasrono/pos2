<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Unavailable - Wing POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; padding: 40px; max-width: 500px; }
        .icon-circle { width: 80px; height: 80px; background: #fff3cd; color: #ffc107; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px; }
        h1 { font-weight: 800; color: #1a1a1a; margin-bottom: 15px; }
        p { color: #6c757d; line-height: 1.6; }
        .btn-primary { border-radius: 50px; padding: 12px 30px; font-weight: 600; background-color: #0d6efd; border: none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-circle">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <h1>System Offline</h1>
        <p>The system has been temporarily deactivated for maintenance or administrative reasons. Please contact the administrator for more information.</p>
        <div class="mt-4">
            <a href="{{ route('login') }}" class="btn btn-primary">Administrator Login</a>
        </div>
        <div class="mt-3">
            <small class="text-muted">&copy; {{ date('Y') }} Wing POS. All rights reserved.</small>
        </div>
    </div>
</body>
</html>
