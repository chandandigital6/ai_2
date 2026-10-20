<x-layouts::app :title="__('Roles & Permissions')">
<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
    <header class="rounded-3xl bg-slate-950 p-7 text-white shadow-xl">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><p class="text-xs font-semibold uppercase tracking-widest text-emerald-300">Growya / Administration</p>
            <h1 class="mt-2 text-3xl font-extrabold">Roles & Permissions</h1>
            <p class="mt-2 text-sm text-slate-300">Manage global access. Business staff permissions are managed separately per business.</p></div>
            <a href="{{ route('dashboard') }}" class="rounded-xl border border-slate-500 px-4 py-2 text-sm font-semibold hover:bg-slate-800">Dashboard →</a>
        </div>
    </header>
    @if(session('success')) <div role="status" class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-900">{{ session('success') }}</div> @endif
    @if($errors->any()) <div role="alert" class="rounded-xl bg-red-50 p-4 text-red-700">{{ $errors->first() }}</div> @endif
    <section class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900"><p class="text-sm text-zinc-500">Roles</p><p class="mt-2 text-3xl font-bold">{{ $roles->count() }}</p></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900"><p class="text-sm text-zinc-500">Permissions</p><p class="mt-2 text-3xl font-bold">{{ $permissions->flatten()->count() }}</p></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900"><p class="text-sm text-zinc-500">Security</p><p class="mt-2 font-bold text-emerald-700">Super Admin Only</p></div>
    </section>
    <div class="grid gap-6 xl:grid-cols-2">
    @foreach($roles as $role)
      <form method="POST" action="{{ route('growya.roles.matrix.update', $role) }}" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        @csrf @method('PATCH')
        <div class="flex items-center justify-between gap-3 border-b border-zinc-200 p-5 dark:border-zinc-700">
          <div><h2 class="text-lg font-bold">{{ ucwords(str_replace('_',' ', $role->name)) }}</h2><p class="text-xs text-zinc-500">{{ $role->permissions->count() }} permissions granted</p></div>
          @if($role->name === 'super_admin')<span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900">Protected</span>@else
          <button class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save Changes</button>@endif
        </div>
        <div class="max-h-[540px] space-y-5 overflow-y-auto p-5">
          @foreach($permissions as $group => $items)
            <fieldset><legend class="mb-3 text-xs font-extrabold uppercase tracking-wider text-zinc-500">{{ str_replace('_', ' ', $group) }}</legend>
            <div class="grid gap-2 sm:grid-cols-2">
            @foreach($items as $permission)
            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-100 px-3 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800">
            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="rounded border-zinc-300 text-emerald-600" @checked($role->hasPermissionTo($permission)) @disabled($role->name === 'super_admin')>
            <span class="break-all">{{ $permission->name }}</span></label>
            @endforeach</div></fieldset>
          @endforeach
        </div>
      </form>
    @endforeach
    </div>
    <p class="text-sm text-zinc-500">Changes to this matrix affect Spatie global role permissions. Existing business-specific access checks and approval requirements still apply.</p>
</div>
</x-layouts::app>
