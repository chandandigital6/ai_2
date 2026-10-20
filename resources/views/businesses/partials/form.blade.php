@php
    $editing = isset($business);

    $india = $countries->firstWhere('name', 'India');

    $selectedState = old('state_id', $business->state_id ?? '');

    $selectedCity = old('city_id', $business->city_id ?? '');
@endphp

@if ($errors->any())
    <div class="mb-5 rounded-xl bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200">
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form id="growya-business-form" method="POST"
    action="{{ $editing ? route('businesses.update', $business) : route('businesses.store') }}"
    enctype="multipart/form-data"
    class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">

    @csrf

    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid gap-5 md:grid-cols-2">

        @foreach ([
        'name' => 'Business Name *',
        'phone' => 'Phone',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'pincode' => 'Pincode',
        'gst_number' => 'GST Number',
        'upi_id' => 'UPI ID',
    ] as $key => $label)
            <label class="block space-y-1.5">
                <span class="text-sm font-medium">
                    {{ $label }}
                </span>

                <input type="{{ $key === 'email' ? 'email' : 'text' }}" name="{{ $key }}"
                    value="{{ old($key, $business->$key ?? '') }}" @required($key === 'name')
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 focus:border-blue-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </label>
        @endforeach


        {{-- Business Logo --}}
        <div class="space-y-2">
            <label for="business-logo" class="block text-sm font-medium">
                Business Logo
            </label>

            @if (!empty($business?->logo))
                <img src="{{ asset('storage/' . $business->logo) }}" alt="Business Logo"
                    class="h-28 w-28 rounded-xl border border-zinc-700 object-contain">
            @endif

            <input id="business-logo" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp"
                class="block w-full rounded-xl border border-zinc-300 p-3 text-sm dark:border-zinc-700">

            <p class="text-xs text-zinc-500">
                JPG, PNG, WebP — Maximum 2 MB
            </p>

            @error('logo')
                <p class="text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Business Cover --}}
        <div class="space-y-2">
            <label for="business-cover" class="block text-sm font-medium">
                Business Cover Image
            </label>

            @if (!empty($business?->cover))
                <img src="{{ asset('storage/' . $business->cover) }}" alt="Business Cover"
                    class="h-32 w-full rounded-xl border border-zinc-700 object-cover">
            @endif

            <input id="business-cover" type="file" name="cover" accept=".jpg,.jpeg,.png,.webp"
                class="block w-full rounded-xl border border-zinc-300 p-3 text-sm dark:border-zinc-700">

            <p class="text-xs text-zinc-500">
                JPG, PNG, WebP — Maximum 5 MB
            </p>

            @error('cover')
                <p class="text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>


        {{-- Business Category --}}
        <label class="block space-y-1.5">
            <span class="text-sm font-medium">
                Business Category
            </span>

            <select name="category_id"
                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Select Category</option>

                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $business->category_id ?? '') === (string) $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </label>

        {{-- Country --}}
        <label class="block space-y-1.5">
            <span class="text-sm font-medium">
                Country *
            </span>

            <input type="text" value="India" readonly
                class="w-full rounded-xl border border-zinc-300 bg-zinc-100 px-3 py-2.5 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

            <input type="hidden" name="country_id" value="{{ $india?->id }}">
        </label>

        {{-- State --}}
        <label class="block space-y-1.5">
            <span class="text-sm font-medium">
                State / UT *
            </span>

            <select id="growya_state_id" name="state_id" required data-selected="{{ $selectedState }}"
                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Select State</option>

                @foreach ($states as $state)
                    @if ($india && (int) $state->country_id === (int) $india->id)
                        <option value="{{ $state->id }}" @selected((string) $selectedState === (string) $state->id)>
                            {{ $state->name }}
                        </option>
                    @endif
                @endforeach
            </select>
        </label>

        {{-- City --}}
        <label class="block space-y-1.5">
            <span class="text-sm font-medium">
                City *
            </span>

            <select id="growya_city_id" name="city_id" required data-selected="{{ $selectedCity }}" disabled
                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Select State First</option>
            </select>
        </label>

        {{-- Owner --}}
        @if (!$editing && auth()->user()->hasRole('super_admin', 'web'))
            <label class="block space-y-1.5">
                <span class="text-sm font-medium">
                    Business Owner
                </span>

                <select name="owner_id"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Myself</option>

                    @foreach ($owners as $owner)
                        <option value="{{ $owner->id }}" @selected((string) old('owner_id') === (string) $owner->id)>
                            {{ $owner->name }} — {{ $owner->email }}
                        </option>
                    @endforeach
                </select>
            </label>
        @endif

    </div>

    {{-- Address --}}
    <label class="block space-y-1.5">
        <span class="text-sm font-medium">
            Business Address
        </span>

        <textarea name="address" rows="3"
            class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">{{ old('address', $business->address ?? '') }}</textarea>
    </label>

    {{-- Description --}}
    <label class="block space-y-1.5">
        <span class="text-sm font-medium">
            Business Description
        </span>

        <textarea name="description" rows="4"
            class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">{{ old('description', $business->description ?? '') }}</textarea>
    </label>

    {{-- Payment Settings --}}
    <div class="flex flex-wrap gap-6">

        <label class="flex items-center gap-2">
            <input type="checkbox" name="cod_enabled" value="1" @checked(old('cod_enabled', $business->cod_enabled ?? false))>
            COD Enabled
        </label>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="upi_enabled" value="1" @checked(old('upi_enabled', $business->upi_enabled ?? false))>
            UPI Enabled
        </label>

    </div>

    {{-- Buttons --}}
    <div class="flex flex-wrap gap-3">

        <button type="submit"
            class="rounded-xl bg-zinc-950 px-5 py-2.5 font-semibold text-white hover:opacity-90 dark:bg-white dark:text-zinc-950">
            {{ $editing ? 'Update Business' : 'Create Business' }}
        </button>

        <a href="{{ route('businesses.index') }}"
            class="rounded-xl border border-zinc-300 px-5 py-2.5 dark:border-zinc-700">
            Cancel
        </a>

    </div>
</form>

{{-- Dynamic State-City Dropdown --}}
<script>
    (function() {
        const form = document.getElementById('growya-business-form');

        if (!form || form.dataset.locationInitialized === '1') {
            return;
        }

        form.dataset.locationInitialized = '1';

        const stateSelect = form.querySelector('#growya_state_id');
        const citySelect = form.querySelector('#growya_city_id');

        const cities =
            {{ \Illuminate\Support\Js::from(
                $cities->map(
                        fn($city) => [
                            'id' => $city->id,
                            'name' => $city->name,
                            'state_id' => $city->state_id,
                        ],
                    )->values(),
            ) }};

        const selectedCity = citySelect.dataset.selected || '';

        const citiesByState = {};

        cities.forEach(function(city) {
            const stateId = String(city.state_id);

            if (!citiesByState[stateId]) {
                citiesByState[stateId] = [];
            }

            citiesByState[stateId].push(city);
        });

        function loadCities(stateId, cityId = '') {
            citySelect.replaceChildren();

            const placeholder = new Option(
                stateId ? 'Select City' : 'Select State First',
                ''
            );

            citySelect.add(placeholder);

            const filteredCities = citiesByState[String(stateId)] || [];

            filteredCities.forEach(function(city) {
                const option = new Option(city.name, city.id);

                if (String(city.id) === String(cityId)) {
                    option.selected = true;
                }

                citySelect.add(option);
            });

            citySelect.disabled = !stateId || filteredCities.length === 0;
        }

        stateSelect.addEventListener('change', function() {
            loadCities(this.value);
        });

        loadCities(stateSelect.value, selectedCity);
    })();
</script>
