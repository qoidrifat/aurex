@php
    $current = request()->route()?->getName() ?? '';
@endphp

<aside class="hidden w-64 shrink-0 border-r md:flex md:flex-col" style="border-color: var(--color-border); background-color: var(--color-surface);">
    <div class="flex items-center gap-2 px-5 py-5 text-lg font-semibold">
        <x-brand-mark class="h-7 w-7" />
        <span>AUREX</span>
    </div>

    <nav class="flex flex-1 flex-col gap-1 px-3">
        <x-nav-link href="{{ route('dashboard') }}" :active="$current === 'dashboard'" icon="home">Dashboard</x-nav-link>
        <x-nav-link href="{{ route('analysis.create') }}" :active="str_starts_with($current, 'analysis.') && $current !== 'analysis.history'" icon="scan">Analyze</x-nav-link>
        <x-nav-link href="{{ route('analysis.history') }}" :active="$current === 'analysis.history'" icon="history">History</x-nav-link>
        <x-nav-link href="{{ route('reports.index') }}" :active="str_starts_with($current, 'reports.')" icon="file">Style Reports</x-nav-link>
        <x-nav-link href="{{ route('profile.edit') }}" :active="$current === 'profile.edit'" icon="user">Profile</x-nav-link>
        <x-nav-link href="{{ route('profile.settings') }}" :active="$current === 'profile.settings'" icon="settings">Settings</x-nav-link>

        @auth
            @if (auth()->user()->isAdmin())
                <div class="mt-6 px-3 text-[11px] uppercase tracking-widest" style="color: var(--color-muted);">Admin</div>
                <x-nav-link href="{{ route('admin.dashboard') }}" :active="$current === 'admin.dashboard'" icon="chart">Overview</x-nav-link>
                <x-nav-link href="{{ route('admin.users') }}" :active="$current === 'admin.users'" icon="user">Users</x-nav-link>
                <x-nav-link href="{{ route('admin.analyses') }}" :active="$current === 'admin.analyses'" icon="grid">Analyses</x-nav-link>
                <x-nav-link href="{{ route('admin.images') }}" :active="$current === 'admin.images'" icon="image">Images</x-nav-link>
            @endif
        @endauth
    </nav>

    <div class="m-3 mt-auto rounded-2xl p-4" style="background: linear-gradient(135deg, rgba(183,65,14,0.12), rgba(85,107,47,0.12)); border: 1px solid rgba(183,65,14,0.18);">
        @auth
            @if (! auth()->user()->isPro())
                <p class="text-sm font-medium">Upgrade to AUREX Pro</p>
                <p class="mt-1 text-xs" style="color: var(--color-cream-dim);">Unlimited analyses, PDF reports, and priority AI runs.</p>
                <a href="{{ route('profile.settings') }}" class="mt-3 inline-flex text-xs font-medium" style="color: var(--color-rust-soft);">Learn more →</a>
            @else
                <p class="text-sm font-medium">You're on AUREX Pro</p>
                <p class="mt-1 text-xs" style="color: var(--color-cream-dim);">Unlimited analyses are unlocked.</p>
            @endif
        @endauth
    </div>
</aside>
