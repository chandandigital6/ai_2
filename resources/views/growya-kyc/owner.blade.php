@php
    $docTypes = [
        'gst_certificate' => 'GST Certificate',
        'pan' => 'PAN Document',
        'business_registration' => 'Business Registration',
        'owner_identity' => 'Owner Identity',
        'address_proof' => 'Address Proof',
    ];

    $entities = [
        'proprietorship' => 'Proprietorship',
        'partnership' => 'Partnership',
        'llp' => 'LLP',
        'private_limited' => 'Private Limited',
        'other' => 'Other',
    ];

    $badge = [
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
    ];

    $docLabel = fn($type) => $docTypes[$type] ?? ucfirst(str_replace('_', ' ', (string) $type));

    $latest = $submissions->first();
    $isPending = $latest?->status === 'pending';
    $isApproved = $latest?->status === 'approved';
    $reverifyDue = $isApproved && $latest->reverify_by && $latest->reverify_by->isPast();
    $formOpen = !$isApproved || $reverifyDue || $errors->any();

    $card = 'rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 md:p-6';
    $input =
        'mt-1 w-full rounded-lg border p-3 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100 dark:border-zinc-700 dark:bg-zinc-900';
    $fileInput =
        $input .
        ' file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:font-semibold file:text-emerald-700';
@endphp

<x-layouts::app title="Business KYC" :business="$business">
    <div id="main-content" class="mx-auto max-w-4xl space-y-6 p-4 md:p-8">
        <div>
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Verification</p>
            <h1 class="text-2xl font-bold md:text-3xl">Business KYC</h1>
            <p class="mt-1 text-sm text-zinc-500">
                {{ $business->name }} · Documents safely submit karo. Business approval aur KYC alag cheezein hain.
            </p>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200"
                role="status">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Current status --}}
        @if (!$latest)
            <div
                class="rounded-2xl border border-dashed p-5 text-sm text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                Abhi tak koi KYC submit nahi hua. Neeche form bharke documents bhejo.
            </div>
        @elseif ($isPending)
            <div
                class="rounded-2xl border border-amber-300 bg-amber-50 p-5 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                <strong>Review pending.</strong> Aapka KYC request #{{ $latest->id }} admin review ke liye gaya hai.
                Tab tak naya request nahi bhej sakte.
            </div>
        @elseif ($isApproved)
            <div
                class="rounded-2xl border border-emerald-300 bg-emerald-50 p-5 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                <strong>KYC approved ✓</strong>
                @if ($latest->reverify_by)
                    <span class="block">
                        Dobara verify karna hai: <strong>{{ $latest->reverify_by->format('d M Y') }}</strong>
                        @if ($reverifyDue)
                            <span class="font-semibold text-red-700">(due)</span>
                        @endif
                    </span>
                @endif
            </div>
        @else
            <div
                class="rounded-2xl border border-red-300 bg-red-50 p-5 text-sm text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                <strong>Pichla KYC reject hua.</strong>
                @if ($latest->review_reason)
                    <span class="block">Wajah: {{ $latest->review_reason }}</span>
                @endif
                <span class="block">Documents theek karke dobara submit karo.</span>
            </div>
        @endif

        {{-- Form --}}
        @unless ($isPending)
            <details class="{{ $card }}" @if ($formOpen) open @endif>
                <summary class="cursor-pointer text-lg font-bold">
                    {{ $latest ? 'Submit / Resubmit KYC' : 'Submit KYC' }}
                </summary>

                <form action="{{ route('growya.kyc.submit', $business) }}" method="POST" enctype="multipart/form-data"
                    class="mt-5 space-y-5">
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="legal_name" class="block text-sm font-medium">Legal business name <span
                                    class="text-red-600">*</span></label>
                            <input id="legal_name" class="{{ $input }}" name="legal_name"
                                value="{{ old('legal_name', $business->name) }}" maxlength="255" required>
                            @error('legal_name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="entity_type" class="block text-sm font-medium">Entity type <span
                                    class="text-red-600">*</span></label>
                            <select id="entity_type" class="{{ $input }}" name="entity_type" required>
                                @foreach ($entities as $k => $v)
                                    <option value="{{ $k }}" @selected(old('entity_type') === $k)>{{ $v }}
                                    </option>
                                @endforeach
                            </select>
                            @error('entity_type')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="rounded-lg bg-zinc-50 p-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        PDF / JPG / PNG / WEBP, har file max 5 MB. Kam se kam <strong>1 document</strong> (max 5) upload
                        karo.
                        Passwords, bank PIN ya zaroorat se zyada personal details mat bhejo.
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($docTypes as $key => $text)
                            <div class="space-y-2 rounded-xl border p-4 dark:border-zinc-700">
                                <label for="doc-{{ $key }}"
                                    class="block text-sm font-semibold">{{ $text }}</label>
                                <input id="doc-{{ $key }}" type="file" name="documents[{{ $key }}]"
                                    accept=".pdf,.jpg,.jpeg,.png,.webp" class="{{ $fileInput }}">
                                @error('documents.' . $key)
                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                @enderror

                                <label for="exp-{{ $key }}" class="block text-xs text-zinc-500">Expiry date <span
                                        class="text-zinc-400">(optional)</span></label>
                                <input id="exp-{{ $key }}" type="date" name="expires_on[{{ $key }}]"
                                    value="{{ old('expires_on.' . $key) }}" min="{{ now()->addDay()->toDateString() }}"
                                    class="w-full rounded-lg border p-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                @error('expires_on.' . $key)
                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                    @error('documents')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @error('documents.*')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="declaration" value="1" required @checked(old('declaration'))
                            class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-emerald-700">
                        <span>Main confirm karta/karti hoon ki main in authentic documents ko business verification ke liye
                            share karne ke liye authorized hoon.</span>
                    </label>
                    @error('declaration')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    <button type="submit"
                        class="rounded-xl bg-emerald-700 px-6 py-3 font-semibold text-white transition hover:bg-emerald-800">
                        Submit KYC for review
                    </button>
                </form>
            </details>
        @endunless

        {{-- History --}}
        <section class="{{ $card }}">
            <h2 class="mb-4 text-lg font-bold">Submission history</h2>

            <div class="space-y-4">
                @forelse ($submissions as $entry)
                    <div class="space-y-3 rounded-xl border p-4 dark:border-zinc-700">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-semibold">
                                #{{ $entry->id }}
                                <span class="font-normal text-zinc-500">·
                                    {{ $entry->created_at?->format('d M Y, h:i A') }}</span>
                            </p>
                            <span
                                class="rounded-full px-3 py-1 text-xs font-semibold {{ $badge[$entry->status] ?? 'bg-zinc-100 text-zinc-600' }}">
                                {{ ucfirst($entry->status) }}
                            </span>
                        </div>

                        @if ($entry->status === 'approved' && $entry->reverify_by)
                            <p class="text-sm text-zinc-500">Reverify by {{ $entry->reverify_by->format('d M Y') }}</p>
                        @endif

                        @if ($entry->review_reason)
                            <p class="rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                                <strong>Review note:</strong> {{ $entry->review_reason }}
                            </p>
                        @endif

                        <div class="flex flex-wrap gap-2">
                            @foreach ($entry->documents as $document)
                                <a class="inline-flex items-center rounded-lg border px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:border-emerald-600 dark:border-zinc-700 dark:text-emerald-400"
                                    href="{{ route('growya.kyc.download', [$entry, $document]) }}">
                                    ⬇ {{ $docLabel($document->document_type) }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No KYC requests submitted.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts::app>
