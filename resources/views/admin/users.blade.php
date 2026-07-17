@extends('layouts.dashboard')

@section('dashboard')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <span class="aurex-badge">Admin</span>
            <h1 class="mt-4 text-3xl font-semibold tracking-tight">Users</h1>
            <p class="mt-1 text-sm" style="color: var(--color-muted);">All registered users on AUREX.</p>
        </div>
        <form method="GET" action="{{ route('admin.users') }}">
            <input type="text" name="q" value="{{ $q }}" placeholder="Search name or email" class="aurex-input w-72">
        </form>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border" style="border-color: var(--color-border);">
        <table class="min-w-full text-sm">
            <thead style="background-color: var(--color-surface-elevated); color: var(--color-muted);">
                <tr class="text-left text-xs uppercase tracking-widest">
                    <th class="px-6 py-4">User</th>
                    <th class="px-6 py-4">Email</th>
                    <th class="px-6 py-4">Role</th>
                    <th class="px-6 py-4">Plan</th>
                    <th class="px-6 py-4">Analyses</th>
                    <th class="px-6 py-4">Joined</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $u)
                    <tr class="border-t" style="border-color: var(--color-border); background-color: var(--color-surface);">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-semibold" style="background-color: var(--color-surface-elevated);">
                                    {{ $u->initials() }}
                                </div>
                                <span class="font-medium">{{ $u->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">{{ $u->email }}</td>
                        <td class="px-6 py-4"><span class="aurex-badge">{{ ucfirst($u->role) }}</span></td>
                        <td class="px-6 py-4">{{ ucfirst($u->plan) }}</td>
                        <td class="px-6 py-4">{{ $u->analyses_count }}</td>
                        <td class="px-6 py-4 text-xs" style="color: var(--color-muted);">{{ $u->created_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $users->links() }}</div>
@endsection
