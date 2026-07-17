@extends('layouts.dashboard')

@section('dashboard')
    <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        {{-- Welcome --}}
        <div class="aurex-card overflow-hidden" style="background: radial-gradient(circle at 10% 20%, rgba(183,65,14,0.18), transparent 55%), radial-gradient(circle at 90% 80%, rgba(85,107,47,0.18), transparent 55%), var(--color-surface);">
            <span class="aurex-badge"><x-icon name="sparkle" class="h-3 w-3" /> Welcome back</span>
            <h1 class="mt-4 text-3xl font-semibold">Hey {{ \Illuminate\Support\Str::before($user->name, ' ') }} 👋</h1>
            <p class="mt-2 max-w-xl text-sm" style="color: var(--color-cream-dim);">
                Upload a new selfie to get a fresh AI-generated style report with hairstyle, color palette, and outfit recommendations.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('analysis.create') }}" class="aurex-btn aurex-btn-primary">
                    <x-icon name="sparkle" class="h-4 w-4" />
                    Analyze Your Style
                </a>
                <a href="{{ route('analysis.create') }}" class="aurex-btn aurex-btn-secondary">
                    <x-icon name="upload" class="h-4 w-4" />
                    Upload Selfie
                </a>
            </div>
        </div>

        {{-- Score summary --}}
        <div class="aurex-card flex items-center gap-6">
            <x-score-ring :score="$averageScore" :size="140" label="Avg Score" />
            <div>
                <p class="aurex-label">Your AUREX profile</p>
                <p class="mt-2 text-2xl font-semibold">
                    @if ($latest)
                        {{ ucfirst($latest->face_shape ?? 'Unknown') }} · {{ ucfirst($latest->skin_undertone ?? 'Neutral') }}
                    @else
                        Not analyzed yet
                    @endif
                </p>
                <p class="mt-1 text-xs" style="color: var(--color-muted);">
                    {{ $totalAnalyses }} {{ \Illuminate\Support\Str::plural('analysis', $totalAnalyses) }} on file
                </p>
                @if ($latest)
                    <a href="{{ route('reports.show', $latest) }}" class="mt-4 inline-flex items-center gap-1 text-sm aurex-link">
                        View latest report <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="mt-6 grid gap-6 md:grid-cols-3">
        <x-stat-card label="Total analyses" :value="$totalAnalyses" icon="scan" />
        <x-stat-card label="Average score" :value="$averageScore ?: '—'" icon="star" />
        <x-stat-card label="Plan" :value="$user->isPro() ? 'Pro' : 'Free'" icon="shield" :trend="$user->isPro() ? 'Unlimited analyses' : 'Upgrade for unlimited'" />
    </div>

    {{-- Recent analyses --}}
    <div class="mt-10">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold">Recent analyses</h2>
            <a href="{{ route('analysis.history') }}" class="text-sm aurex-link">See all</a>
        </div>

        @if ($recent->isEmpty())
            <div class="aurex-card mt-6 text-center">
                <p class="text-sm" style="color: var(--color-cream-dim);">You haven't analyzed any selfies yet.</p>
                <a href="{{ route('analysis.create') }}" class="aurex-btn aurex-btn-primary mt-6 inline-flex">
                    <x-icon name="upload" class="h-4 w-4" />
                    Upload your first selfie
                </a>
            </div>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($recent as $analysis)
                    <a href="{{ route('analysis.show', $analysis) }}" class="aurex-card aurex-hover-lift block">
                        <div class="flex items-center justify-between">
                            <span class="aurex-badge">{{ ucfirst($analysis->status) }}</span>
                            <span class="text-xs" style="color: var(--color-muted);">{{ $analysis->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="mt-6 flex items-end justify-between">
                            <div>
                                <p class="text-[11px] uppercase tracking-widest" style="color: var(--color-muted);">Style score</p>
                                <p class="text-3xl font-semibold">{{ $analysis->style_score ?? '—' }}</p>
                            </div>
                            <div class="text-right text-xs" style="color: var(--color-cream-dim);">
                                <p>{{ ucfirst($analysis->face_shape ?? '—') }}</p>
                                <p>{{ ucfirst($analysis->skin_undertone ?? '—') }}</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Recommended styles preview --}}
    @if ($latest)
        <div class="mt-10">
            <h2 class="text-xl font-semibold">Recommended styles</h2>
            <p class="mt-1 text-sm" style="color: var(--color-cream-dim);">Pulled from your latest analysis.</p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ((array) ($latest->hairstyles ?? []) as $style)
                    <div class="aurex-card">
                        <p class="aurex-label">Hairstyle</p>
                        <p class="mt-2 text-lg font-medium capitalize">{{ $style }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
