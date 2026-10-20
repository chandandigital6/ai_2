
<x-layouts::app title="Review Business">

<div class="mx-auto max-w-5xl space-y-6 p-4 md:p-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">
                Review Business
            </h1>

            <p class="mt-1 text-zinc-500">
                {{ $business->name }}
            </p>
        </div>

        <a
            href="{{ route('admin.business-verifications.index') }}"
            wire:navigate
            class="rounded-xl border border-zinc-300 px-4 py-2 dark:border-zinc-700"
        >
            Back to Approvals
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-4 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl bg-red-50 p-4 text-red-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">

        <h2 class="mb-5 text-lg font-bold">
            Business Information
        </h2>

        <div class="grid gap-5 sm:grid-cols-2">

            <div>
                <p class="text-sm text-zinc-500">Business Name</p>
                <p class="font-semibold">{{ $business->name }}</p>
            </div>

            <div>
                <p class="text-sm text-zinc-500">Owner</p>
                <p class="font-semibold">
                    {{ $business->owner?->name ?? 'N/A' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-zinc-500">Mobile</p>
                <p>{{ $business->phone ?: 'N/A' }}</p>
            </div>

            <div>
                <p class="text-sm text-zinc-500">Email</p>
                <p>{{ $business->email ?: 'N/A' }}</p>
            </div>

            <div>
                <p class="text-sm text-zinc-500">Business Status</p>
                <p class="font-semibold">
                    {{ ucfirst($business->status) }}
                </p>
            </div>

            <div>
                <p class="text-sm text-zinc-500">Verification Status</p>
                <p class="font-semibold">
                    {{ ucfirst($business->verification_status) }}
                </p>
            </div>

        </div>

        <div class="mt-5 border-t border-zinc-200 pt-5 dark:border-zinc-700">
            <p class="text-sm text-zinc-500">Address</p>
            <p class="mt-1">
                {{ $business->address ?: 'N/A' }}
            </p>
        </div>

        <div class="mt-5 border-t border-zinc-200 pt-5 dark:border-zinc-700">
            <p class="text-sm text-zinc-500">Description</p>
            <p class="mt-1 whitespace-pre-line">
                {{ $business->description ?: 'N/A' }}
            </p>
        </div>
    </div>

    {{-- Approve --}}
    <div class="grid gap-5 lg:grid-cols-2">

        <div class="rounded-2xl border border-green-200 p-6 dark:border-green-900">
            <h2 class="text-lg font-bold">
                Approve Business
            </h2>

            <p class="mt-2 text-sm text-zinc-500">
                Approve this business for public listing.
                Business must also be Active to appear publicly.
            </p>

            @if($business->verification_status !== 'approved')
                <form
                    method="POST"
                    action="{{ route('admin.business-verifications.approve', $business) }}"
                    class="mt-5"
                    onsubmit="return confirm('Approve this business?')"
                >
                    @csrf
                    @method('PATCH')

                    <button
                        type="submit"
                        class="rounded-xl bg-green-600 px-6 py-2.5 font-semibold text-white"
                    >
                        Approve Business
                    </button>
                </form>
            @else
                <p class="mt-5 font-semibold text-green-600">
                    This business is already approved.
                </p>
            @endif
        </div>

        {{-- Reject --}}
        <div class="rounded-2xl border border-red-200 p-6 dark:border-red-900">
            <h2 class="text-lg font-bold">
                Reject Business
            </h2>

            <p class="mt-2 text-sm text-zinc-500">
                Enter a reason before rejecting the business.
            </p>

            <form
                method="POST"
                action="{{ route('admin.business-verifications.reject', $business) }}"
                class="mt-5 space-y-4"
                onsubmit="return confirm('Reject this business?')"
            >
                @csrf
                @method('PATCH')

                <textarea
                    name="reason"
                    required
                    minlength="5"
                    maxlength="2000"
                    rows="4"
                    placeholder="Example: Business contact details could not be verified."
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800"
                >{{ old('reason') }}</textarea>

                <button
                    type="submit"
                    class="rounded-xl bg-red-600 px-6 py-2.5 font-semibold text-white"
                >
                    Reject Business
                </button>
            </form>
        </div>
    </div>

    {{-- Move Back to Pending --}}
    @if($business->verification_status !== 'pending')
        <form
            method="POST"
            action="{{ route('admin.business-verifications.pending', $business) }}"
            onsubmit="return confirm('Move this business back to pending verification?')"
        >
            @csrf
            @method('PATCH')

            <button
                type="submit"
                class="rounded-xl border border-amber-400 px-5 py-2.5 font-semibold text-amber-600"
            >
                Move Back to Pending
            </button>
        </form>
    @endif

    {{-- Approval History --}}
    <div class="rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">

        <h2 class="mb-5 text-lg font-bold">
            Verification History
        </h2>

        <div class="space-y-4">

            @forelse($business->verificationLogs as $log)

                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="font-semibold">
                            {{ ucfirst($log->old_status ?: 'Unknown') }}
                            →
                            {{ ucfirst($log->new_status) }}
                        </p>

                        <p class="text-xs text-zinc-500">
                            {{ $log->created_at->format('d M Y, h:i A') }}
                        </p>
                    </div>

                    <p class="mt-2 text-sm text-zinc-500">
                        By:
                        {{ $log->performedBy?->name ?? 'Former Admin / System' }}
                    </p>

                    @if($log->reason)
                        <div class="mt-3 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                            <p class="text-sm font-medium">
                                Reason
                            </p>

                            <p class="mt-1 whitespace-pre-line text-sm">
                                {{ $log->reason }}
                            </p>
                        </div>
                    @endif

                </div>

            @empty
                <p class="text-sm text-zinc-500">
                    No verification actions recorded yet.
                </p>
            @endforelse

        </div>

    </div>

</div>

</x-layouts::app>
