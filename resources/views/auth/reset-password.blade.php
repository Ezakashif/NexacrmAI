<x-marketing-auth-layout
    title="Reset password"
    heading="Reset password"
    subheading="Choose a new password for your Algos account. You’ll sign in again after resetting."
>
    <form method="POST" action="{{ route('password.store') }}" class="space-y-5" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="mk-label">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $request->email) }}"
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
            <label for="password" class="mk-label">New password</label>
            <x-password-input
                name="password"
                id="password"
                variant="marketing"
                autocomplete="new-password"
                :required="true"
                class="@error('password') border-red-400 @enderror"
            />
            <p class="mt-1.5 text-xs text-slate-500">
                At least 10 characters, with upper and lower case, a number, and a symbol.
            </p>
            @error('password')
                <p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mk-label">Confirm password</label>
            <x-password-input
                name="password_confirmation"
                id="password_confirmation"
                variant="marketing"
                autocomplete="new-password"
                :required="true"
                class="@error('password_confirmation') border-red-400 @enderror"
            />
            @error('password_confirmation')
                <p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <x-marketing.button type="submit" class="w-full" size="lg">
            Reset password
        </x-marketing.button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        <a href="{{ route('password.request') }}" class="font-semibold text-sky-700 hover:text-sky-800">Request a new reset link</a>
        <span class="mx-2 text-slate-300">·</span>
        <a href="{{ route('login') }}" class="font-semibold text-sky-700 hover:text-sky-800">Back to sign in</a>
    </p>
</x-marketing-auth-layout>
