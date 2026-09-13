<x-marketing-auth-layout
    title="Accept invitation"
    heading="Join {{ $invitation->company?->name ?? config('app.name') }}"
    subheading="Hi {{ $invitation->name }}, set a password to activate {{ $invitation->email }}."
>
    <form method="POST" action="{{ route('invitations.accept.store', $invitation->token) }}" class="space-y-5" novalidate>
        @csrf

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

        <button type="submit" class="mk-btn-primary w-full">Activate account</button>
    </form>
</x-marketing-auth-layout>
