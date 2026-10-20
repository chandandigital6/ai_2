@auth
@php
$unreadGrowyaNotifications = \App\Models\NotificationRecipient::query()
    ->where('user_id', auth()->id())
    ->whereNull('opened_at')
    ->whereHas('notification', fn ($q) => $q->where('status', 'sent'))
    ->count();
@endphp
<a href="{{ route('growya.notifications.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700" aria-label="Notifications">
    <span>🔔 Notifications</span>
    @if($unreadGrowyaNotifications)
        <span class="rounded-full bg-red-600 px-2 py-0.5 text-xs font-semibold text-white">{{ $unreadGrowyaNotifications > 99 ? '99+' : $unreadGrowyaNotifications }}</span>
    @endif
</a>
@endauth
