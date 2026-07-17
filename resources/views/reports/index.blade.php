@extends('layouts.dashboard')

@section('dashboard')
    <h1 class="text-3xl font-semibold tracking-tight">Saved style reports</h1>
    <p class="mt-1 text-sm" style="color: var(--color-muted);">Deep-dive reports you've saved from past analyses.</p>

    @if ($reports->isEmpty())
        <div class="aurex-card mt-8 text-center">
            <p class="text-sm" style="color: var(--color-cream-dim);">You haven't saved any style reports yet.</p>
            <a href="{{ route('analysis.create') }}" class="aurex-btn aurex-btn-primary mt-6 inline-flex">Start a new analysis</a>
        </div>
    @else
        <div class="mt-8 grid gap-4 md:grid-cols-2">
            @foreach ($reports as $report)
                <a href="{{ route('reports.show', $report->analysis) }}" class="aurex-card aurex-hover-lift block">
                    <div class="flex items-center justify-between">
                        <span class="aurex-badge">Saved</span>
                        <span class="text-xs" style="color: var(--color-muted);">{{ $report->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="mt-6 text-lg font-semibold">{{ $report->title }}</p>
                    <p class="mt-1 text-sm" style="color: var(--color-cream-dim);">
                        Analysis #{{ $report->analysis_id }} · Score {{ $report->analysis->style_score ?? '—' }} · {{ ucfirst($report->analysis->face_shape ?? '—') }}
                    </p>
                </a>
            @endforeach
        </div>

        <div class="mt-6">{{ $reports->links() }}</div>
    @endif
@endsection
