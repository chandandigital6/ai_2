<x-layouts::app :title="'Staff History - '.$business->name">
<div class="mx-auto max-w-6xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h1 class="text-2xl font-bold">Staff Permission & Access History</h1><p class="text-zinc-500">{{ $business->name }}</p></div>
        <a href="{{ route('businesses.staff.index', $business) }}" class="rounded-lg border px-4 py-2">Back to Staff</a>
    </div>
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-zinc-100 dark:bg-zinc-800"><tr><th class="p-3">When</th><th class="p-3">Changed by</th><th class="p-3">Staff</th><th class="p-3">Action</th><th class="p-3">Before</th><th class="p-3">After</th></tr></thead>
            <tbody>
            @forelse($audits as $audit)
            <tr class="border-t border-zinc-200 align-top dark:border-zinc-700">
                <td class="p-3 whitespace-nowrap">{{ $audit->created_at?->format('d M Y, h:i A') }}</td>
                <td class="p-3">{{ $audit->actor?->name ?? 'Deleted user' }}</td>
                <td class="p-3">{{ $audit->subject?->name ?? 'Deleted user' }}</td>
                <td class="p-3 font-semibold">{{ ucfirst($audit->action) }}</td>
                <td class="p-3"><pre class="whitespace-pre-wrap break-words text-xs">{{ json_encode($audit->before_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></td>
                <td class="p-3"><pre class="whitespace-pre-wrap break-words text-xs">{{ json_encode($audit->after_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></td>
            </tr>
            @empty <tr><td colspan="6" class="p-6 text-center">No changes recorded yet.</td></tr> @endforelse
            </tbody>
        </table>
    </div>
    {{ $audits->links() }}
</div>
</x-layouts::app>
