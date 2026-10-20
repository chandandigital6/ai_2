@props(['value'])
@php
    $tone = match ((string) $value) {
        'approved', 'active', 'paid', 'completed', 'delivered', 'confirmed' => 'green',
        'pending', 'pending_verification', 'processing', 'requested', 'unpaid' => 'amber',
        'rejected', 'cancelled', 'failed', 'inactive' => 'rose',
        default => 'blue',
    };
@endphp
<span class="g-badge g-tone-{{ $tone }}"><span class="g-dot"></span>{{ ucfirst(str_replace('_', ' ', (string) $value)) }}</span>
