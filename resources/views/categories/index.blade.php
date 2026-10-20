
<x-layouts::app :title="'Categories'">
    <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">Category Management</h1>
                <p class="text-sm text-zinc-500">
                    Manage business, product and service categories.
                </p>
            </div>

            <a href="{{ route('categories.create') }}"
               class="rounded-xl bg-zinc-950 px-5 py-2.5 font-medium text-white dark:bg-white dark:text-zinc-950">
                + Add Category
            </a>
        </div>

        @if(session('success'))
            <div class="rounded-xl bg-green-50 p-4 text-green-700 dark:bg-green-950 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-red-700 dark:bg-red-950 dark:text-red-200">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="GET" action="{{ route('categories.index') }}"
              class="grid gap-3 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700 md:grid-cols-[1fr_220px_auto]">

            <input name="search" value="{{ request('search') }}"
                   placeholder="Search categories..."
                   class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">

            <select name="type"
                    class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">All Types</option>
                @foreach($types as $key => $label)
                    <option value="{{ $key }}" @selected(request('type') === $key)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <button class="rounded-xl bg-zinc-900 px-5 py-2 text-white dark:bg-white dark:text-black">
                Filter
            </button>
        </form>

        <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="p-4">Image</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Type</th>
                        <th class="p-4">Parent</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Featured</th>
                        <th class="p-4">Order</th>
                        <th class="p-4">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($categories as $category)
                        <tr>
                            <td class="p-4">
                                @if($category->image)
                                    <img
                                        src="{{ asset('storage/' . $category->image) }}"
                                        class="h-12 w-12 rounded-lg object-cover"
                                        alt="{{ $category->name }}"
                                    >
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-zinc-100 text-xs dark:bg-zinc-800">
                                        No Image
                                    </div>
                                @endif
                            </td>

                            <td class="p-4">
                                <div class="font-semibold">
                                    {{ $category->name }}
                                </div>
                                <div class="text-xs text-zinc-500">
                                    {{ $category->slug }}
                                </div>
                            </td>

                            <td class="p-4 capitalize">
                                {{ $category->category_type }}
                            </td>

                            <td class="p-4">
                                {{ $category->parent?->name ?? 'Main Category' }}
                            </td>

                            <td class="p-4">
                                {{ $category->is_active ? 'Active' : 'Inactive' }}
                            </td>

                            <td class="p-4">
                                {{ $category->is_featured ? 'Yes' : 'No' }}
                            </td>

                            <td class="p-4">{{ $category->sort_order }}</td>

                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('categories.edit', $category) }}"
                                       class="font-medium text-blue-600 dark:text-blue-400">
                                        Edit
                                    </a>

                                    <form method="POST"
                                          action="{{ route('categories.destroy', $category) }}"
                                          onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="font-medium text-red-600">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-zinc-500">
                                No categories found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $categories->links() }}
    </div>
</x-layouts::app>
