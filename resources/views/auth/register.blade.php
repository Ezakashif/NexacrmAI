<x-marketing-auth-layout
    title="Create workspace"
    heading="Create your workspace"
    subheading="Set up your company account and first admin user."
    :wide="true"
>
    <form method="POST" action="{{ route('register') }}" class="space-y-5" novalidate>
        @csrf

        <div>
            <label for="company_name" class="mk-label">Company name</label>
            <input
                id="company_name"
                type="text"
                name="company_name"
                value="{{ old('company_name') }}"
                required
                autofocus
                autocomplete="organization"
                class="mk-input @error('company_name') border-red-400 @enderror"
                placeholder="Acme Corp"
            >
            @error('company_name')
                <p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="name" class="mk-label">Your name</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autocomplete="name"
                class="mk-input @error('name') border-red-400 @enderror"
                placeholder="Alex Morgan"
            >
            @error('name')
                <p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="mk-label">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="username"
                class="mk-input @error('email') border-red-400 @enderror"
                placeholder="alex@company.com"
            >
            @error('email')
                <p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mk-label">Password</label>
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
            />
        </div>

        <x-marketing.button type="submit" class="w-full" size="lg">
            Create account
        </x-marketing.button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        <a href="{{ \App\Support\MarketingCta::liveDemoHref() }}" class="font-semibold text-sky-700 hover:text-sky-800">Try Live Demo</a>
    </p>

    <p class="mt-3 text-center text-sm text-slate-600">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-sky-700 hover:text-sky-800">Sign in</a>
    </p>
</x-marketing-auth-layout>
