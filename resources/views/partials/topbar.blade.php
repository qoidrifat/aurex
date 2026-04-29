<header class="sticky top-0 z-20 flex items-center justify-between border-b px-6 py-4 md:px-10" style="background-color: rgba(15,15,15,0.7); backdrop-filter: blur(12px); border-color: var(--color-border);">
    <div class="flex items-center gap-3">
        <button type="button" class="md:hidden" aria-label="Open navigation" onclick="document.documentElement.classList.toggle('aurex-menu-open')">
            <x-icon name="menu" class="h-5 w-5" />
        </button>
        <div>
            <p class="text-xs uppercase tracking-widest" style="color: var(--color-muted);">AUREX</p>
            <p class="text-sm font-medium">{{ $pageTitle ?? ucfirst(str_replace(['_', '.'], ' ', request()->route()?->getName() ?? 'dashboard')) }}</p>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('analysis.create') }}" class="aurex-btn aurex-btn-primary hidden md:inline-flex">
            <x-icon name="sparkle" class="h-4 w-4" />
            Analyze Your Style
        </a>

        @auth
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="flex h-10 w-10 items-center justify-center rounded-full border text-sm font-semibold" style="border-color: var(--color-border); background-color: var(--color-surface-elevated);">
                    {{ auth()->user()->initials() }}
                </button>
                <div x-show="open" x-transition @click.outside="open = false" x-cloak class="absolute right-0 z-30 mt-2 w-52 overflow-hidden rounded-xl border shadow-xl" style="background-color: var(--color-surface); border-color: var(--color-border);">
                    <div class="px-4 py-3 text-xs" style="color: var(--color-muted);">
                        Signed in as
                        <div class="mt-0.5 text-sm" style="color: var(--color-cream);">{{ auth()->user()->email }}</div>
                    </div>
                    <div class="border-t" style="border-color: var(--color-border);"></div>
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-white/5">Profile</a>
                    <a href="{{ route('profile.settings') }}" class="block px-4 py-2 text-sm hover:bg-white/5">Settings</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm hover:bg-white/5" style="color: var(--color-rust-soft);">
                            <x-icon name="logout" class="h-4 w-4" />
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</header>
