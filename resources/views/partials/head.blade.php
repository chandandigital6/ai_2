<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'grova_ai') : config('app.name', 'grova_ai') }}
</title>





@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@include('partials.growya-assets')
<meta name="csrf-token" content="{{ csrf_token() }}">
@fluxAppearance
