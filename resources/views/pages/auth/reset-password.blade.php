{{-- <x-layouts::auth :title="__('Reset password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Reset password')" :description="__('Please enter your new password below')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <!-- Email Address -->
            <flux:input
                name="email"
                value="{{ request('email') }}"
                :label="__('Email')"
                type="email"
                required
                autocomplete="email"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="reset-password-button">
                    {{ __('Reset password') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth> --}}






<x-layouts::auth :title="__('Reset password')">
    <h1 class="text-3xl font-black tracking-tight">Set a new password</h1>
    <p class="mt-2 text-sm text-slate-500">Choose a new secure password for your account.</p>
    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-700">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form method="POST" action="{{ route('password.update') }}" class="mt-7 space-y-5">@csrf
        <input type="hidden" name="token" value="{{ request()->route('token') }}">
        <div><label for="email" class="mb-2 block text-sm font-bold">Email address</label><input id="email"
                name="email" type="email" value="{{ old('email', request('email')) }}" required autocomplete="email"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15">
        </div>
        <div><label for="password" class="mb-2 block text-sm font-bold">New password</label><input id="password"
                name="password" type="password" required autocomplete="new-password"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15">
        </div>
        <div><label for="password_confirmation" class="mb-2 block text-sm font-bold">Confirm new password</label><input
                id="password_confirmation" name="password_confirmation" type="password" required
                autocomplete="new-password"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15">
        </div>
        <button type="submit" data-test="reset-password-button"
            class="w-full rounded-xl bg-emerald-600 px-5 py-3 font-extrabold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600/30">Reset
            password</button>
    </form>
</x-layouts::auth>
