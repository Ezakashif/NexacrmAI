<x-marketing-auth-layout
    title="Forgot password"
    heading="Forgot your password?"
    subheading="Enter your work email and we’ll send a secure reset link if an account exists."
>
    @if (session('status'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" novalidate>
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

        <x-marketing.button type="submit" class="w-full" size="lg">
            Email reset link
        </x-marketing.button>
    </form>

    <p class="mt-4 text-center text-xs leading-relaxed text-slate-500">
        For security, we won’t confirm whether that email is registered.
        Reset links expire after {{ config('auth.passwords.users.expire', 60) }} minutes.
    </p>

    <p class="mt-6 text-center text-sm text-slate-600">
        <a href="{{ route('login') }}" class="font-semibold text-sky-700 hover:text-sky-800">Back to sign in</a>
    </p>
</x-marketing-auth-layout>
