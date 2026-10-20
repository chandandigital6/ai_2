<x-layouts::app :title="'Team · '.$business->name">
<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-sm text-emerald-700">{{ $business->name }} / Workspace</p><h1 class="text-2xl font-bold">Team & permissions</h1><p class="mt-1 text-sm text-zinc-500">Permissions apply only to this business. A role label never grants extra access.</p></div><a class="rounded-xl border px-4 py-2" href="{{ route('businesses.staff.history',$business) }}">Access history</a></div>
    @if(session('success'))<div role="status" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if($canManage)
    <details class="rounded-2xl border bg-white p-5 dark:bg-zinc-900" @if($errors->any()) open @endif><summary class="cursor-pointer text-lg font-semibold">+ Add staff or manager</summary>
        <p class="my-3 text-sm text-zinc-500">Enter an existing account's email, or fill name, mobile and password to create a new account.</p>
        <form method="POST" action="{{ route('businesses.staff.store',$business) }}" class="space-y-5">@csrf
            <div class="grid gap-4 md:grid-cols-3">
            @foreach(['email'=>'Email *','name'=>'Name (new account)','phone'=>'Mobile (new account)'] as $key=>$label)<label>{{ $label }}<input name="{{ $key }}" type="{{ $key === 'email' ? 'email' : 'text' }}" value="{{ old($key) }}" @if($key === 'email') required @endif class="mt-1 w-full rounded-xl border bg-transparent px-3 py-2.5"></label>@endforeach
            <label>Password (new account)<input name="password" type="password" minlength="12" autocomplete="new-password" class="mt-1 w-full rounded-xl border bg-transparent px-3 py-2.5"></label>
            <label>Confirm password<input name="password_confirmation" type="password" autocomplete="new-password" class="mt-1 w-full rounded-xl border bg-transparent px-3 py-2.5"></label>
            <label>Role<select name="role" class="mt-1 w-full rounded-xl border bg-transparent px-3 py-2.5">@foreach($roles as $key=>$label)<option value="{{ $key }}" @selected(old('role','staff') === $key)>{{ $label }}</option>@endforeach</select></label>
            </div>
            @include('businesses.staff.permissions',['selected'=>old('permissions',[])])
            <button class="rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white">Add team member</button>
        </form>
    </details>
    @endif
    <div class="space-y-3">@forelse($staff as $member)
        <details class="rounded-2xl border bg-white p-5 dark:bg-zinc-900"><summary class="flex cursor-pointer flex-wrap justify-between gap-2"><span class="font-semibold">{{ $member->user?->name ?? 'Deleted account' }} <span class="text-sm font-normal text-zinc-500">{{ $member->user?->email }}</span></span><span class="text-sm">{{ $roles[$member->role] ?? ucfirst($member->role) }} · {{ $member->is_active ? 'Active' : 'Inactive' }} · {{ $member->permissions->count() }} permissions</span></summary>
        @if($member->role !== 'owner' && $canManage)
            <form method="POST" action="{{ route('businesses.staff.update',[$business,$member]) }}" class="mt-5 space-y-4">@csrf @method('PUT')
                <div class="flex flex-wrap gap-4"><label>Role <select name="role" class="rounded-lg border bg-transparent px-3 py-2">@foreach($roles as $key=>$label)<option value="{{ $key }}" @selected($member->role === $key)>{{ $label }}</option>@endforeach</select></label><label>Status <select name="is_active" class="rounded-lg border bg-transparent px-3 py-2"><option value="1" @selected($member->is_active)>Active</option><option value="0" @selected(!$member->is_active)>Inactive</option></select></label></div>
                @include('businesses.staff.permissions',['selected'=>$member->permissions->pluck('id')->all()])
                <button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-white">Save access</button>
            </form>
            <form method="POST" action="{{ route('businesses.staff.destroy',[$business,$member]) }}" class="mt-4" onsubmit="return confirm('Remove this member from this business?')">@csrf @method('DELETE')<button class="rounded-xl border border-red-300 px-4 py-2 text-red-700">Remove from business</button></form>
        @else <p class="mt-4 text-sm text-zinc-500">{{ $member->role === 'owner' ? 'The recorded owner has full business access and is protected.' : $member->permissions->pluck('name')->join(', ') }}</p>@endif
        </details>
    @empty <p class="rounded-xl border p-6">No team members yet.</p>@endforelse</div>
    {{ $staff->links() }}
</div>
</x-layouts::app>
