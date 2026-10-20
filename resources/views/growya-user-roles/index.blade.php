<x-layouts::app :title="__('User Role Management')">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 md:px-8">
        <div class="rounded-3xl bg-zinc-950 p-6 text-white md:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-400">Growya / Super Admin</p>
                    <h1 class="mt-2 text-2xl font-bold md:text-3xl">Manage User Roles</h1>
                    <p class="mt-2 text-sm text-zinc-300">Assign a global role to existing users. Its saved permissions apply automatically.</p>
                </div>
                @if(\Illuminate\Support\Facades\Route::has('growya.roles.matrix'))
                    <a href="{{ route('growya.roles.matrix') }}" class="rounded-xl border border-zinc-500 px-4 py-2 text-sm font-semibold hover:bg-zinc-800">Edit Role Permissions →</a>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif

        <form method="GET" action="{{ route('growya.users.roles.index') }}" class="flex flex-wrap gap-2">
            <input name="search" value="{{ $search }}" placeholder="Search user name or email" class="min-w-0 flex-1 rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" />
            <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white">Search</button>
        </form>

        <div class="space-y-3">
            @forelse($users as $user)
                @php
                    $isProtected = $user->hasRole('super_admin') || auth()->id() === $user->id;
                    $currentRole = $user->roles->firstWhere('guard_name', 'web');
                @endphp
                <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="grid gap-4 lg:grid-cols-2 lg:items-center">
                        <div>
                            <h2 class="font-bold text-zinc-900 dark:text-white">{{ $user->name }} <span class="text-xs font-normal text-zinc-500">#{{ $user->id }}</span></h2>
                            <p class="mt-1 break-all text-sm text-zinc-500">{{ $user->email }}</p>
                            <p class="mt-2 text-xs text-zinc-500">Current role: <span class="font-bold">{{ $user->roles->pluck('name')->implode(', ') ?: 'No role' }}</span></p>
                            @if($isProtected)<p class="mt-2 text-xs font-semibold text-amber-700">Protected account — role changes disabled</p>@endif
                        </div>
                        @if(!$isProtected)
                            <form method="POST" action="{{ route('growya.users.roles.update', $user) }}" class="flex flex-col gap-2 sm:flex-row">
                                @csrf
                                @method('PATCH')
                                <select name="role_id" required class="min-w-0 flex-1 rounded-xl border border-zinc-300 bg-white px-3 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                    <option value="">Select a role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" @selected((string) old('role_id', $currentRole?->id) === (string) $role->id)>{{ ucwords(str_replace('_', ' ', $role->name)) }} ({{ $role->permissions->count() }} permissions)</option>
                                    @endforeach
                                </select>
                                <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800" type="submit">Save Role</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-zinc-200 p-8 text-center text-zinc-500">No users found.</div>
            @endforelse
        </div>
        {{ $users->links() }}
        <p class="text-xs leading-relaxed text-zinc-500">Global Spatie permissions are inherited from the selected role. Business approval and business-specific membership/permissions remain separate; assigning the owner role does not approve a business.</p>
    </div>
</x-layouts::app>
