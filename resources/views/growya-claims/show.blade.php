@php
    $isAdmin = auth()->user()->hasRole('super_admin', 'web');
    $isOpen = in_array($claim->status, ['pending', 'under_review'], true);
    $isDecided = in_array($claim->status, ['approved', 'rejected'], true);

    $badge = [
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'under_review' => 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
        'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
    ];

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

    $docLabel = fn($t) => $docTypes[$t] ?? ucfirst(str_replace('_', ' ', (string) $t));
    $statusText = ucwords(str_replace('_', ' ', $claim->status));

    $fileSize = function ($bytes) {
        $bytes = (int) $bytes;
        return $bytes >= 1048576 ? number_format($bytes / 1048576, 1) . ' MB' : max(1, round($bytes / 1024)) . ' KB';
    };

    // Admin ke liye: submission ke baad owner badal gaya to approve block hoga
    $ownerChanged = $claim->business && (int) $claim->owner_id_at_submission !== (int) $claim->business->owner_id;

    $steps = [
        ['Submitted', true, $claim->created_at],
        ['Under review', $claim->status !== 'pending', null],
        ['Decision', $isDecided, $claim->reviewed_at],
    ];

    $card = 'rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 md:p-6';
    $input = 'mt-1 w-full rounded-lg border p-2.5 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100 dark:border-zinc-700 dark:bg-zinc-900';
@endphp

<x-layouts::app :title="'Claim details'">
    <main id="main-content" class="mx-auto max-w-5xl space-y-6 p-4 md:p-8">
        <a href="{{ $isAdmin ? route('growya.claims.admin') : route('growya.claims.mine') }}" wire:navigate
            class="text-sm font-semibold text-emerald-700 underline dark:text-emerald-400">
            ← {{ $isAdmin ? 'All claims' : 'My claims' }}
        </a>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Ownership claim #{{ $claim->id }}</p>
                <h1 class="text-2xl font-bold md:text-3xl">{{ $claim->business?->name ?? 'Deleted business' }}</h1>
                <p class="mt-1 text-sm text-zinc-500">Submitted {{ $claim->created_at?->format('d M Y, h:i A') }}</p>
            </div>
            <span class="rounded-full px-4 py-1.5 text-sm font-semibold {{ $badge[$claim->status] ?? 'bg-zinc-100 text-zinc-600' }}">
                {{ $statusText }}
            </span>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Progress --}}
        <ol class="grid grid-cols-3 gap-2 text-center text-xs font-semibold" aria-label="Claim progress">
            @foreach ($steps as [$text, $done, $when])
                <li>
                    <div class="h-2 rounded-full {{ $done ? ($claim->status === 'rejected' && $text === 'Decision' ? 'bg-red-500' : 'bg-emerald-600') : 'bg-zinc-200 dark:bg-zinc-700' }}"></div>
                    <span class="mt-2 block {{ $done ? 'text-emerald-700 dark:text-emerald-400' : 'text-zinc-400' }}">{{ $text }}</span>
                    @if ($done && $when)
                        <span class="block text-[10px] font-normal text-zinc-400">{{ $when->format('d M Y') }}</span>
                    @endif
                </li>
            @endforeach
        </ol>

        {{-- Outcome message for claimant --}}
        @unless ($isAdmin)
            @if ($claim->status === 'approved')
                <div class="rounded-2xl border border-emerald-300 bg-emerald-50 p-5 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                    <strong>Claim approved ✓</strong> Ab aap is business ke owner hain.
                </div>
            @elseif ($claim->status === 'rejected')
                <div class="rounded-2xl border border-red-300 bg-red-50 p-5 text-sm text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                    <strong>Claim reject hua.</strong> Naye documents ke saath dobara claim bhej sakte ho.
                </div>
            @else
                <div class="rounded-2xl border border-amber-300 bg-amber-50 p-5 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                    Claim review mein hai. Admin approval ke baad hi business ka access milega.
                </div>
            @endif
        @endunless

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">

                {{-- Claim details --}}
                <section class="{{ $card }}">
                    <h2 class="mb-4 text-lg font-bold">Claim details</h2>
                    <dl class="grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-400">Claimant</dt>
                            <dd class="mt-1 font-semibold">{{ $claim->claimant?->name }}</dd>
                            @if ($isAdmin)
                                <dd class="text-xs text-zinc-500">{{ $claim->claimant?->email }}</dd>
                            @endif
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-400">Claimed role</dt>
                            <dd class="mt-1">{{ $roles[$claim->claimed_role] ?? ucfirst(str_replace('_', ' ', (string) $claim->claimed_role)) }}</dd>
                        </div>
                        @if ($isAdmin)
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-zinc-400">Current owner</dt>
                                <dd class="mt-1">{{ $claim->business?->owner?->name ?? '—' }}</dd>
                                <dd class="text-xs text-zinc-500">{{ $claim->business?->owner?->email }}</dd>
                            </div>
                        @endif
                        @if ($claim->reviewed_at && $claim->reviewer)
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-zinc-400">Reviewed by</dt>
                                <dd class="mt-1">{{ $claim->reviewer->name }} · {{ $claim->reviewed_at->format('d M Y') }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="mt-4 border-t pt-4 dark:border-zinc-700">
                        <p class="text-xs uppercase tracking-wide text-zinc-400">Reason</p>
                        <p class="mt-1 whitespace-pre-wrap text-sm leading-6">{{ $claim->reason }}</p>
                    </div>

                    @if ($claim->admin_note && ($isAdmin || $isDecided))
                        <p class="mt-4 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                            <strong>Admin note:</strong> {{ $claim->admin_note }}
                        </p>
                    @endif
                </section>

                {{-- Documents --}}
                <section class="{{ $card }}">
                    <h2 class="mb-1 text-lg font-bold">Private supporting documents</h2>
                    <p class="mb-4 text-xs text-zinc-500">Ye files private storage mein hain. Sirf download se khulengi.</p>

                    <div class="divide-y dark:divide-zinc-700">
                        @forelse ($claim->documents as $doc)
                            <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <p class="font-semibold">{{ $docLabel($doc->document_type) }}</p>
                                    <p class="truncate text-xs text-zinc-500">{{ $doc->original_name }} · {{ $fileSize($doc->file_size) }}</p>
                                </div>
                                <a href="{{ route('growya.claims.document', [$claim, $doc]) }}"
                                    class="inline-flex rounded-lg border px-3 py-1.5 text-sm font-semibold text-emerald-700 hover:border-emerald-600 dark:border-zinc-700 dark:text-emerald-400">
                                    ⬇ Download
                                </a>
                            </div>
                        @empty
                            <p class="py-4 text-sm text-red-600">Koi document upload nahi hua.</p>
                        @endforelse
                    </div>
                </section>

                {{-- Audit (admin only) --}}
                @if ($isAdmin)
                    <section class="{{ $card }}">
                        <h2 class="mb-4 text-lg font-bold">Audit history</h2>

                        <ol class="relative space-y-4 border-l border-zinc-200 pl-4 text-sm dark:border-zinc-700">
                            @forelse ($claim->events->sortByDesc('id') as $event)
                                <li class="relative">
                                    <span class="absolute -left-[1.4rem] top-1.5 h-2.5 w-2.5 rounded-full bg-emerald-600"></span>
                                    <p class="font-medium">{{ ucwords(str_replace('_', ' ', $event->action)) }}</p>
                                    @if ($event->notes)
                                        <p class="text-zinc-600 dark:text-zinc-300">{{ $event->notes }}</p>
                                    @endif
                                    <p class="text-xs text-zinc-400">{{ $event->actor?->name ?? 'System' }} · {{ $event->created_at?->format('d M Y, H:i') }}</p>
                                </li>
                            @empty
                                <li class="text-zinc-500">No activity yet.</li>
                            @endforelse
                        </ol>
                    </section>
                @endif
            </div>

            {{-- Admin decision --}}
            @if ($isAdmin && $isOpen)
                <aside>
                    <section class="{{ $card }} space-y-4 lg:sticky lg:top-6" x-data="{ decision: '{{ old('decision', 'under_review') }}' }">
                        <h2 class="text-lg font-bold">Admin decision</h2>

                        <p class="rounded-lg bg-amber-50 p-3 text-xs leading-5 text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                            Documents independently check karo aur maujooda owner ke haq verify karo. <strong>Approve</strong> karne par ownership turant transfer hoti hai aur purane owner ka access hat jata hai.
                        </p>

                        @if ($ownerChanged)
                            <p class="rounded-lg bg-red-50 p-3 text-xs font-semibold leading-5 text-red-800 dark:bg-red-950 dark:text-red-200">
                                Claim ke baad business ka owner badal chuka hai. Approve nahi ho sakta. Naya claim maango.
                            </p>
                        @endif

                        <form method="POST" action="{{ route('growya.claims.review', $claim) }}" class="space-y-4">
                            @csrf @method('PATCH')
                            <input type="hidden" name="expected_owner_id" value="{{ $claim->business->owner_id }}">

                            <label class="block text-sm font-medium">Decision
                                <select name="decision" x-model="decision" required class="{{ $input }}">
                                    <option value="under_review">Under review</option>
                                    <option value="rejected">Reject</option>
                                    <option value="approved">Approve &amp; transfer ownership</option>
                                </select>
                            </label>

                            <label class="block text-sm font-medium">Admin note <span class="text-red-600">*</span>
                                <textarea name="admin_note" required minlength="10" maxlength="2000" rows="4"
                                    placeholder="Verification evidence / wajah (min 10 characters)"
                                    class="{{ $input }}">{{ old('admin_note') }}</textarea>
                                @error('admin_note') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                <span class="mt-1 block text-xs font-normal text-zinc-400">Approve ya reject par ye note claimant ko dikhega. Private details mat likho.</span>
                            </label>

                            <label class="block text-sm font-medium" x-show="decision === 'approved'" x-cloak>
                                Exact business name type karo: <strong>{{ $claim->business->name }}</strong>
                                <input name="confirmation" autocomplete="off" value="{{ old('confirmation') }}"
                                    :required="decision === 'approved'" class="{{ $input }}">
                                @error('confirmation') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <button type="submit" @disabled($ownerChanged)
                                onclick="return confirm('Confirm the selected decision. APPROVE transfers ownership and revokes prior owner business membership.')"
                                :class="decision === 'approved' ? 'bg-red-700 hover:bg-red-800' : 'bg-emerald-700 hover:bg-emerald-800'"
                                class="w-full rounded-xl px-5 py-3 font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50">
                                <span x-text="decision === 'approved' ? 'Approve & transfer ownership' : (decision === 'rejected' ? 'Reject claim' : 'Mark under review')"></span>
                            </button>
                        </form>
                    </section>
                </aside>
            @endif
        </div>
    </main>
</x-layouts::app>
