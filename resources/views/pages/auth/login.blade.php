<x-layouts::auth :title="__('Log in')">
    <h1>Welcome back.</h1><p class="mt-2 text-sm text-slate-500">Your next discovery starts here.</p>
    @if(session('status'))<div role="status" class="g-alert g-tone-green mt-5">{{ session('status') }}</div>@endif
    @if($errors->any())<div role="alert" id="login-errors" class="g-alert g-tone-rose mt-5">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5">@csrf
        <div><label for="email" class="mb-2 block">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="you@example.com" @if($errors->has('email')) aria-invalid="true" aria-describedby="login-errors" @endif class="w-full border bg-white px-4 py-3 text-slate-900 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15"></div>
        <div><div class="mb-2 flex items-center justify-between gap-2"><label for="password">Password</label>@if(Route::has('password.request'))<a href="{{ route('password.request') }}" class="text-xs font-semibold text-emerald-700">Forgot password?</a>@endif</div><div class="g-password"><input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Enter your password" class="w-full border bg-white px-4 py-3 text-slate-900 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15"><button type="button" class="g-password-toggle" data-g-password-toggle aria-controls="password" aria-pressed="false">Show</button></div></div>
        <label class="flex items-center gap-2 text-slate-600"><input type="checkbox" name="remember" value="1" @checked(old('remember')) class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">Keep me signed in</label>
        <button type="submit" class="g-btn g-btn-primary w-full" data-test="login-button">Log in to Growya<x-growya.icon name="arrow-right" /></button>
    </form>
    @if(Route::has('register'))<p class="mt-7 text-center text-xs text-slate-600">New around here? <a href="{{ route('register') }}" class="font-semibold text-emerald-700">Create an account</a></p>@endif
</x-layouts::auth>
