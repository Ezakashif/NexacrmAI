<x-marketing-auth-layout
    title="Verify email"
    heading="Verify your email"
    subheading="Confirm your email address to unlock your Algos workspace."
>
    @if ($errors->has('email'))
        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
            {{ $errors->first('email') }}
        </div>
    @endif

    @if (session('status') == 'verification-link-sent')
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
            A new verification link has been sent{{ ! empty($email) ? ' to '.$email : '' }}.
        </div>
    @endif

    @if (! empty($mailDeliveryDisabled))
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-relaxed text-amber-900" role="status">
            Outbound email is currently using the <code class="font-semibold">{{ config('mail.default') }}</code> mailer,
            so messages are not delivered to a real inbox. Set <code class="font-semibold">MAIL_MAILER=smtp</code>
            (Mailpit, SES, Postmark, etc.) in <code class="font-semibold">.env</code>, or use the preview link below.
        </div>
    @endif

    <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-relaxed text-slate-600">
        We sent a secure link to
        <span class="font-semibold text-slate-900">{{ $email ?? 'your email address' }}</span>.
        Open it to finish setup. Links expire for security—request another if needed.
    </div>

    @if (! empty($verificationPreviewUrl))
        <div class="mb-6 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm leading-relaxed text-slate-700">
            <p class="font-semibold text-slate-900">Verification preview link</p>
            <p class="mt-1 text-slate-600">Use this while mail delivery is disabled:</p>
            <a href="{{ $verificationPreviewUrl }}" class="mt-2 inline-flex break-all font-semibold text-sky-700 underline underline-offset-2 hover:text-sky-900">
                Verify email address
            </a>
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="space-y-3">
        @csrf
        <x-marketing.button type="submit" class="w-full" size="lg">
            Resend verification email
        </x-marketing.button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <x-marketing.button type="submit" variant="secondary" class="w-full">
            Log out
        </x-marketing.button>
    </form>
</x-marketing-auth-layout>
