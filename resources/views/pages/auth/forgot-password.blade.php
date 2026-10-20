{{-- <x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                {{ __('Email password reset link') }}
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('Or, return to') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth> --}}


<x-layouts::auth :title="__('Forgot password')">
    <h1 class="text-3xl font-black tracking-tight">Forgot password?</h1>
    <p class="mt-2 text-sm text-slate-500">Enter your registered email to receive a reset link.</p>
    @if (session('status'))
        <div role="status" class="mt-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">@csrf
        <div><label for="email" class="mb-2 block text-sm font-bold">Email address</label><input id="email"
                name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15">
        </div>
        <button type="submit" data-test="email-password-reset-link-button"
            class="w-full rounded-xl bg-emerald-600 px-5 py-3 font-extrabold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600/30">Email
            password reset link</button>
    </form>
    <p class="mt-7 text-center text-sm"><a href="{{ route('login') }}"
            class="font-bold text-emerald-700 hover:underline">← Back to log in</a></p>
</x-layouts::auth>
