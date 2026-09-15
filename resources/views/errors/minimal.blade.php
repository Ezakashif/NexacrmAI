<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') — {{ config('app.name', 'NexaCRM') }}</title>
    <link rel="icon" href="{{ asset(config('marketing.assets.favicon', 'branding/nexacrm-mark.svg')) }}" type="image/svg+xml">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
        }
        .error-card {
            width: min(32rem, calc(100vw - 2rem));
            padding: 2rem 1.75rem;
            border: 1px solid #1e293b;
            border-radius: 1rem;
            background: #111827;
            text-align: center;
        }
        .error-brand {
            margin: 0 0 1.25rem;
            font-size: 0.85rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #38bdf8;
        }
        .error-code {
            margin: 0;
            font-size: 3.5rem;
            line-height: 1;
            color: #f8fafc;
        }
        .error-title {
            margin: 0.75rem 0 0.5rem;
            font-size: 1.35rem;
        }
        .error-message {
            margin: 0 0 1.5rem;
            color: #94a3b8;
            line-height: 1.5;
        }
        .error-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
        }
        .error-actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.5rem;
            padding: 0 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
        }
        .error-actions a.primary {
            background: #0284c7;
            color: #fff;
        }
        .error-actions a.secondary {
            border: 1px solid #334155;
            color: #e2e8f0;
        }
    </style>
</head>
<body>
    <main class="error-card">
        <p class="error-brand">{{ config('app.name', 'NexaCRM') }}</p>
        <p class="error-code">@yield('code')</p>
        <h1 class="error-title">@yield('title')</h1>
        <p class="error-message">@yield('message')</p>
        <div class="error-actions">
            <a class="primary" href="{{ url('/') }}">Go to home</a>
            <a class="secondary" href="{{ route('login') }}">Sign in</a>
        </div>
    </main>
</body>
</html>
