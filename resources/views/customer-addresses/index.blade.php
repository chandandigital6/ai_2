<x-layouts::app title="My Delivery Addresses">
<div class="mx-auto max-w-5xl space-y-6 p-6"><h1 class="text-3xl font-bold">My Delivery Addresses</h1>
@if(session('success'))<div class="rounded-xl bg-emerald-50 p-3 text-emerald-800">{{ session('success') }}</div>@endif
@if($errors->any())<div class="rounded-xl bg-red-50 p-3 text-red-900">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<div class="grid gap-4 md:grid-cols-2">@forelse($addresses as $a)
<div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700"><div class="flex justify-between"><h2 class="font-bold">{{ $a->label }} @if($a->is_default)<span class="rounded-full bg-emerald-100 px-2 py-1 text-xs text-emerald-900">Default</span>@endif</h2></div>
<p class="mt-2 text-sm">{{ $a->recipient_name }} · {{ $a->phone }}</p><p class="mt-1 text-sm text-zinc-500">{{ $a->address }}, {{ $a->city?->name }}, {{ $a->state?->name }} - {{ $a->pincode }}</p>
<div class="mt-4 flex flex-wrap gap-2">@unless($a->is_default)<form method="POST" action="{{ route('growya.addresses.default',$a) }}">@csrf @method('PATCH')<button class="rounded-lg bg-emerald-700 px-3 py-2 text-sm text-white">Set Default</button></form>@endunless
<form method="POST" action="{{ route('growya.addresses.destroy',$a) }}" onsubmit="return confirm('Delete this saved address?')">@csrf @method('DELETE')<button class="rounded-lg border px-3 py-2 text-sm">Delete</button></form></div>
<details class="mt-4"><summary class="cursor-pointer text-sm font-semibold">Edit address</summary>
<form method="POST" action="{{ route('growya.addresses.update',$a) }}" class="mt-3 grid gap-3">@csrf @method('PUT')
@include('customer-addresses.partials.fields',['item'=>$a])
<button class="rounded-lg bg-emerald-700 p-3 text-white">Save Changes</button></form></details>
</div>@empty <p class="text-zinc-500">No saved addresses yet.</p>@endforelse</div>
<div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700"><h2 class="mb-4 text-xl font-bold">Add New Address</h2><form method="POST" action="{{ route('growya.addresses.store') }}" class="grid gap-3">@csrf
@include('customer-addresses.partials.fields',['item'=>null])
<button class="rounded-lg bg-emerald-700 p-3 font-bold text-white">Save Address</button></form></div>
</div></x-layouts::app>
