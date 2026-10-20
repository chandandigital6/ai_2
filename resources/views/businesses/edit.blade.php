<x-layouts::app :title="__('Edit Business')">
    <div class="mx-auto max-w-5xl space-y-5">
        <div>
            <h1 class="text-2xl font-bold">Edit: {{ $business->name }}</h1>
            <p class="text-sm text-zinc-500">Edit showroom information.</p>
        </div>@include('businesses.partials.form')
    </div>
</x-layouts::app>
