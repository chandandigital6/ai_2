
<x-layouts::app :title="'Product Bulk Import Export'">

@php
    $access = app(\App\Services\BusinessAccessService::class);

    $canImport = $access->can(
        auth()->user(),
        $business,
        'products.create'
    );
@endphp

<div class="mx-auto max-w-6xl space-y-6 p-4 md:p-6">

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">
                Product Bulk Import / Export
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                {{ $business->name }}
            </p>
        </div>

        <a
            href="{{ route('businesses.products.index', $business) }}"
            class="rounded-xl border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700"
        >
            Back to Products
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-4 text-sm text-green-700 dark:bg-green-950 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200">
            <h3 class="font-semibold">Import Error</h3>

            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Download Sample Template --}}
    @if($canImport)
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">
                Step 1 — Download CSV Template
            </h2>

            <p class="mt-2 text-sm text-zinc-500">
                Download the sample CSV and enter product details.
                Keep column names and their order unchanged.
            </p>

            <a
                href="{{ route('businesses.products.bulk.template', $business) }}"
                class="mt-4 inline-flex rounded-xl bg-zinc-950 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950"
            >
                Download Sample CSV
            </a>
        </div>
    @endif

    {{-- Categories Reference --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

        <h2 class="mb-3 text-lg font-semibold">
            Product Category IDs
        </h2>

        <p class="mb-4 text-sm text-zinc-500">
            CSV में category_id के लिए नीचे दी गई ID इस्तेमाल करें।
            Category खाली भी रख सकते हैं।
        </p>

        <div class="max-h-60 overflow-auto rounded-xl border border-zinc-200 dark:border-zinc-700">

            <table class="w-full text-left text-sm">

                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="p-3">ID</th>
                        <th class="p-3">Category Name</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($categories as $category)
                        <tr>
                            <td class="p-3">
                                {{ $category->id }}
                            </td>

                            <td class="p-3">
                                {{ $category->name }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="p-4 text-zinc-500">
                                No active product categories.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

    {{-- Import Products --}}
    @if($canImport)
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

            <h2 class="text-lg font-semibold">
                Step 2 — Import Products
            </h2>

            <p class="mt-2 text-sm text-zinc-500">
                Maximum 500 products per CSV.
                Maximum file size: 2 MB.
            </p>

            <form
                method="POST"
                enctype="multipart/form-data"
                action="{{ route('businesses.products.bulk.import', $business) }}"
                class="mt-5 space-y-4"
            >
                @csrf

                <label class="block space-y-2">
                    <span class="text-sm font-medium">
                        Select CSV File
                    </span>

                    <input
                        type="file"
                        name="file"
                        accept=".csv,text/csv"
                        required
                        class="block w-full rounded-xl border border-zinc-300 p-3 text-sm dark:border-zinc-700"
                    >
                </label>

                <button
                    type="submit"
                    class="rounded-xl bg-zinc-950 px-6 py-2.5 font-semibold text-white dark:bg-white dark:text-zinc-950"
                >
                    Import Products
                </button>
            </form>
        </div>
    @endif

    {{-- Import Errors --}}
    @if(session('import_errors'))
        <div class="rounded-2xl border border-red-200 bg-white p-5 dark:border-red-900 dark:bg-zinc-900">

            <h2 class="text-lg font-semibold">
                Invalid CSV Rows
            </h2>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="p-3">CSV Row</th>
                            <th class="p-3">Error</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach(session('import_errors') as $error)
                            <tr class="border-b">
                                <td class="p-3">
                                    {{ $error[0] }}
                                </td>

                                <td class="p-3 text-red-600">
                                    {{ $error[1] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <a
                href="{{ route('businesses.products.bulk.errors', $business) }}"
                class="mt-4 inline-flex rounded-xl border border-red-300 px-5 py-2.5 text-sm font-semibold text-red-600"
            >
                Download Full Error Report
            </a>
        </div>
    @endif

    {{-- Export --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

        <h2 class="text-lg font-semibold">
            Export Products
        </h2>

        <p class="mt-2 text-sm text-zinc-500">
            Download all products belonging to this business
            as a CSV file.
        </p>

        <a
            href="{{ route('businesses.products.bulk.export', $business) }}"
            class="mt-4 inline-flex rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-semibold dark:border-zinc-700"
        >
            Export Products CSV
        </a>

    </div>

</div>

</x-layouts::app>
