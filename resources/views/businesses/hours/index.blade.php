
<x-layouts::app :title="'Business Hours - '.$business->name">

    <div class="mx-auto max-w-5xl space-y-6 p-4 md:p-6">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">

            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                    Business Opening Hours
                </h1>

                <p class="mt-1 text-sm text-zinc-500">
                    {{ $business->name }}
                </p>
            </div>

            <a
                href="{{ route('businesses.show', $business) }}"
                class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-700"
            >
                Back to Business
            </a>
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="rounded-xl bg-green-50 p-4 text-sm text-green-700 dark:bg-green-950 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('businesses.hours.store', $business) }}"
            class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
        >
            @csrf

            <div class="border-b border-zinc-200 p-5 dark:border-zinc-700">
                <h2 class="text-lg font-semibold">
                    Weekly Business Schedule
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Set opening and closing hours for each day.
                </p>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">

                @foreach ($days as $dayNumber => $dayName)

                    @php
                        $hour = $hours->get($dayNumber);

                        $isClosed = (bool) old(
                            "hours.$dayNumber.is_closed",
                            $hour?->is_closed ?? ($dayNumber === 0)
                        );

                        $openTime = old(
                            "hours.$dayNumber.open_time",
                            $hour?->open_time
                                ? substr($hour->open_time, 0, 5)
                                : '10:00'
                        );

                        $closeTime = old(
                            "hours.$dayNumber.close_time",
                            $hour?->close_time
                                ? substr($hour->close_time, 0, 5)
                                : '20:00'
                        );
                    @endphp

                    <div
                        class="grid gap-4 p-5 md:grid-cols-[150px_1fr_1fr_130px] md:items-center"
                        data-business-hour-row
                    >

                        {{-- Day --}}
                        <div class="font-semibold text-zinc-900 dark:text-white">
                            {{ $dayName }}
                        </div>

                        {{-- Opening Time --}}
                        <label class="space-y-1">
                            <span class="block text-xs text-zinc-500">
                                Opening Time
                            </span>

                            <input
                                type="time"
                                name="hours[{{ $dayNumber }}][open_time]"
                                value="{{ $openTime }}"
                                data-hour-time
                                @disabled($isClosed)
                                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-zinc-900 disabled:opacity-40 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            >
                        </label>

                        {{-- Closing Time --}}
                        <label class="space-y-1">
                            <span class="block text-xs text-zinc-500">
                                Closing Time
                            </span>

                            <input
                                type="time"
                                name="hours[{{ $dayNumber }}][close_time]"
                                value="{{ $closeTime }}"
                                data-hour-time
                                @disabled($isClosed)
                                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-zinc-900 disabled:opacity-40 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            >
                        </label>

                        {{-- Closed Day --}}
                        <label class="flex items-center gap-2 pt-4 md:pt-0">

                            <input
                                type="hidden"
                                name="hours[{{ $dayNumber }}][is_closed]"
                                value="0"
                            >

                            <input
                                type="checkbox"
                                name="hours[{{ $dayNumber }}][is_closed]"
                                value="1"
                                data-hour-closed
                                @checked($isClosed)
                                class="h-4 w-4 rounded border-zinc-300"
                            >

                            <span class="text-sm font-medium">
                                Closed
                            </span>
                        </label>

                    </div>

                @endforeach
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-zinc-200 p-5 dark:border-zinc-700">

                <p class="text-sm text-zinc-500">
                    Closed days will not have opening or closing times.
                </p>

                @can('update', $business)
                    <button
                        type="submit"
                        class="rounded-xl bg-zinc-950 px-6 py-2.5 font-semibold text-white hover:opacity-90 dark:bg-white dark:text-zinc-950"
                    >
                        Save Business Hours
                    </button>
                @endcan

            </div>

        </form>

    </div>

    <script>
        function initializeBusinessHours() {
            document.querySelectorAll(
                '[data-business-hour-row]'
            ).forEach(function(row) {

                if (row.dataset.initialized === '1') {
                    return;
                }

                row.dataset.initialized = '1';

                const checkbox = row.querySelector(
                    '[data-hour-closed]'
                );

                const inputs = row.querySelectorAll(
                    '[data-hour-time]'
                );

                function updateState() {
                    inputs.forEach(function(input) {
                        input.disabled = checkbox.checked;
                    });
                }

                checkbox.addEventListener(
                    'change',
                    updateState
                );

                updateState();
            });
        }

        document.addEventListener(
            'DOMContentLoaded',
            initializeBusinessHours
        );

        document.addEventListener(
            'livewire:navigated',
            initializeBusinessHours
        );
    </script>

</x-layouts::app>
