@extends('layouts.dashboard')

@section('dashboard')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Analysis history</h1>
            <p class="mt-1 text-sm" style="color: var(--color-muted);">Every AI run you've done on AUREX.</p>
        </div>
        <a href="{{ route('analysis.create') }}" class="aurex-btn aurex-btn-primary">
            <x-icon name="sparkle" class="h-4 w-4" />
            New analysis
        </a>
    </div>

    @if ($analyses->isEmpty())
        <div class="aurex-card mt-8 text-center">
            <p class="text-sm" style="color: var(--color-cream-dim);">No analyses yet. Upload your first selfie to see your AUREX profile.</p>
            <a href="{{ route('analysis.create') }}" class="aurex-btn aurex-btn-primary mt-6 inline-flex">Upload selfie</a>
        </div>
    @else
        <div class="mt-8 overflow-hidden rounded-2xl border" style="border-color: var(--color-border);">
            <table class="min-w-full text-sm">
                <thead style="background-color: var(--color-surface-elevated); color: var(--color-muted);">
                    <tr class="text-left text-xs uppercase tracking-widest">
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Score</th>
                        <th class="px-6 py-4">Face shape</th>
                        <th class="px-6 py-4">Undertone</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($analyses as $a)
                        <tr class="border-t" style="border-color: var(--color-border); background-color: var(--color-surface);">
                            <td class="px-6 py-4">
                                <p class="font-medium">{{ $a->created_at->toFormattedDateString() }}</p>
                                <p class="text-xs" style="color: var(--color-muted);">{{ $a->created_at->diffForHumans() }}</p>
                            </td>
                            <td class="px-6 py-4 text-lg font-semibold">{{ $a->style_score ?? '—' }}</td>
                            <td class="px-6 py-4 capitalize">{{ $a->face_shape ?? '—' }}</td>
                            <td class="px-6 py-4 capitalize">{{ $a->skin_undertone ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="aurex-badge">{{ ucfirst($a->status) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('analysis.show', $a) }}" class="aurex-link text-xs">View details →</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $analyses->links() }}
        </div>
    @endif
@endsection
