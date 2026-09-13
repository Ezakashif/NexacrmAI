<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance — {{ $platformName }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top, #1e293b, #020617 60%);
            color: #e2e8f0;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .box {
            max-width: 480px;
            padding: 2rem;
            border: 1px solid #1f2937;
            border-radius: 1rem;
            background: rgba(15, 23, 42, 0.92);
            text-align: center;
        }
        h1 { font-size: 1.5rem; color: #fff; margin-bottom: 0.75rem; }
        p { color: #94a3b8; margin-bottom: 0; }
        a { color: #38bdf8; }
    </style>
</head>
<body>
<div class="box">
    <h1>{{ $platformName }} is under maintenance</h1>
    <p>We're performing scheduled platform updates. Please try again shortly.</p>
    <p class="mt-3 mb-0"><a href="{{ route('login') }}">Super Admin login</a></p>
</div>
</body>
</html>
