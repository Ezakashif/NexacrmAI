<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="stylesheet" href="{{ asset('css/crm-tokens.css') }}">
        <link rel="stylesheet" href="{{ asset('css/crm-app.css') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ asset('css/password-field.css') }}">
    </head>
    <body class="font-sans text-gray-900 antialiased login-page">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0" style="background: linear-gradient(160deg, #eff6ff 0%, #f8fafc 45%, #ecfeff 100%);">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white overflow-hidden sm:rounded-lg" style="border: 1px solid var(--crm-border); box-shadow: var(--crm-shadow-hover); border-radius: var(--crm-radius);">
                {{ $slot }}
            </div>
        </div>
        <script src="{{ asset('js/password-toggle.js') }}" defer></script>
    </body>
</html>
