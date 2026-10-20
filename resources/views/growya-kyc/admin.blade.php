@php
    $tabs = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'];

    $badge = [
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
    ];
@endphp

<x-layouts::app title="Business KYC Reviews">
    <div id="main-content" class="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
        <div>
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Administration</p>
            <h1 class="text-2xl font-bold md:text-3xl">Business KYC Reviews</h1>
            <p class="mt-1 text-sm text-zinc-500">Business registration approval se alag verification.</p>
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

        {{-- Tabs --}}
        <nav class="flex flex-wrap gap-2" aria-label="Filter by status">
            @foreach ($tabs as $key => $text)
                <a href="{{ route('growya.kyc.admin', ['status' => $key]) }}" wire:navigate
                    @if ($status === $key) aria-current="page" @endif
                    class="rounded-full border px-4 py-1.5 text-sm font-semibold transition
                    {{ $status === $key
                        ? 'border-emerald-700 bg-emerald-700 text-white'
                        : 'border-zinc-300 text-zinc-600 hover:border-emerald-600 hover:text-emerald-700 dark:border-zinc-700 dark:text-zinc-300' }}">
                    {{ $text }}
                </a>
            @endforeach
        </nav>

        <div class="overflow-x-auto rounded-2xl border bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800">
                    <tr>
                        <th class="p-3 font-semibold">ID</th>
                        <th class="p-3 font-semibold">Business</th>
                        <th class="p-3 font-semibold">Submitted by</th>
                        <th class="p-3 font-semibold">Status</th>
                        <th class="p-3 font-semibold">Submitted</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($submissions as $submission)
                        <tr class="border-t transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                            <td class="whitespace-nowrap p-3 text-zinc-500">#{{ $submission->id }}</td>
                            <td class="p-3 font-semibold">{{ $submission->business?->name ?? 'Deleted business' }}</td>
                            <td class="whitespace-nowrap p-3">{{ $submission->submitter?->name }}</td>
                            <td class="p-3">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge[$submission->status] ?? 'bg-zinc-100 text-zinc-600' }}">
                                    {{ ucfirst($submission->status) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap p-3 text-zinc-500">
                                {{ $submission->created_at?->format('d M Y') }}</td>
                            <td class="p-3 text-right">
                                <a href="{{ route('growya.kyc.admin.show', $submission) }}" wire:navigate
                                    class="inline-flex rounded-lg border px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:border-emerald-600 dark:border-zinc-700 dark:text-emerald-400">
                                    {{ $submission->status === 'pending' ? 'Review' : 'View' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center">
                                <p class="font-semibold">No submissions.</p>
                                <p class="mt-1 text-sm text-zinc-500">
                                    {{ $status === 'pending' ? 'Koi KYC review ke liye pending nahi hai.' : 'Is filter mein koi record nahi mila.' }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $submissions->links() }}
    </div>
</x-layouts::app>
