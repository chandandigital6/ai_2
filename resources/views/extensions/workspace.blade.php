<x-layouts::app :title="$sections[$section][0]" :business="$business">
@include('extensions.style')
@php $access=app(\App\Services\BusinessAccessService::class); $can=fn($p)=>$access->can(auth()->user(),$business,$p); @endphp
<div class="g-page"><div class="g-page-heading"><div><p class="g-kicker">{{ $business->name }}</p><h1>{{ $sections[$section][0] }}</h1></div><a class="g-btn g-btn-secondary" href="{{ route('businesses.show',$business) }}">Business overview</a></div>
<div class="ex-row">@foreach($sections as $key=>[$label,$permission])@if($can($permission))<a class="ex-tab {{ $section===$key?'active':'' }}" href="{{ route('extensions.workspace',[$business,$key]) }}">{{ $label }}</a>@endif @endforeach</div>
@if(session('success'))<div class="g-alert g-tone-green mt-4">{{ session('success') }}</div>@endif
@if($errors->any())<div class="g-alert g-tone-red mt-4">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@include('extensions.sections.'.$section)
</div></x-layouts::app>
