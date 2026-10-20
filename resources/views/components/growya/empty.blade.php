@props(['title', 'description' => '', 'icon' => 'box', 'href' => null, 'action' => 'Explore marketplace'])
<div class="g-empty"><span class="g-icon-box"><x-growya.icon :name="$icon" /></span><h3>{{ $title }}</h3><p>{{ $description }}</p>@if($href)<a class="g-btn g-btn-secondary" href="{{ $href }}">{{ $action }}<x-growya.icon name="arrow-right" /></a>@endif</div>
