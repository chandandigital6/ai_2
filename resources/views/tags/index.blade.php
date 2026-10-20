
<x-layouts::app :title="'Tags Management'">

<div class="mx-auto max-w-6xl space-y-6 p-4 md:p-6">

    <div>
        <h1 class="text-2xl font-bold">Tags Management</h1>
        <p class="mt-1 text-sm text-zinc-500">
            Create and manage product tags.
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

    {{-- Add New Tag --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

        <h2 class="mb-4 text-lg font-semibold">Add New Tag</h2>

        <form
            method="POST"
            action="{{ route('tags.store') }}"
            class="flex flex-wrap gap-3"
        >
            @csrf

            <input
                type="text"
                name="name"
                required
                maxlength="255"
                value="{{ old('name') }}"
                placeholder="Example: New Arrival"
                class="min-w-48 flex-1 rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
            >

            <button
                type="submit"
                class="rounded-xl bg-zinc-950 px-6 py-2.5 font-semibold text-white dark:bg-white dark:text-zinc-950"
            >
                Add Tag
            </button>
        </form>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('tags.index') }}"
          class="flex gap-3">

        <input
            name="search"
            value="{{ request('search') }}"
            placeholder="Search tags..."
            class="min-w-0 flex-1 rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-900"
        >

        <button
            class="rounded-xl border border-zinc-300 px-5 py-2.5 dark:border-zinc-700"
        >
            Search
        </button>
    </form>

    {{-- Tags List --}}
    <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-700">

        <table class="w-full text-left text-sm">

            <thead class="bg-zinc-100 dark:bg-zinc-800">
                <tr>
                    <th class="p-4">ID</th>
                    <th class="p-4">Tag Name</th>
                    <th class="p-4">Slug</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">

                @forelse($tags as $tag)
                    <tr>

                        <td class="p-4">{{ $tag->id }}</td>

                        <td class="p-4">
                            <form
                                id="tag-update-{{ $tag->id }}"
                                method="POST"
                                action="{{ route('tags.update', $tag) }}"
                            >
                                @csrf
                                @method('PUT')

                                <input
                                    type="text"
                                    name="name"
                                    required
                                    maxlength="255"
                                    value="{{ $tag->name }}"
                                    class="w-full min-w-40 rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
                                >
                            </form>
                        </td>

                        <td class="p-4 text-zinc-500">
                            {{ $tag->slug }}
                        </td>

                        <td class="p-4">
                            <div class="flex flex-wrap gap-2">

                                <button
                                    type="submit"
                                    form="tag-update-{{ $tag->id }}"
                                    class="rounded-xl border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700"
                                >
                                    Update
                                </button>

                                <form
                                    method="POST"
                                    action="{{ route('tags.destroy', $tag) }}"
                                    onsubmit="return confirm('Delete this tag?')"
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
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-zinc-500">
                            No tags found.
                        </td>
                    </tr>
                @endforelse

            </tbody>

        </table>
    </div>

    {{ $tags->links() }}

</div>

</x-layouts::app>
