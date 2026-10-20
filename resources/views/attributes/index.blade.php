
<x-layouts::app :title="'Attributes Management'">

<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">

    <div>
        <h1 class="text-2xl font-bold">
            Attributes Management
        </h1>
        <p class="mt-1 text-sm text-zinc-500">
            Manage attributes and their values.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-4 text-sm text-green-700 dark:bg-green-950 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Create Attribute --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

        <h2 class="mb-4 text-lg font-semibold">
            Add New Attribute
        </h2>

        <form
            method="POST"
            action="{{ route('attributes.store') }}"
            class="flex flex-wrap gap-3"
        >
            @csrf

            <input
                type="text"
                name="name"
                required
                maxlength="255"
                value="{{ old('name') }}"
                placeholder="Example: Size, Color, Purity"
                class="min-w-48 flex-1 rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
            >

            <button
                type="submit"
                class="rounded-xl bg-zinc-950 px-6 py-2.5 font-semibold text-white dark:bg-white dark:text-zinc-950"
            >
                Add Attribute
            </button>
        </form>
    </div>

    {{-- Search --}}
    <form
        method="GET"
        action="{{ route('attributes.index') }}"
        class="flex gap-3"
    >
        <input
            type="search"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search attributes..."
            class="min-w-0 flex-1 rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
        >

        <button
            type="submit"
            class="rounded-xl border border-zinc-300 px-5 py-2.5 dark:border-zinc-700"
        >
            Search
        </button>
    </form>

    {{-- Attribute Cards --}}
    <div class="space-y-5">

        @forelse($attributes as $attribute)

            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">

                <div class="border-b border-zinc-200 p-5 dark:border-zinc-700">

                    <div class="flex flex-wrap items-start justify-between gap-4">

                        <div>
                            <h2 class="text-lg font-semibold">
                                {{ $attribute->name }}
                            </h2>

                            <p class="mt-1 text-xs text-zinc-500">
                                Slug: {{ $attribute->slug }}
                                · Values: {{ $attribute->values_count }}
                            </p>
                        </div>

                        <form
                            method="POST"
                            action="{{ route('attributes.destroy', $attribute) }}"
                            onsubmit="return confirm('Delete this attribute?')"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="rounded-xl border border-red-300 px-4 py-2 text-sm font-medium text-red-600"
                            >
                                Delete Attribute
                            </button>
                        </form>
                    </div>

                    {{-- Update Attribute --}}
                    <form
                        method="POST"
                        action="{{ route('attributes.update', $attribute) }}"
                        class="mt-4 flex flex-wrap gap-3"
                    >
                        @csrf
                        @method('PUT')

                        <input
                            name="name"
                            required
                            maxlength="255"
                            value="{{ $attribute->name }}"
                            class="min-w-48 flex-1 rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
                        >

                        <button
                            type="submit"
                            class="rounded-xl bg-zinc-950 px-5 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950"
                        >
                            Update Name
                        </button>
                    </form>

                </div>

                {{-- Add Attribute Value --}}
                <div class="border-b border-zinc-200 p-5 dark:border-zinc-700">

                    <h3 class="mb-3 font-semibold">
                        Add Value to {{ $attribute->name }}
                    </h3>

                    <form
                        method="POST"
                        action="{{ route('attributes.values.store', $attribute) }}"
                        class="grid gap-3 sm:grid-cols-[1fr_130px_auto]"
                    >
                        @csrf

                        <input
                            name="value"
                            required
                            maxlength="255"
                            placeholder="Example: Red, XL, 22K"
                            class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
                        >

                        <input
                            type="number"
                            name="sort_order"
                            min="0"
                            value="0"
                            required
                            placeholder="Order"
                            class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
                        >

                        <button
                            type="submit"
                            class="rounded-xl border border-zinc-300 px-5 py-2 font-medium dark:border-zinc-700"
                        >
                            Add Value
                        </button>
                    </form>
                </div>

                {{-- Existing Values --}}
                <div class="space-y-3 p-5">

                    <h3 class="font-semibold">
                        Attribute Values
                    </h3>

                    @forelse($attribute->values as $value)

                        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">

                            <form
                                id="attribute-value-update-{{ $value->id }}"
                                method="POST"
                                action="{{ route('attributes.values.update', [$attribute, $value]) }}"
                                class="flex min-w-48 flex-1 flex-wrap gap-3"
                            >
                                @csrf
                                @method('PUT')

                                <input
                                    name="value"
                                    required
                                    maxlength="255"
                                    value="{{ $value->value }}"
                                    class="min-w-32 flex-1 rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
                                >

                                <input
                                    type="number"
                                    name="sort_order"
                                    min="0"
                                    value="{{ $value->sort_order }}"
                                    required
                                    class="w-24 rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
                                >
                            </form>

                            <button
                                type="submit"
                                form="attribute-value-update-{{ $value->id }}"
                                class="rounded-xl border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700"
                            >
                                Update
                            </button>

                            <form
                                method="POST"
                                action="{{ route('attributes.values.destroy', [$attribute, $value]) }}"
                                onsubmit="return confirm('Delete this value?')"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="rounded-xl border border-red-300 px-4 py-2 text-sm text-red-600"
                                >
                                    Delete
                                </button>
                            </form>

                        </div>

                    @empty
                        <p class="text-sm text-zinc-500">
                            No values created yet.
                        </p>
                    @endforelse

                </div>

            </div>

        @empty
            <div class="rounded-2xl border border-dashed border-zinc-300 p-10 text-center text-zinc-500 dark:border-zinc-700">
                No attributes found.
            </div>
        @endforelse

    </div>

    {{ $attributes->links() }}

</div>

</x-layouts::app>
