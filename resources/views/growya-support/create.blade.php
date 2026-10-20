@php
    $categories = [
        'orders' => 'Orders',
        'payments' => 'Payments',
        'refunds' => 'Refunds',
        'business' => 'Business',
        'technical' => 'Technical',
        'other' => 'Other',
    ];
    $priorities = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];

    $input = 'mt-1 w-full rounded-lg border p-3 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100 dark:border-zinc-700 dark:bg-zinc-900';
@endphp

<x-layouts::app title="Create Support Ticket">
    <div id="main-content" class="mx-auto max-w-3xl space-y-6 p-4 md:p-8">
        <a href="{{ route('growya.support.index') }}" wire:navigate
            class="text-sm font-semibold text-emerald-700 underline dark:text-emerald-400">← All tickets</a>

        <div>
            <h1 class="text-2xl font-bold md:text-3xl">Create support ticket</h1>
            <p class="text-sm text-zinc-500">Apni dikkat detail mein likho. Hamari team jald reply karegi.</p>
        </div>

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('growya.support.store') }}" enctype="multipart/form-data"
            class="space-y-5 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 md:p-6">
            @csrf

            <div>
                <label for="subject" class="block text-sm font-medium">Subject <span class="text-red-600">*</span></label>
                <input id="subject" required maxlength="200" name="subject" value="{{ old('subject') }}"
                    placeholder="Short summary, jaise: Order ka payment verify nahi hua" class="{{ $input }}">
                @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="category" class="block text-sm font-medium">Category <span class="text-red-600">*</span></label>
                    <select id="category" required name="category" class="{{ $input }}">
                        @foreach ($categories as $v => $text)
                            <option value="{{ $v }}" @selected(old('category') === $v)>{{ $text }}</option>
                        @endforeach
                    </select>
                    @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="priority" class="block text-sm font-medium">Priority <span class="text-red-600">*</span></label>
                    <select id="priority" required name="priority" class="{{ $input }}">
                        @foreach ($priorities as $v => $text)
                            <option value="{{ $v }}" @selected(old('priority', 'medium') === $v)>{{ $text }}</option>
                        @endforeach
                    </select>
                    @error('priority') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-zinc-400">Urgent sirf zaroori issues ke liye chuno.</p>
                </div>
            </div>

            @if ($businesses->isNotEmpty())
                <div>
                    <label for="business_id" class="block text-sm font-medium">Related business <span class="font-normal text-zinc-500">(optional)</span></label>
                    <select id="business_id" name="business_id" class="{{ $input }}">
                        <option value="">None</option>
                        @foreach ($businesses as $b)
                            <option value="{{ $b->id }}" @selected(old('business_id') == $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @error('business_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label for="message" class="block text-sm font-medium">Describe your issue <span class="text-red-600">*</span></label>
                <textarea id="message" name="message" rows="7" required minlength="5" maxlength="10000"
                    placeholder="Kya hua, kab hua, aur order number / UTR jaisi details likho..."
                    class="{{ $input }}">{{ old('message') }}</textarea>
                @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="attachments" class="block text-sm font-medium">
                    Attachments <span class="font-normal text-zinc-500">(optional, up to 3 · jpg/png/webp/pdf · max 5 MB each)</span>
                </label>
                <input id="attachments" type="file" multiple name="attachments[]" accept=".jpg,.jpeg,.png,.webp,.pdf"
                    class="{{ $input }} file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:font-semibold file:text-emerald-700">
                @error('attachments') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('attachments.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap items-center gap-4 border-t pt-5 dark:border-zinc-700">
                <button type="submit"
                    class="rounded-xl bg-emerald-700 px-6 py-3 font-semibold text-white transition hover:bg-emerald-800">
                    Submit ticket
                </button>
                <a href="{{ route('growya.support.index') }}" class="text-sm underline">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts::app>
