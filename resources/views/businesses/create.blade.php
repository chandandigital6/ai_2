<x-layouts::app :title="__('Register Your Business')">
    <div class="mx-auto max-w-5xl space-y-5">
        <div>
            <h1 class="text-2xl font-bold">Register Your Business</h1>
            <p class="text-sm text-zinc-500">Complete the form to request a business account. Your showroom will be published only after Super Admin approval.</p>
        </div>@include('businesses.partials.form')
    </div>
</x-layouts::app>
