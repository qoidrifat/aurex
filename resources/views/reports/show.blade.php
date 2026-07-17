@extends('layouts.dashboard')

@section('dashboard')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <span class="aurex-badge">AUREX Style Report</span>
            <h1 class="mt-4 text-3xl font-semibold tracking-tight">{{ $report->title }}</h1>
            <p class="mt-1 text-sm" style="color: var(--color-muted);">
                Analysis #{{ $analysis->id }} · {{ $analysis->created_at->toFormattedDateString() }}
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('reports.download', $analysis) }}" target="_blank" class="aurex-btn aurex-btn-primary">
                <x-icon name="download" class="h-4 w-4" />
                Download report
            </a>
            <a href="{{ route('analysis.show', $analysis) }}" class="aurex-btn aurex-btn-secondary">View analysis</a>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_2fr]">
        <aside class="aurex-card flex flex-col items-center gap-4 text-center">
            <x-score-ring :score="$analysis->style_score ?? 0" :size="170" />
            <p class="text-sm font-semibold">{{ $analysis->scoreLabel() }}</p>
            @if ($analysis->uploadedImage)
                <img src="{{ $analysis->uploadedImage->url() }}" class="mt-2 h-40 w-32 rounded-xl object-cover" alt="Selfie">
            @endif
            <dl class="mt-4 w-full space-y-3 text-left text-sm">
                <div class="flex items-center justify-between">
                    <dt style="color: var(--color-muted);">Face shape</dt>
                    <dd class="font-medium capitalize">{{ $analysis->face_shape ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt style="color: var(--color-muted);">Skin undertone</dt>
                    <dd class="font-medium capitalize">{{ $analysis->skin_undertone ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt style="color: var(--color-muted);">Style score</dt>
                    <dd class="font-medium">{{ $analysis->style_score ?? '—' }}/100</dd>
                </div>
            </dl>
        </aside>

        <div class="space-y-6">
            <section class="aurex-card">
                <h2 class="text-lg font-semibold">Face shape explanation</h2>
                <p class="mt-2 text-sm leading-relaxed" style="color: var(--color-cream-dim);">
                    {{ $report->face_shape_summary }}
                </p>
            </section>

            <section class="aurex-card">
                <h2 class="text-lg font-semibold">Hairstyle recommendations</h2>
                <p class="mt-2 text-sm leading-relaxed" style="color: var(--color-cream-dim);">
                    {{ $report->hairstyle_summary }}
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($hairstyles as $h)
                        <span class="aurex-badge">{{ $h->label }}</span>
                    @endforeach
                </div>
            </section>

            <section class="aurex-card">
                <h2 class="text-lg font-semibold">Color palette analysis</h2>
                <p class="mt-2 text-sm leading-relaxed" style="color: var(--color-cream-dim);">
                    {{ $report->color_summary }}
                </p>
                <div class="mt-5 flex flex-wrap items-center gap-6">
                    @foreach ($colors as $c)
                        <x-color-swatch :hex="$c->hex_color" :label="$c->label" />
                    @endforeach
                </div>
            </section>

            <section class="aurex-card">
                <h2 class="text-lg font-semibold">Outfit suggestions</h2>
                <p class="mt-2 text-sm leading-relaxed" style="color: var(--color-cream-dim);">
                    {{ $report->outfit_summary }}
                </p>
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($outfits as $o)
                        <li class="flex items-start gap-3">
                            <span class="mt-1 h-1.5 w-1.5 rounded-full" style="background-color: var(--color-rust);"></span>
                            {{ $o->label }}
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="aurex-card">
                <h2 class="text-lg font-semibold">Style improvement tips</h2>
                <pre class="mt-2 whitespace-pre-wrap text-sm leading-relaxed" style="color: var(--color-cream-dim); font-family: inherit;">{{ $report->improvement_tips }}</pre>
            </section>
        </div>
    </div>
@endsection
