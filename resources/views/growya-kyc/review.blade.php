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

    $fileSize = function ($bytes) {
        $bytes = (int) $bytes;
        return $bytes >= 1048576 ? number_format($bytes / 1048576, 1) . ' MB' : max(1, round($bytes / 1024)) . ' KB';
    };

    $card = 'rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 md:p-6';
    $input =
        'mt-1 w-full rounded-lg border p-2.5 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100 dark:border-zinc-700 dark:bg-zinc-900';
@endphp

<x-layouts::app title="Review Business KYC">
    <div id="main-content" class="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
        <a href="{{ route('growya.kyc.admin') }}" wire:navigate
            class="text-sm font-semibold text-emerald-700 underline dark:text-emerald-400">← All KYC reviews</a>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">KYC review</p>
                <h1 class="text-2xl font-bold md:text-3xl">{{ $submission->business?->name }}</h1>
                <p class="mt-1 text-sm text-zinc-500">Submission #{{ $submission->id }} ·
                    {{ $submission->created_at?->format('d M Y, h:i A') }}</p>
            </div>
            <span
                class="rounded-full px-4 py-1.5 text-sm font-semibold {{ $badge[$submission->status] ?? 'bg-zinc-100 text-zinc-600' }}">
                {{ ucfirst($submission->status) }}
            </span>
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

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">

                {{-- Details --}}
                <section class="{{ $card }}">
                    <h2 class="mb-4 text-lg font-bold">Submission details</h2>
                    <dl class="grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-400">Legal name</dt>
                            <dd class="mt-1 font-semibold">{{ $submission->legal_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-400">Entity type</dt>
                            <dd class="mt-1">
                                {{ $entities[$submission->entity_type] ?? ucfirst($submission->entity_type) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-400">Submitted by</dt>
                            <dd class="mt-1">{{ $submission->submitter?->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-400">Business name on platform</dt>
                            <dd class="mt-1">{{ $submission->business?->name }}</dd>
                        </div>
                        @if ($submission->reviewed_at)
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-zinc-400">Reviewed by</dt>
                                <dd class="mt-1">{{ $submission->reviewer?->name }} ·
                                    {{ $submission->reviewed_at->format('d M Y, h:i A') }}</dd>
                            </div>
                        @endif
                        @if ($submission->reverify_by)
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-zinc-400">Reverify by</dt>
                                <dd class="mt-1">{{ $submission->reverify_by->format('d M Y') }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($submission->review_reason)
                        <p class="mt-4 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                            <strong>Review note:</strong> {{ $submission->review_reason }}
                        </p>
                    @endif

                    @if (strcasecmp(trim($submission->legal_name), trim((string) $submission->business?->name)) !== 0)
                        <p
                            class="mt-4 rounded-lg bg-amber-50 p-3 text-xs text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                            Legal name aur platform par business ka naam alag hain. Documents mein naam check karna.
                        </p>
                    @endif
                </section>

                {{-- Documents --}}
                <section class="{{ $card }}">
                    <h2 class="mb-1 text-lg font-bold">Private documents</h2>
                    <p class="mb-4 text-xs text-zinc-500">Ye files private storage mein hain. Sirf download se khulengi.
                    </p>

                    <div class="divide-y dark:divide-zinc-700">
                        @forelse ($submission->documents as $document)
                            @php $expired = $document->expires_on && $document->expires_on->isPast(); @endphp
                            <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <p class="font-semibold">{{ $docLabel($document->document_type) }}</p>
                                    <p class="truncate text-xs text-zinc-500">
                                        {{ $document->original_name }} · {{ $fileSize($document->file_size) }}
                                    </p>
                                    @if ($document->expires_on)
                                        <p
                                            class="text-xs {{ $expired ? 'font-semibold text-red-600' : 'text-zinc-500' }}">
                                            Expires
                                            {{ $document->expires_on->format('d M Y') }}{{ $expired ? ' (expired)' : '' }}
                                        </p>
                                    @endif
                                </div>
                                <a href="{{ route('growya.kyc.download', [$submission, $document]) }}"
                                    class="inline-flex rounded-lg border px-3 py-1.5 text-sm font-semibold text-emerald-700 hover:border-emerald-600 dark:border-zinc-700 dark:text-emerald-400">
                                    ⬇ Download
                                </a>
                            </div>
                        @empty
                            <p class="py-4 text-sm text-red-600">Koi document upload nahi hua.</p>
                        @endforelse
                    </div>
                </section>

                {{-- Audit --}}
                <section class="{{ $card }}">
                    <h2 class="mb-4 text-lg font-bold">Audit history</h2>

                    <ol class="relative space-y-4 border-l border-zinc-200 pl-4 text-sm dark:border-zinc-700">
                        @forelse ($submission->events->sortByDesc('id') as $event)
                            <li class="relative">
                                <span
                                    class="absolute -left-[1.4rem] top-1.5 h-2.5 w-2.5 rounded-full bg-emerald-600"></span>
                                <p class="font-medium">{{ ucfirst($event->action) }}</p>
                                @if ($event->reason)
                                    <p class="text-zinc-600 dark:text-zinc-300">{{ $event->reason }}</p>
                                @endif
                                <p class="text-xs text-zinc-400">
                                    {{ $event->actor?->name ?? 'System' }} ·
                                    {{ $event->created_at?->format('d M Y, H:i') }}
                                </p>
                            </li>
                        @empty
                            <li class="text-zinc-500">No activity yet.</li>
                        @endforelse
                    </ol>
                </section>
            </div>

            {{-- Decision --}}
            <aside>
                @if ($submission->status === 'pending')
                    <section class="{{ $card }} space-y-4 lg:sticky lg:top-6" x-data="{ decision: '{{ old('decision', 'approved') }}' }">
                        <h2 class="text-lg font-bold">Make decision</h2>

                        <p
                            class="rounded-lg bg-amber-50 p-3 text-xs leading-5 text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                            Approve karne se pehle documents ki authenticity khud verify karo. KYC approval se business
                            listing approve nahi hoti.
                        </p>

                        <form method="POST" action="{{ route('growya.kyc.review', $submission) }}" class="space-y-4">
                            @csrf @method('PATCH')

                            <label class="block text-sm font-medium">Decision
                                <select name="decision" required x-model="decision" class="{{ $input }}">
                                    <option value="approved">Approve KYC</option>
                                    <option value="rejected">Reject KYC</option>
                                </select>
                            </label>

                            <label class="block text-sm font-medium" x-show="decision === 'rejected'" x-cloak>
                                Reason <span class="text-red-600">*</span>
                                <textarea name="reason" rows="3" minlength="5" maxlength="2000" :required="decision === 'rejected'"
                                    placeholder="Owner ko kya theek karna hai?" class="{{ $input }}">{{ old('reason') }}</textarea>
                                @error('reason')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="block text-sm font-medium" x-show="decision === 'approved'" x-cloak>
                                Reverification date <span class="font-normal text-zinc-500">(optional)</span>
                                <input type="date" name="reverify_by" value="{{ old('reverify_by') }}"
                                    min="{{ now()->addDay()->toDateString() }}" class="{{ $input }}">
                                @error('reverify_by')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </label>

                            <button type="submit" onclick="return confirm('Confirm KYC review decision?')"
                                class="w-full rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white transition hover:bg-emerald-800">
                                Save review
                            </button>
                        </form>
                    </section>
                @else
                    <section class="{{ $card }} text-sm text-zinc-600 dark:text-zinc-300">
                        Ye submission review ho chuka hai. Ab decision badla nahi ja sakta.
                    </section>
                @endif
            </aside>
        </div>
    </div>
</x-layouts::app>
