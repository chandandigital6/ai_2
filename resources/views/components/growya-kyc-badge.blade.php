@props(['business'])
@php
    $latestKyc = \App\Models\GrowyaKycSubmission::query()
        ->where('business_id', $business->id)
        ->latest('id')
        ->first(['status', 'reverify_by']);
    $verifiedKyc = $latestKyc?->status === 'approved'
        && (!$latestKyc->reverify_by || \Illuminate\Support\Carbon::parse($latestKyc->reverify_by)->endOfDay()->isFuture());
@endphp
@if($verifiedKyc)
    <span {{ $attributes->class(['inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800']) }} title="Business documents reviewed by Growya">
        ✓ KYC Verified
    </span>
@endif
