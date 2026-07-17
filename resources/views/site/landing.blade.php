@extends('layouts.marketing')

@section('marketing')
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0" style="background:
            radial-gradient(circle at 20% 10%, rgba(183,65,14,0.2), transparent 50%),
            radial-gradient(circle at 80% 20%, rgba(85,107,47,0.2), transparent 55%);"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-6 py-20 md:grid-cols-2 md:py-28">
            <div>
                <span class="aurex-badge">
                    <x-icon name="sparkle" class="h-3.5 w-3.5" />
                    AI Style Intelligence
                </span>
                <h1 class="mt-6 text-5xl font-semibold leading-[1.05] tracking-tight md:text-6xl">
                    Upgrade Your Look<br>
                    <span style="background: linear-gradient(90deg, #B7410E 0%, #6B8340 100%); -webkit-background-clip: text; background-clip: text; color: transparent;">With AI</span>
                </h1>
                <p class="mt-6 max-w-xl text-base md:text-lg" style="color: var(--color-cream-dim);">
                    AI-powered personal style intelligence for modern men. Upload a selfie and get hairstyle, color, and outfit recommendations tailored to your face.
                </p>

                <div class="mt-10 flex flex-wrap items-center gap-4">
                    <a href="{{ auth()->check() ? route('analysis.create') : route('register') }}" class="aurex-btn aurex-btn-primary aurex-pulse">
                        <x-icon name="sparkle" class="h-4 w-4" />
                        Try AUREX
                    </a>
                    <a href="{{ auth()->check() ? route('analysis.create') : route('register') }}" class="aurex-btn aurex-btn-secondary">
                        <x-icon name="upload" class="h-4 w-4" />
                        Upload Your Selfie
                    </a>
                </div>

                <div class="mt-10 flex items-center gap-8 text-sm" style="color: var(--color-muted);">
                    <div class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4" style="color: var(--color-olive-soft);" /> Free to try</div>
                    <div class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4" style="color: var(--color-olive-soft);" /> No credit card</div>
                    <div class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4" style="color: var(--color-olive-soft);" /> Results in seconds</div>
                </div>
            </div>

            {{-- Hero visual: AI scanning face --}}
            <div class="relative">
                <div class="relative mx-auto aspect-[4/5] max-w-md overflow-hidden rounded-3xl border" style="border-color: var(--color-border); background: radial-gradient(circle at 50% 30%, rgba(183,65,14,0.15), var(--color-surface));">
                    <div class="absolute inset-6 overflow-hidden rounded-2xl border" style="border-color: rgba(183,65,14,0.3);">
                        <svg viewBox="0 0 200 240" class="h-full w-full" preserveAspectRatio="xMidYMid slice">
                            <defs>
                                <linearGradient id="face-grad" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#2A2A2A"/>
                                    <stop offset="100%" stop-color="#1C1C1C"/>
                                </linearGradient>
                            </defs>
                            <rect width="200" height="240" fill="url(#face-grad)"/>
                            <ellipse cx="100" cy="110" rx="52" ry="66" stroke="#6B8340" stroke-width="1.2" fill="none" opacity="0.85"/>
                            <circle cx="82" cy="100" r="3" fill="#F5F5F5"/>
                            <circle cx="118" cy="100" r="3" fill="#F5F5F5"/>
                            <path d="M86 140 Q100 152 114 140" stroke="#F5F5F5" stroke-width="1.4" fill="none" stroke-linecap="round"/>
                            <path d="M96 110 L96 128 L104 128" stroke="#B7410E" stroke-width="1.2" fill="none" stroke-linecap="round"/>
                            <g opacity="0.55" stroke="#6B8340" stroke-width="0.6">
                                <line x1="40" y1="60" x2="70" y2="80"/>
                                <line x1="160" y1="60" x2="130" y2="80"/>
                                <line x1="40" y1="180" x2="70" y2="160"/>
                                <line x1="160" y1="180" x2="130" y2="160"/>
                            </g>
                        </svg>
                        <div class="aurex-scan-line"></div>
                    </div>

                    {{-- AI overlay badges --}}
                    <div class="absolute left-4 top-4 aurex-badge">
                        <span class="h-1.5 w-1.5 rounded-full" style="background-color: var(--color-rust);"></span>
                        Scanning
                    </div>
                    <div class="absolute bottom-4 left-4 right-4 space-y-2">
                        <div class="rounded-xl border px-3 py-2 text-xs backdrop-blur" style="background-color: rgba(28,28,28,0.75); border-color: var(--color-border);">
                            <div class="flex items-center justify-between">
                                <span style="color: var(--color-muted);">Face shape</span>
                                <span class="font-medium">Oval</span>
                            </div>
                        </div>
                        <div class="rounded-xl border px-3 py-2 text-xs backdrop-blur" style="background-color: rgba(28,28,28,0.75); border-color: var(--color-border);">
                            <div class="flex items-center justify-between">
                                <span style="color: var(--color-muted);">Undertone</span>
                                <span class="font-medium">Warm</span>
                            </div>
                        </div>
                        <div class="rounded-xl border px-3 py-2 text-xs backdrop-blur" style="background-color: rgba(28,28,28,0.75); border-color: var(--color-border);">
                            <div class="flex items-center justify-between">
                                <span style="color: var(--color-muted);">Style score</span>
                                <span class="font-medium" style="color: var(--color-olive-soft);">82 / 100</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section id="how-it-works" class="mx-auto max-w-7xl px-6 py-20">
        <div class="max-w-xl">
            <p class="aurex-label">How it works</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight md:text-4xl">Three steps to a sharper you</h2>
        </div>

        <div class="mt-12 grid gap-6 md:grid-cols-3">
            @foreach ([
                ['1', 'Upload Selfie', 'Drag, drop, or snap a photo. We scan the image in your browser and send it securely to our AI.', 'upload'],
                ['2', 'AI Face Analysis', 'Our vision model maps your facial structure, proportions, and skin undertone in seconds.', 'scan'],
                ['3', 'Get Style Recommendations', 'Receive personalized hairstyles, a color palette, and outfit ideas built for you.', 'sparkle'],
            ] as $step)
                <div class="aurex-card aurex-hover-lift">
                    <div class="flex items-center justify-between">
                        <span class="text-5xl font-semibold" style="color: rgba(183,65,14,0.25);">{{ $step[0] }}</span>
                        <div class="flex h-10 w-10 items-center justify-center rounded-full" style="background-color: rgba(85,107,47,0.15); color: var(--color-olive-soft);">
                            <x-icon :name="$step[3]" class="h-5 w-5" />
                        </div>
                    </div>
                    <h3 class="mt-6 text-xl font-semibold">{{ $step[1] }}</h3>
                    <p class="mt-2 text-sm" style="color: var(--color-cream-dim);">{{ $step[2] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="mx-auto max-w-7xl px-6 py-20">
        <div class="max-w-xl">
            <p class="aurex-label">Features</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight md:text-4xl">Built for the next generation of style</h2>
            <p class="mt-4 text-sm" style="color: var(--color-cream-dim);">
                Every recommendation is grounded in your unique proportions, tones, and lifestyle — not a trend cycle.
            </p>
        </div>

        <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            @foreach ($features as $feature)
                <div class="aurex-card aurex-hover-lift flex flex-col">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full" style="background-color: rgba(183,65,14,0.15); color: var(--color-rust-soft);">
                        <x-icon :name="$feature['icon']" class="h-5 w-5" />
                    </div>
                    <h3 class="mt-6 text-lg font-semibold">{{ $feature['title'] }}</h3>
                    <p class="mt-2 text-sm" style="color: var(--color-cream-dim);">{{ $feature['body'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Testimonials --}}
    <section id="testimonials" class="mx-auto max-w-7xl px-6 py-20">
        <div class="max-w-xl">
            <p class="aurex-label">Testimonials</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight md:text-4xl">Style, upgraded.</h2>
        </div>

        <div class="mt-12 grid gap-6 md:grid-cols-3">
            @foreach ($testimonials as $t)
                <figure class="aurex-card">
                    <div class="flex items-center gap-1" style="color: var(--color-rust-soft);">
                        @for ($i = 0; $i < 5; $i++)
                            <x-icon name="star" class="h-4 w-4" />
                        @endfor
                    </div>
                    <blockquote class="mt-4 text-sm leading-relaxed" style="color: var(--color-cream-dim);">
                        "{{ $t['quote'] }}"
                    </blockquote>
                    <figcaption class="mt-6 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-semibold" style="background-color: var(--color-surface-elevated);">
                            {{ strtoupper(substr($t['name'], 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium">{{ $t['name'] }}</p>
                            <p class="text-xs" style="color: var(--color-muted);">{{ $t['role'] }}</p>
                        </div>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-7xl px-6 pb-24">
        <div class="overflow-hidden rounded-3xl border p-10 md:p-16" style="border-color: var(--color-border); background: radial-gradient(circle at 20% 50%, rgba(183,65,14,0.25), transparent 50%), radial-gradient(circle at 80% 50%, rgba(85,107,47,0.2), transparent 55%), var(--color-surface);">
            <div class="grid gap-10 md:grid-cols-[1.2fr_1fr] md:items-end">
                <div>
                    <h2 class="text-3xl font-semibold md:text-4xl">Ready to see your AUREX score?</h2>
                    <p class="mt-4 max-w-xl text-sm" style="color: var(--color-cream-dim);">
                        Get a full AI style report in under a minute. No app, no waitlist — just a selfie.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3 md:justify-end">
                    <a href="{{ auth()->check() ? route('analysis.create') : route('register') }}" class="aurex-btn aurex-btn-primary">
                        Try AUREX
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                    <a href="{{ route('login') }}" class="aurex-btn aurex-btn-secondary">Log in</a>
                </div>
            </div>
        </div>
    </section>
@endsection
