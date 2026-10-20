@props(['placement'=>'home','city'=>null])
@php
    $banners=\App\Models\Banner::where('placement',$placement)->where('status','active')
        ->where(fn($q)=>$q->whereNull('starts_at')->orWhere('starts_at','<=',now()))
        ->where(fn($q)=>$q->whereNull('ends_at')->orWhere('ends_at','>',now()))
        ->where(fn($q)=>$q->whereNull('city_id')->when($city,fn($q)=>$q->orWhere('city_id',$city)))
        ->orderBy('sort_order')->limit(5)->get();
@endphp
@if($banners->isNotEmpty())<section class="g-container" aria-label="Featured announcements" style="display:grid;gap:16px;padding-top:20px;padding-bottom:20px">@foreach($banners as $banner)<article style="position:relative;border-radius:18px;overflow:hidden;border:1px solid #e2e8e6"><img src="{{ Storage::disk('public')->url($banner->media) }}" alt="{{ $banner->cta_text?:'Featured announcement' }}" style="display:block;width:100%;max-height:360px;object-fit:cover" loading="lazy">@if($banner->cta_url && str_starts_with($banner->cta_url,'https://'))<a href="{{ $banner->cta_url }}" class="g-btn g-btn-primary" style="position:absolute;bottom:20px;left:20px" rel="noopener">{{ $banner->cta_text?:'Learn more' }}</a>@endif</article>@endforeach</section>@endif
