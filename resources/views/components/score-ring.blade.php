@props(['score' => 0, 'size' => 160, 'label' => null])

@php
    $s = max(0, min(100, (int) $score));
    $r = 54;
    $c = 2 * M_PI * $r;
    $offset = $c - ($s / 100) * $c;
@endphp

<div class="relative inline-flex items-center justify-center" style="width: {{ $size }}px; height: {{ $size }}px;">
    <svg viewBox="0 0 128 128" class="h-full w-full -rotate-90">
        <defs>
            <linearGradient id="score-grad-{{ $s }}" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#B7410E"/>
                <stop offset="100%" stop-color="#6B8340"/>
            </linearGradient>
        </defs>
        <circle cx="64" cy="64" r="{{ $r }}" stroke="#2E2E2E" stroke-width="8" fill="none" />
        <circle cx="64" cy="64" r="{{ $r }}"
                stroke="url(#score-grad-{{ $s }})"
                stroke-width="8"
                stroke-linecap="round"
                fill="none"
                stroke-dasharray="{{ $c }}"
                stroke-dashoffset="{{ $offset }}" />
    </svg>
    <div class="absolute inset-0 flex flex-col items-center justify-center">
        <span class="text-3xl font-semibold">{{ $s }}</span>
        <span class="text-[10px] uppercase tracking-widest" style="color: var(--color-muted);">{{ $label ?? 'Style Score' }}</span>
    </div>
</div>
