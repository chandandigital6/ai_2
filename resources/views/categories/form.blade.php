
<x-layouts::app :title="$category->exists ? 'Edit Category' : 'Add Category'">

<div class="mx-auto max-w-4xl space-y-6 p-4 md:p-6">

    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">
                {{ $category->exists ? 'Edit Category' : 'Add Category' }}
            </h1>
            <p class="text-sm text-zinc-500">
                Category details, image and visibility.
            </p>
        </div>

        <a href="{{ route('categories.index') }}"
           class="rounded-xl border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700">
            Back
        </a>
    </div>

    @if($errors->any())
        <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          enctype="multipart/form-data"
          action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}"
          class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">

        @csrf

        @if($category->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 md:grid-cols-2">

            <label class="space-y-1">
                <span class="text-sm font-medium">Category Name *</span>

                <input name="name" required maxlength="255"
                       value="{{ old('name', $category->name) }}"
                       placeholder="Example: Jewellery & Gems"
                       class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800">
            </label>

            <label class="space-y-1">
                <span class="text-sm font-medium">Category Type *</span>

                <select name="category_type" id="category_type" required
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}"
                            @selected(old('category_type', $category->category_type ?? 'business') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="space-y-1">
                <span class="text-sm font-medium">Parent Category</span>

                <select name="parent_id" id="parent_id"
                        data-selected="{{ old('parent_id', $category->parent_id) }}"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                    <option value="">Main Category</option>

                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}"
                                data-type="{{ $parent->category_type }}">
                            {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="space-y-1">
                <span class="text-sm font-medium">Icon Name / Code</span>

                <input name="icon"
                       value="{{ old('icon', $category->icon) }}"
                       placeholder="Example: gem"
                       class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800">
            </label>

            <label class="space-y-1">
                <span class="text-sm font-medium">Sort Order</span>

                <input type="number" name="sort_order" min="0" required
                       value="{{ old('sort_order', $category->sort_order ?? 0) }}"
                       class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800">
            </label>

            <label class="space-y-1">
                <span class="text-sm font-medium">Category Image</span>

                @if($category->image)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($category->image) }}"
                         alt="Current category image"
                         class="mb-2 h-24 w-24 rounded-xl object-cover">
                @endif

                <input type="file" name="image"
                       accept=".jpg,.jpeg,.png,.webp"
                       class="block w-full rounded-xl border border-zinc-300 p-3 text-sm dark:border-zinc-700">
                <span class="block text-xs text-zinc-500">Max 2 MB</span>
            </label>

        </div>

        <div class="flex flex-wrap gap-6">

            <label class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       @checked(old('is_active', $category->is_active ?? true))>
                Active Category
            </label>

            <label class="flex items-center gap-2">
                <input type="hidden" name="is_featured" value="0">
                <input type="checkbox" name="is_featured" value="1"
                       @checked(old('is_featured', $category->is_featured ?? false))>
                Featured Category
            </label>

        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="rounded-xl bg-zinc-950 px-6 py-2.5 font-semibold text-white dark:bg-white dark:text-zinc-950">
                {{ $category->exists ? 'Update Category' : 'Create Category' }}
            </button>

            <a href="{{ route('categories.index') }}"
               class="rounded-xl border border-zinc-300 px-5 py-2.5 dark:border-zinc-700">
                Cancel
            </a>
        </div>

    </form>
</div>

<script>
(function () {
    const typeSelect = document.getElementById('category_type');
    const parentSelect = document.getElementById('parent_id');

    if (!typeSelect || !parentSelect) return;

    const initiallySelected = parentSelect.dataset.selected || '';

    function filterParents(selectedId = '') {
        let found = false;

        Array.from(parentSelect.options).forEach(option => {
            if (!option.value) return;

            const matching = option.dataset.type === typeSelect.value;

            option.hidden = !matching;
            option.disabled = !matching;

            if (matching && option.value === String(selectedId)) {
                found = true;
            }
        });

        parentSelect.value = found ? String(selectedId) : '';
    }

    typeSelect.addEventListener('change', () => filterParents());
    filterParents(initiallySelected);
})();
</script>

</x-layouts::app>
