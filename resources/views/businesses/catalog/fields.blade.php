<div class="grid gap-3 sm:grid-cols-2">
    <label class="text-sm">Name<input name="name" required maxlength="255" value="{{ $record?->name ?? old('name') }}"
            class="mt-1 w-full rounded-xl border bg-transparent px-3 py-2.5"></label>
    @if ($kind === 'categories')
        <label class="text-sm">Type<select name="category_type"
                class="mt-1 w-full rounded-xl border bg-transparent px-3 py-2.5">
                @foreach (['product', 'service'] as $type)
                    <option value="{{ $type }}" @selected(($record?->category_type ?? old('category_type')) === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm">Parent (same type)<select name="parent_id"
                class="mt-1 w-full rounded-xl border bg-transparent px-3 py-2.5">
                <option value="">Top level</option>
                @foreach ($parents as $parent)
                    @if ($parent->id !== $record?->id)
                        <option value="{{ $parent->id }}" @selected(($record?->parent_id ?? old('parent_id')) == $parent->id)>{{ $parent->name }} ·
                            {{ $parent->category_type }}</option>
                    @endif
                @endforeach
            </select>
        </label>
        <label class="text-sm">Status<select name="is_active"
                class="mt-1 w-full rounded-xl border bg-transparent px-3 py-2.5">
                <option value="1" @selected($record?->is_active ?? true)>Active</option>
                <option value="0" @selected($record && !$record->is_active)>Inactive</option>
            </select></label>
    @endif
</div>
