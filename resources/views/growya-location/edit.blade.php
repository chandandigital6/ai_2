<x-layouts::app :title="'Business Map Location'">
    <div class="mx-auto max-w-3xl space-y-5 p-4">
        <div>
            <h1 class="text-2xl font-bold">Business Map Location</h1>
            <p class="text-sm text-zinc-500">{{ $business->name }} — set an accurate pin for nearby searches.</p>
        </div>
        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-red-800">{{ $errors->first() }}</div>
        @endif
        <form action="{{ route('growya.nearby.location.update', $business) }}" method="POST"
            class="space-y-4 rounded-2xl border p-5">
            @csrf @method('PATCH')
            <label class="block">Latitude <input name="latitude" type="number" step="any" min="-90"
                    max="90" required value="{{ old('latitude', $business->latitude) }}"
                    class="mt-1 w-full rounded-xl border px-3 py-2 dark:bg-zinc-900"></label>
            <label class="block">Longitude <input name="longitude" type="number" step="any" min="-180"
                    max="180" required value="{{ old('longitude', $business->longitude) }}"
                    class="mt-1 w-full rounded-xl border px-3 py-2 dark:bg-zinc-900"></label>
            <p class="text-sm text-zinc-500">Enter the actual business location. Do not use the customer's location.
                Coordinates are public as part of business discovery.</p>
            <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-2.5 font-semibold text-white">Save Map
                Location</button>
        </form>
        <a href="{{ route('businesses.show', $business) }}" class="underline">Back to business</a>
    </div>
</x-layouts::app>
