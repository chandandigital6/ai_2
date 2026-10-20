
<x-layouts::app :title="'Social Links - '.$business->name">

    <div class="mx-auto max-w-5xl space-y-6 p-4 md:p-6">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                    Business Social Links
                </h1>

                <p class="mt-1 text-sm text-zinc-500">
                    {{ $business->name }}
                </p>
            </div>

            <a
                href="{{ route('businesses.show', $business) }}"
                class="rounded-xl border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700"
            >
                Back to Business
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-green-50 p-4 text-sm text-green-700 dark:bg-green-950 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

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
            action="{{ route('businesses.social-links.store', $business) }}"
            class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
        >
            @csrf

            <div class="border-b border-zinc-200 p-5 dark:border-zinc-700">
                <h2 class="text-lg font-semibold">
                    Social Media Profiles
                </h2>
                <p class="mt-1 text-sm text-zinc-500">
                    Add or update your business social media links.
                    Leave a URL empty to remove that link.
                </p>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">

                @foreach ($platforms as $platform => $label)

                    @php
                        $saved = $links->get($platform);

                        $url = old(
                            "links.$platform.url",
                            $saved?->url ?? ''
                        );

                        $username = old(
                            "links.$platform.username",
                            $saved?->username ?? ''
                        );
                    @endphp

                    <div class="grid gap-4 p-5 md:grid-cols-[160px_1fr]">

                        <div class="flex items-center gap-3">
                            <span class="font-semibold text-zinc-900 dark:text-white">
                                {{ $label }}
                            </span>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">

                            <label class="block space-y-1">
                                <span class="text-xs text-zinc-500">
                                    Profile URL
                                </span>

                                <input
                                    type="url"
                                    name="links[{{ $platform }}][url]"
                                    value="{{ $url }}"
                                    placeholder="https://..."
                                    @cannot('update', $business) readonly @endcannot
                                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                >
                            </label>

                            <label class="block space-y-1">
                                <span class="text-xs text-zinc-500">
                                    Username / Handle
                                </span>

                                <input
                                    type="text"
                                    name="links[{{ $platform }}][username]"
                                    value="{{ $username }}"
                                    placeholder="@username"
                                    @cannot('update', $business) readonly @endcannot
                                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                >
                            </label>

                        </div>

                        @if ($saved?->url)
                            <div class="md:col-start-2">
                                <a
                                    href="{{ $saved->url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
                                >
                                    View saved {{ $label }} link ↗
                                </a>
                            </div>
                        @endif

                    </div>

                @endforeach

            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-zinc-200 p-5 dark:border-zinc-700">

                <p class="text-xs text-zinc-500">
                    Only HTTPS or HTTP links are accepted.
                </p>

                @can('update', $business)
                    <button
                        type="submit"
                        class="rounded-xl bg-zinc-950 px-6 py-2.5 font-semibold text-white hover:opacity-90 dark:bg-white dark:text-zinc-950"
                    >
                        Save Social Links
                    </button>
                @endcan

            </div>
        </form>

    </div>

</x-layouts::app>
