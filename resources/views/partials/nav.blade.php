<header class="sticky top-0 z-30 border-b" style="backdrop-filter: blur(14px); background-color: rgba(15,15,15,0.65); border-color: var(--color-border);">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
        <a href="{{ route('landing') }}" class="flex items-center gap-2 text-lg font-semibold tracking-tight">
            <x-brand-mark class="h-7 w-7" />
            AUREX
        </a>

        <nav class="hidden items-center gap-8 text-sm md:flex" style="color: var(--color-cream-dim);">
            <a href="#how-it-works" class="transition-colors hover:text-white">How it works</a>
            <a href="#features" class="transition-colors hover:text-white">Features</a>
            <a href="#testimonials" class="transition-colors hover:text-white">Reviews</a>
        </nav>

        <div class="flex items-center gap-3">
            @auth
                <a href="{{ route('dashboard') }}" class="aurex-btn aurex-btn-secondary">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="hidden text-sm md:inline" style="color: var(--color-cream-dim);">Log in</a>
                <a href="{{ route('register') }}" class="aurex-btn aurex-btn-primary">
                    Try AUREX
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            @endauth
        </div>
    </div>
</header>
