@php
    $roles = [
        'proprietor' => 'Proprietor',
        'partner' => 'Partner',
        'director' => 'Director',
        'authorized_representative' => 'Authorized Representative',
    ];

    $docTypes = [
        'gst' => 'GST Certificate',
        'business_registration' => 'Business Registration',
        'authorization_letter' => 'Authorization Letter',
        'other' => 'Other Supporting Document',
    ];

    $badge = [
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'under_review' => 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
        'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
    ];

    $hasOpenClaim = $latest && in_array($latest->status, ['pending', 'under_review'], true);

    $input =
        'mt-1 w-full rounded-lg border p-3 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100 dark:border-zinc-700 dark:bg-zinc-900';
@endphp

<x-layouts::app :title="'Claim business'">
    <main id="main-content" class="mx-auto max-w-3xl space-y-6 p-4 md:p-8">
        <a href="{{ route('growya.claims.mine') }}" wire:navigate
            class="text-sm font-semibold text-emerald-700 underline dark:text-emerald-400">← My claims</a>

        <div>
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Ownership claim</p>
            <h1 class="text-2xl font-bold md:text-3xl">Claim {{ $business->name }}</h1>
            <p class="mt-1 text-sm text-zinc-500">
                Legal ownership ka proof do. Claim submit karne se business ka management access nahi milta. Admin
                approval ke baad hi milega.
            </p>
        </div>

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        @if ($latest)
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border p-4 text-sm dark:border-zinc-700">
                <span>Pichla claim <strong>#{{ $latest->id }}</strong></span>
                <span class="flex items-center gap-3">
                    <span
                        class="rounded-full px-3 py-1 text-xs font-semibold {{ $badge[$latest->status] ?? 'bg-zinc-100 text-zinc-600' }}">
                        {{ ucwords(str_replace('_', ' ', $latest->status)) }}
                    </span>
                    <a class="font-semibold text-emerald-700 underline dark:text-emerald-400"
                        href="{{ route('growya.claims.show', $latest) }}">Details</a>
                </span>
            </div>
        @endif

        @if ($hasOpenClaim)
            <div
                class="rounded-2xl border border-amber-300 bg-amber-50 p-5 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                Is business ke liye aapka claim abhi review mein hai. Naya claim tab bhej sakte ho jab ye decide ho
                jaye.
            </div>
        @else
            <form method="POST" enctype="multipart/form-data" action="{{ route('growya.claims.store', $business) }}"
                class="space-y-5 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 md:p-6">
                @csrf

                <div>
                    <label for="claimed_role" class="block text-sm font-medium">Business se aapka rishta <span
                            class="text-red-600">*</span></label>
                    <select id="claimed_role" name="claimed_role" required class="{{ $input }}">
                        @foreach ($roles as $key => $label)
                            <option value="{{ $key }}" @selected(old('claimed_role') === $key)>{{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('claimed_role')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reason" class="block text-sm font-medium">Ownership claim samjhao <span
                            class="text-red-600">*</span></label>
                    <textarea id="reason" name="reason" minlength="20" maxlength="3000" required rows="5"
                        placeholder="Aap is business ke owner kaise hain? Kab se? Listing kisne banayi?" class="{{ $input }}">{{ old('reason') }}</textarea>
                    <p class="mt-1 text-xs text-zinc-400">Kam se kam 20 characters.</p>
                    @error('reason')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="document_type" class="block text-sm font-medium">Document type <span
                                class="text-red-600">*</span></label>
                        <select id="document_type" name="document_type" required class="{{ $input }}">
                            @foreach ($docTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('document_type') === $key)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('document_type')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="document" class="block text-sm font-medium">Supporting document <span
                                class="text-red-600">*</span></label>
                        <input id="document" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp"
                            required
                            class="{{ $input }} file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:font-semibold file:text-emerald-700">
                        <p class="mt-1 text-xs text-zinc-400">PDF / JPG / PNG / WebP, max 5 MB.</p>
                        @error('document')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="declaration" value="1" required @checked(old('declaration'))
                        class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-emerald-700">
                    <span>Main confirm karta/karti hoon ki ye documents aur mere statements authentic hain.</span>
                </label>
                @error('declaration')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror

                <div class="flex flex-wrap items-center gap-4 border-t pt-5 dark:border-zinc-700">
                    <button type="submit"
                        class="rounded-xl bg-emerald-700 px-6 py-3 font-semibold text-white transition hover:bg-emerald-800">
                        Submit claim
                    </button>
                    <a href="{{ route('growya.claims.mine') }}" class="text-sm underline">Cancel</a>
                </div>
            </form>
        @endif
    </main>
</x-layouts::app>
