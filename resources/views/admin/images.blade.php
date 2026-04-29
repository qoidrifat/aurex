@extends('layouts.dashboard')

@section('dashboard')
    <span class="aurex-badge">Admin</span>
    <h1 class="mt-4 text-3xl font-semibold tracking-tight">Uploaded images</h1>
    <p class="mt-1 text-sm" style="color: var(--color-muted);">All selfies uploaded by users.</p>

    <div class="mt-8 grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @forelse ($images as $img)
            <div class="aurex-card p-3">
                <div class="aspect-square w-full overflow-hidden rounded-lg" style="background-color: var(--color-surface-elevated);">
                    @if (\Illuminate\Support\Facades\Storage::disk($img->disk)->exists($img->path))
                        <img src="{{ $img->url() }}" class="h-full w-full object-cover" alt="Selfie #{{ $img->id }}">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-xs" style="color: var(--color-muted);">missing file</div>
                    @endif
                </div>
                <p class="mt-2 truncate text-xs" style="color: var(--color-cream-dim);">{{ $img->user?->email ?? 'unknown' }}</p>
                <p class="text-[10px]" style="color: var(--color-muted);">{{ $img->created_at->diffForHumans() }}</p>
            </div>
        @empty
            <p class="text-sm" style="color: var(--color-muted);">No images uploaded yet.</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $images->links() }}</div>
@endsection
