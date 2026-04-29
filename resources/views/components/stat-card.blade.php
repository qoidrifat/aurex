@props(['label' => '', 'value' => '', 'icon' => 'sparkle', 'trend' => null])

<div class="aurex-card aurex-hover-lift flex items-start justify-between">
    <div>
        <p class="aurex-label">{{ $label }}</p>
        <p class="mt-2 text-3xl font-semibold">{{ $value }}</p>
        @if ($trend)
            <p class="mt-2 text-xs" style="color: var(--color-olive-soft);">{{ $trend }}</p>
        @endif
    </div>
    <div class="flex h-10 w-10 items-center justify-center rounded-full" style="background-color: rgba(183,65,14,0.12); color: var(--color-rust-soft);">
        <x-icon :name="$icon" class="h-5 w-5" />
    </div>
</div>
