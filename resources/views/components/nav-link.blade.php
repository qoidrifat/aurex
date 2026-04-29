@props(['href' => '#', 'active' => false, 'icon' => null])

@php
    $classes = $active
        ? 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium'
        : 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors duration-150 hover:bg-white/5';
    $style = $active
        ? 'background-color: rgba(183,65,14,0.15); color: var(--color-cream); border: 1px solid rgba(183,65,14,0.25);'
        : 'color: var(--color-cream-dim);';
@endphp

<a href="{{ $href }}" class="{{ $classes }}" style="{{ $style }}">
    @if ($icon)
        <x-icon :name="$icon" class="h-4 w-4" />
    @endif
    <span>{{ $slot }}</span>
</a>
