@props(['label', 'value', 'note' => null, 'icon' => 'chart', 'tone' => 'green', 'href' => null])
<article class="g-stat">
    <div class="g-stat-top"><span class="g-muted">{{ $label }}</span><span class="g-icon-box g-tone-{{ $tone }}"><x-growya.icon :name="$icon" /></span></div>
    <p class="g-stat-value">{{ $value }}</p>
    <div class="g-stat-bottom"><span>{{ $note }}</span>@if($href)<a href="{{ $href }}" aria-label="Open {{ $label }}"><x-growya.icon name="arrow-up-right" /></a>@endif</div>
</article>
