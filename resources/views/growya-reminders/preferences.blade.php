<x-layouts::app :title="'Reminder preferences'">
<div class="mx-auto max-w-3xl space-y-5 px-5 py-8">
  <div><h1 class="text-2xl font-bold">Reminder preferences</h1><p class="text-sm text-zinc-500">Choose which automatic reminders appear in your Growya notification bell.</p></div>
  @if(session('success'))<div class="rounded-xl border border-emerald-300 p-4 text-emerald-700">{{session('success')}}</div>@endif
  @if($errors->any())<div class="rounded-xl border border-red-300 p-4 text-red-700">{{$errors->first()}}</div>@endif
  <form action="{{route('growya.reminders.preferences.update')}}" method="POST" class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
    @csrf @method('PATCH')
    @foreach(['wishlist_reminders'=>'Wishlist follow-ups','chat_reminders'=>'Chat follow-ups','subscription_reminders'=>'Subscription expiry','promotion_reminders'=>'Promotion expiry','support_reminders'=>'Support ticket overdue'] as $field=>$label)
      <label class="flex items-center justify-between gap-4 border-b border-zinc-100 py-3 dark:border-zinc-800">
        <span class="font-medium">{{$label}}</span>
        <span><input type="hidden" name="{{$field}}" value="0"><input type="checkbox" name="{{$field}}" value="1" @checked((bool)old($field,$settings?->{$field} ?? true)) class="h-5 w-5 rounded accent-emerald-600"></span>
      </label>
    @endforeach
    <button class="rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white">Save preferences</button>
  </form>
</div>
</x-layouts::app>
