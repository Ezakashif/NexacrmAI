<x-marketing-auth-layout
    title="Sign in"
    heading="Sign in"
    subheading="Enter your credentials to continue to your Algos workspace."
>
    @if (session('status'))
        <div class="mb-5 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
        @csrf

        <div>
            <label for="email" class="mk-label">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="mk-input @error('email') border-red-400 @enderror"
                placeholder="you@company.com"
            >
            @error('email')
                <p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="mb-1.5 flex items-center justify-between gap-3">
                <label for="password" class="mk-label mb-0">Password</label>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-sky-700 hover:text-sky-800">
                    Forgot password?
                </a>
            </div>
            <x-password-input
                name="password"
                id="password"
                variant="marketing"
                autocomplete="current-password"
                :required="true"
                class="@error('password') border-red-400 @enderror"
            />
            @error('password')
                <p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2">
            <input
                id="remember"
                type="checkbox"
                name="remember"
                value="1"
                class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                {{ old('remember') ? 'checked' : '' }}
            >
            <label for="remember" class="text-sm text-slate-600">Remember me</label>
        </div>

        <x-marketing.button type="submit" class="w-full" size="lg">
            Sign in
        </x-marketing.button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        <a href="{{ \App\Support\MarketingCta::liveDemoHref() }}" class="font-semibold text-sky-700 hover:text-sky-800">Try Live Demo</a>
    </p>

    @if (! empty($registrationEnabled))
        <p class="mt-3 text-center text-sm text-slate-600">
            New to Algos?
            <a href="{{ route('register') }}" class="font-semibold text-sky-700 hover:text-sky-800">Create a workspace</a>
        </p>
    @endif
</x-marketing-auth-layout>
