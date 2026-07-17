@extends('layouts.dashboard')

@section('dashboard')
    <span class="aurex-badge">Admin</span>
    <h1 class="mt-4 text-3xl font-semibold tracking-tight">Analyses</h1>
    <p class="mt-1 text-sm" style="color: var(--color-muted);">All AI runs across AUREX.</p>

    <div class="mt-8 overflow-hidden rounded-2xl border" style="border-color: var(--color-border);">
        <table class="min-w-full text-sm">
            <thead style="background-color: var(--color-surface-elevated); color: var(--color-muted);">
                <tr class="text-left text-xs uppercase tracking-widest">
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">User</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Score</th>
                    <th class="px-6 py-4">Face shape</th>
                    <th class="px-6 py-4">Created</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($analyses as $a)
                    <tr class="border-t" style="border-color: var(--color-border); background-color: var(--color-surface);">
                        <td class="px-6 py-4 font-mono text-xs">#{{ $a->id }}</td>
                        <td class="px-6 py-4">{{ $a->user?->email ?? '—' }}</td>
                        <td class="px-6 py-4"><span class="aurex-badge">{{ ucfirst($a->status) }}</span></td>
                        <td class="px-6 py-4">{{ $a->style_score ?? '—' }}</td>
                        <td class="px-6 py-4 capitalize">{{ $a->face_shape ?? '—' }}</td>
                        <td class="px-6 py-4 text-xs" style="color: var(--color-muted);">{{ $a->created_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $analyses->links() }}</div>
@endsection
