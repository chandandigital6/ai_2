@include('products.partials.style')
@php
    $productAccess = app(\App\Services\BusinessAccessService::class);
    $productCan = fn($p) => $productAccess->can(auth()->user(), $business, $p);
    $tabs = [
        ['Overview','businesses.products.show','products.view'],
        ['Details','businesses.products.edit','products.update'],
        ['Photos','businesses.products.gallery.index','products.view'],
        ['Attributes & options','businesses.products.attributes.index','products.view'],
        ['Variants','businesses.products.variants.index','products.view'],
        ['Tags','businesses.products.tags.index','products.view'],
        ['Stock','businesses.products.inventory.index','inventory.view'],
        ['QR code','businesses.products.qr.show','products.view'],
    ];
@endphp
<header class="pc-heading"><div><span class="pc-kicker">{{ $business->name }} · PRODUCT WORKSPACE</span><h1>{{ $heading ?? ($product->exists ? $product->name : 'Add a product') }}</h1><p>{{ $intro ?? 'Keep product details, options and stock organised in one place.' }}</p></div><a class="pc-button pc-secondary" href="{{ route('businesses.products.index',$business) }}">← All products</a></header>
@if($product->exists)
<nav class="pc-nav" aria-label="Product sections">@foreach($tabs as [$label,$route,$permission])@if($productCan($permission))<a href="{{ route($route,[$business,$product]) }}" @if(request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>@endif @endforeach</nav>
@endif
@if(session('success'))<div class="pc-alert pc-success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="pc-alert pc-error" role="alert"><strong>Please correct these details:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
