<x-layouts::auth :title="__('Create account')">
    <h1>A world of local possibilities.</h1><p class="mt-2 text-sm text-slate-500">Create your Growya account to get started.</p>
    @if($errors->any())<div role="alert" id="register-errors" class="g-alert g-tone-rose mt-5"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('register.store') }}" class="mt-7 space-y-4">@csrf
        <fieldset class="grid grid-cols-2 gap-3"><legend class="mb-2 font-semibold">I am registering for</legend>
            @foreach(['personal' => 'Personal account', 'business' => 'My business'] as $value => $label)
                <label class="flex cursor-pointer items-center gap-2 rounded-xl border p-4"><input type="radio" name="account_type" value="{{ $value }}" @checked(old('account_type', 'personal') === $value) required> {{ $label }}</label>
            @endforeach
        </fieldset>
        @foreach([['name','Full name','text','name','Your full name'],['email','Email address','email','email','you@example.com'],['phone','Mobile number','tel','tel','10-digit mobile number']] as [$field, $label, $type, $autocomplete, $placeholder])<div><label for="{{ $field }}" class="mb-2 block">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field) }}" required autocomplete="{{ $autocomplete }}" placeholder="{{ $placeholder }}" @if($field === 'name') autofocus @endif @if($field === 'phone') inputmode="numeric" pattern="[6-9][0-9]{9}" maxlength="10" @endif @if($errors->has($field)) aria-invalid="true" aria-describedby="register-errors" @endif class="w-full border bg-white px-4 py-3 text-slate-900 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15"></div>@endforeach
        @foreach([['password','Password'],['password_confirmation','Confirm password']] as [$field, $label])<div><label for="{{ $field }}" class="mb-2 block">{{ $label }}</label><div class="g-password"><input id="{{ $field }}" name="{{ $field }}" type="password" required autocomplete="new-password" class="w-full border bg-white px-4 py-3 text-slate-900 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15"><button type="button" class="g-password-toggle" data-g-password-toggle aria-controls="{{ $field }}" aria-pressed="false">Show</button></div></div>@endforeach
        <div><label for="referral_code" class="mb-2 block">Referral code (optional)</label><input id="referral_code" name="referral_code" type="text" maxlength="20" value="{{ old('referral_code', request('ref')) }}" placeholder="Enter friend's referral code" class="w-full border bg-white px-4 py-3 text-slate-900 outline-none focus:border-emerald-600"></div>
        <div class="g-alert g-tone-green" style="font-size:11px">Have a business? Register your showroom after signing in. It becomes public once your application is approved.</div>
        <button type="submit" class="g-btn g-btn-primary w-full" data-test="register-user-button">Create account<x-growya.icon name="arrow-right" /></button>
    </form>
    <p class="mt-6 text-center text-xs text-slate-600">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-emerald-700">Log in</a></p>
    <a href="{{ route('public.marketplace.index') }}" class="g-btn mt-5 w-full border border-slate-300 text-slate-700">Continue as guest</a>
    <p class="mt-2 text-center text-xs text-slate-500">Browse businesses, services and products freely. Sign in to send an enquiry or buy.</p>
</x-layouts::auth>
