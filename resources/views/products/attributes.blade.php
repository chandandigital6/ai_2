<x-layouts::app :title="'Attributes & Options - '.$product->name" :business="$business">
@php $access=app(\App\Services\BusinessAccessService::class); $canEdit=$access->can(auth()->user(),$business,'products.update'); @endphp
<div class="pc-workspace">@include('products.partials.header',['heading'=>'Choose available attributes & options','intro'=>$product->name.' · Select the values this product offers.'])
<section class="pc-card"><div class="pc-compare"><div><h3>1. Attribute</h3><p>A type of detail or choice.<br>Example: Colour or Size.</p></div><div><h3>2. Values</h3><p>Options for that attribute.<br>Example: Red, Blue / M, L.</p></div><div><h3>3. Variant</h3><p>A combination you sell.<br>Example: Red / M · ₹499 · 10 units.</p></div></div><p class="pc-help" style="margin-top:18px">Selecting values does not create stock or variants. A material or brand can also be a descriptive product attribute; it does not have to be a variant choice.</p></section>
<form action="{{ route('businesses.products.attributes.update',[$business,$product]) }}" method="POST" class="pc-card">@csrf @method('PUT')
    @include('products.partials.options')
    <div class="pc-savebar" style="margin-top:22px"><p class="pc-help">Save first, then open Variants to add each combination.</p><div class="pc-actions">@if($canEdit)<button type="submit" class="pc-button">Save available values</button>@endif<a class="pc-button pc-secondary" href="{{ route('businesses.products.variants.index',[$business,$product]) }}">Open variants →</a></div></div>
</form>
@if($access->can(auth()->user(),$business,'attributes.manage'))<section class="pc-alert"><strong>Need a new attribute or value?</strong><p><a class="pc-link" href="{{ route('businesses.catalog.index',[$business,'attributes']) }}">Manage your business attributes →</a> · Create Size with M, L, XL or Purity with 18K, 22K, then select the values here.</p></section>@endif
@include('products.partials.script')
</div></x-layouts::app>
