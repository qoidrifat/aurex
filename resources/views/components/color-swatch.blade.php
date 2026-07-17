@props(['hex' => null, 'label' => ''])

@php
    $fallback = [
        'olive' => '#556B2F', 'camel' => '#B08A56', 'rust' => '#B7410E',
        'charcoal' => '#2A2A2A', 'cream' => '#F5F5F5', 'navy' => '#1B2A4E',
        'sand' => '#D9C6A7', 'forest' => '#2F4F2F', 'slate' => '#4A5560',
        'oat' => '#E8DCC3', 'terracotta' => '#C56B48', 'ink' => '#111111',
    ];
    $color = $hex ?: ($fallback[strtolower(trim((string) $label))] ?? '#6B8340');
@endphp

<div class="flex flex-col items-center gap-2">
    <div class="relative h-16 w-16 rounded-full border" style="background-color: {{ $color }}; border-color: rgba(255,255,255,0.08); box-shadow: 0 6px 18px rgba(0,0,0,0.35);"></div>
    <span class="text-xs capitalize" style="color: var(--color-cream-dim);">{{ $label }}</span>
    @if ($hex)
        <span class="text-[10px]" style="color: var(--color-muted);">{{ $hex }}</span>
    @endif
</div>
