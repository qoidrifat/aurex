@extends('layouts.dashboard')

@section('dashboard')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <span class="aurex-badge"><x-icon name="sparkle" class="h-3.5 w-3.5" /> Step 3 of 3 · Your AUREX report</span>
            <h1 class="mt-4 text-3xl font-semibold tracking-tight">Analysis #{{ $analysis->id }}</h1>
            <p class="mt-1 text-sm" style="color: var(--color-muted);">{{ $analysis->created_at->toFormattedDateString() }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('reports.save', $analysis) }}">
                @csrf
                <button class="aurex-btn aurex-btn-primary" type="submit">
                    <x-icon name="check" class="h-4 w-4" />
                    Save Style Report
                </button>
            </form>
            <button type="button" class="aurex-btn aurex-btn-secondary"
                    x-data
                    @click="navigator.share ? navigator.share({ title: 'My AUREX report', url: window.location.href }) : navigator.clipboard.writeText(window.location.href)">
                Share Result
            </button>
        </div>
    </div>

    {{-- Top summary --}}
    <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_1.4fr]">
        <div class="aurex-card flex flex-col items-center gap-4 text-center">
            <x-score-ring :score="$analysis->style_score ?? 0" :size="200" />
            <p class="text-lg font-semibold">{{ $analysis->scoreLabel() }}</p>
            <p class="max-w-xs text-sm" style="color: var(--color-cream-dim);">
                Your AUREX score is based on proportion harmony, skin-to-color contrast, and outfit fit feasibility.
            </p>
        </div>

        <div class="aurex-card">
            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <p class="aurex-label">Face shape</p>
                    <p class="mt-2 text-2xl font-semibold capitalize">{{ $analysis->face_shape ?? '—' }}</p>
                    <p class="mt-2 text-sm" style="color: var(--color-cream-dim);">
                        Balanced proportions. Most hairstyle shapes will complement you — aim for definition on the top.
                    </p>
                </div>
                <div>
                    <p class="aurex-label">Skin undertone</p>
                    <p class="mt-2 text-2xl font-semibold capitalize">{{ $analysis->skin_undertone ?? '—' }}</p>
                    <p class="mt-2 text-sm" style="color: var(--color-cream-dim);">
                        {{ match ($analysis->skin_undertone) {
                            'warm' => 'Earth tones, olive, rust, and camel will flatter your complexion.',
                            'cool' => 'Navy, slate, icy neutrals, and cool grays work best on you.',
                            default => 'Most palettes work — focus on medium-contrast combinations.',
                        } }}
                    </p>
                </div>
            </div>

            @if ($analysis->uploadedImage)
                <div class="mt-6 flex items-center gap-4 border-t pt-6" style="border-color: var(--color-border);">
                    <img src="{{ $analysis->uploadedImage->url() }}" class="h-16 w-16 rounded-lg object-cover" alt="Selfie">
                    <div>
                        <p class="text-sm font-medium">Source selfie</p>
                        <p class="text-xs" style="color: var(--color-muted);">{{ $analysis->uploadedImage->original_name ?? 'uploaded image' }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Hairstyles --}}
    <section class="mt-10">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold">Best Hairstyles</h2>
            <span class="text-xs" style="color: var(--color-muted);">Curated for your face shape</span>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($hairstyles as $h)
                <div class="aurex-card aurex-hover-lift">
                    <div class="flex items-center justify-between">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full" style="background-color: rgba(85,107,47,0.15); color: var(--color-olive-soft);">
                            <x-icon name="scissors" class="h-5 w-5" />
                        </div>
                        <span class="text-xs" style="color: var(--color-muted);">#{{ $loop->iteration }}</span>
                    </div>
                    <p class="mt-6 text-lg font-semibold capitalize">{{ $h->label }}</p>
                    <p class="mt-2 text-sm" style="color: var(--color-cream-dim);">
                        Ask your barber for structure on top with a clean taper on the sides.
                    </p>
                </div>
            @empty
                <p class="text-sm" style="color: var(--color-muted);">No hairstyle recommendations yet.</p>
            @endforelse
        </div>
    </section>

    {{-- Color palette --}}
    <section class="mt-10">
        <h2 class="text-xl font-semibold">Color Palette</h2>
        <div class="aurex-card mt-4">
            <div class="flex flex-wrap items-center gap-8">
                @forelse ($colors as $color)
                    <x-color-swatch :hex="$color->hex_color" :label="$color->label" />
                @empty
                    <p class="text-sm" style="color: var(--color-muted);">No color recommendations yet.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Outfits --}}
    <section class="mt-10">
        <h2 class="text-xl font-semibold">Outfit Suggestions</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($outfits as $o)
                <div class="aurex-card aurex-hover-lift">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full" style="background-color: rgba(183,65,14,0.15); color: var(--color-rust-soft);">
                        <x-icon name="shirt" class="h-5 w-5" />
                    </div>
                    <p class="mt-6 text-lg font-semibold">{{ $o->label }}</p>
                    <p class="mt-2 text-sm" style="color: var(--color-cream-dim);">
                        Swap in your preferred fit — slim, regular, or relaxed — and keep the color ratios.
                    </p>
                </div>
            @empty
                <p class="text-sm" style="color: var(--color-muted);">No outfit recommendations yet.</p>
            @endforelse
        </div>
    </section>

    <div class="mt-10 flex flex-wrap gap-3">
        <a href="{{ route('reports.show', $analysis) }}" class="aurex-btn aurex-btn-primary">
            View full style report
            <x-icon name="arrow-right" class="h-4 w-4" />
        </a>
        <a href="{{ route('reports.download', $analysis) }}" target="_blank" class="aurex-btn aurex-btn-secondary">
            <x-icon name="download" class="h-4 w-4" />
            Export (HTML/PDF)
        </a>
        <a href="{{ route('analysis.create') }}" class="aurex-btn aurex-btn-secondary">
            Run another analysis
        </a>
    </div>
@endsection
