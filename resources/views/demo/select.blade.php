<x-marketing-auth-layout
    title="Try Live Demo"
    heading="Explore NexaCRM AI"
    subheading="Enter your work email, then choose a role. This is a shared demo workspace that resets daily."
    :wide="true"
>
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('demo.start') }}" class="space-y-5" novalidate>
        @csrf

        <div class="space-y-4 rounded-xl border border-slate-200 bg-slate-50/80 p-4">
            <div>
                <label for="email" class="mk-label">Work email <span class="text-sky-700">*</span></label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    class="mk-input @error('email') border-red-400 @enderror"
                    placeholder="alex@company.com"
                >
                @error('email')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="mk-label">Name <span class="text-slate-400">(optional)</span></label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        autocomplete="name"
                        class="mk-input @error('name') border-red-400 @enderror"
                        placeholder="Alex Morgan"
                    >
                </div>
                <div>
                    <label for="company" class="mk-label">Company <span class="text-slate-400">(optional)</span></label>
                    <input
                        id="company"
                        name="company"
                        type="text"
                        value="{{ old('company') }}"
                        autocomplete="organization"
                        class="mk-input @error('company') border-red-400 @enderror"
                        placeholder="Acme Corp"
                    >
                </div>
            </div>

            <label class="flex items-start gap-3 text-sm text-slate-600">
                <input
                    type="checkbox"
                    name="contact_consent"
                    value="1"
                    class="mt-1 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                    @checked(old('contact_consent'))
                >
                <span>It’s okay to contact me about NexaCRM AI.</span>
            </label>
        </div>

        <div>
            <p class="mb-3 text-sm font-semibold text-slate-800">Choose a demo role</p>
            <div class="space-y-3" role="radiogroup" aria-label="Demo role">
                @foreach ($personas as $key => $persona)
                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm transition hover:border-sky-300 hover:bg-sky-50 has-[:checked]:border-sky-400 has-[:checked]:bg-sky-50"
                    >
                        <input
                            type="radio"
                            name="persona"
                            value="{{ $key }}"
                            class="mt-1 border-slate-300 text-sky-600 focus:ring-sky-500"
                            @checked(old('persona', 'admin') === $key)
                            required
                        >
                        <span>
                            <span class="block text-base font-semibold text-slate-900">{{ $persona['label'] }}</span>
                            <span class="mt-1 block text-sm text-slate-600">{{ $persona['description'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('persona')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="mk-btn mk-btn-primary mk-btn-md w-full justify-center">
            Start live demo
        </button>

        <button
            type="submit"
            name="anonymous"
            value="1"
            class="w-full text-center text-sm font-medium text-slate-500 underline-offset-2 hover:text-slate-700 hover:underline"
        >
            Continue without email
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-sky-700 hover:text-sky-800">Sign in</a>
    </p>
</x-marketing-auth-layout>
