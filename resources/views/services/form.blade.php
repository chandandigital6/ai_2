<x-layouts::app :title="$service->exists ? 'Edit Service' : 'Add Service'">

    @php
        $editing = $service->exists;

        $action = $editing
            ? route('businesses.services.update', [$business, $service])
            : route('businesses.services.store', $business);
    @endphp

    <div class="mx-auto max-w-5xl space-y-6 p-4 md:p-6">

        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">
                    {{ $editing ? 'Edit Service' : 'Add New Service' }}
                </h1>

                <p class="text-sm text-zinc-500">
                    {{ $business->name }}
                </p>
            </div>

            <a href="{{ route('businesses.services.index', $business) }}"
                class="rounded-xl border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700">
                Back
            </a>
        </div>

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="space-y-5 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">

                <h2 class="text-lg font-semibold">
                    Service Information
                </h2>

                <div class="grid gap-5 md:grid-cols-2">

                    <label class="space-y-2">
                        <span class="block text-sm font-medium">
                            Service Name *
                        </span>

                        <input type="text" name="name" value="{{ old('name', $service->name) }}" required
                            maxlength="255" placeholder="Example: Website Development"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                    </label>


                    <label class="space-y-2">

                        <span class="block text-sm font-medium">
                            Service Category
                        </span>

                        <select name="category_id"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                            <option value="">
                                Select Service Category
                            </option>

                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $service->category_id) === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach

                        </select>

                        @if ($categories->isEmpty())
                            <p class="text-sm text-amber-600">
                                No active service categories found.
                                Please add a Service category in Category Management.
                            </p>
                        @endif

                        @error('category_id')
                            <p class="text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </label>


                    <label class="space-y-2">
                        <span class="block text-sm font-medium">
                            Price Type *
                        </span>

                        <select name="price_type" id="service-price-type" required
                            class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                            @foreach ([
        'fixed' => 'Fixed Price',
        'starting_from' => 'Starting From',
        'on_request' => 'Price On Request',
        'free' => 'Free Service',
    ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('price_type', $service->price_type ?? 'fixed') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="space-y-2">
                        <span class="block text-sm font-medium">
                            Service Duration (Minutes)
                        </span>

                        <input type="number" name="duration_minutes" min="1" max="525600"
                            value="{{ old('duration_minutes', $service->duration_minutes) }}" placeholder="Example: 60"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                    </label>

                    <label class="space-y-2" data-service-price-field>
                        <span class="block text-sm font-medium">
                            Price (₹) *
                        </span>

                        <input type="number" name="price" step="0.01" min="0"
                            value="{{ old('price', $service->price) }}" placeholder="5000.00"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                    </label>

                    <label class="space-y-2" data-service-price-field>
                        <span class="block text-sm font-medium">
                            Offer Price (₹)
                        </span>

                        <input type="number" name="offer_price" step="0.01" min="0"
                            value="{{ old('offer_price', $service->offer_price) }}" placeholder="4500.00"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                    </label>

                    <label class="space-y-2">
                        <span class="block text-sm font-medium">
                            Status *
                        </span>

                        <select name="status" required
                            class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                            <option value="active" @selected(old('status', $service->status ?? 'active') === 'active')>
                                Active
                            </option>

                            <option value="inactive" @selected(old('status', $service->status) === 'inactive')>
                                Inactive
                            </option>
                        </select>
                    </label>

                    <label class="space-y-2">
                        <span class="block text-sm font-medium">
                            Featured Image
                        </span>

                        <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp"
                            class="w-full rounded-xl border border-zinc-300 p-3 text-sm dark:border-zinc-700">
                    </label>

                </div>

                @if ($editing && $service->featured_image)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($service->featured_image) }}"
                        alt="Service Image" class="h-40 w-40 rounded-xl border object-cover">
                @endif

                <label class="block space-y-2">
                    <span class="text-sm font-medium">
                        Service Description
                    </span>

                    <textarea name="description" rows="6" maxlength="20000" placeholder="Describe your service..."
                        class="w-full rounded-xl border border-zinc-300 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">{{ old('description', $service->description) }}</textarea>
                </label>

            </div>

            <button type="submit"
                class="rounded-xl bg-zinc-950 px-6 py-3 font-semibold text-white dark:bg-white dark:text-zinc-950">
                {{ $editing ? 'Update Service' : 'Save Service' }}
            </button>
        </form>

    </div>

    <script>
        (function() {
            function initializeServicePrice() {
                const type = document.getElementById('service-price-type');

                if (!type || type.dataset.initialized === '1') {
                    return;
                }

                type.dataset.initialized = '1';

                const fields = document.querySelectorAll(
                    '[data-service-price-field]'
                );

                function updateFields() {
                    const show = (
                        type.value === 'fixed' ||
                        type.value === 'starting_from'
                    );

                    fields.forEach(function(field) {
                        field.hidden = !show;

                        const input = field.querySelector('input');
                        if (!input) return;

                        input.disabled = !show;
                        input.required = show && input.name === 'price';
                    });
                }

                type.addEventListener('change', updateFields);
                updateFields();
            }

            if (document.readyState === 'loading') {
                document.addEventListener(
                    'DOMContentLoaded',
                    initializeServicePrice, {
                        once: true
                    }
                );
            } else {
                initializeServicePrice();
            }

            document.addEventListener(
                'livewire:navigated',
                initializeServicePrice
            );
        })();
    </script>

</x-layouts::app>
