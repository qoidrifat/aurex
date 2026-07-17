<footer class="border-t" style="border-color: var(--color-border);">
    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-6 py-14 md:grid-cols-4">
        <div>
            <a href="{{ route('landing') }}" class="flex items-center gap-2 text-lg font-semibold">
                <x-brand-mark class="h-7 w-7" />
                AUREX
            </a>
            <p class="mt-4 text-sm" style="color: var(--color-muted);">
                AI-powered personal style intelligence for modern men.
            </p>
        </div>

        <div>
            <p class="aurex-label">Product</p>
            <ul class="mt-4 space-y-2 text-sm" style="color: var(--color-cream-dim);">
                <li><a href="#features" class="hover:text-white">Features</a></li>
                <li><a href="#how-it-works" class="hover:text-white">How it works</a></li>
                <li><a href="{{ route('register') }}" class="hover:text-white">Get started</a></li>
            </ul>
        </div>

        <div>
            <p class="aurex-label">Company</p>
            <ul class="mt-4 space-y-2 text-sm" style="color: var(--color-cream-dim);">
                <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                <li><a href="#" class="hover:text-white">Terms</a></li>
                <li><a href="mailto:hello@aurex.app" class="hover:text-white">Contact</a></li>
            </ul>
        </div>

        <div>
            <p class="aurex-label">Social</p>
            <ul class="mt-4 space-y-2 text-sm" style="color: var(--color-cream-dim);">
                <li><a href="#" class="hover:text-white">Instagram</a></li>
                <li><a href="#" class="hover:text-white">TikTok</a></li>
                <li><a href="#" class="hover:text-white">X / Twitter</a></li>
            </ul>
        </div>
    </div>

    <div class="border-t px-6 py-6 text-center text-xs" style="border-color: var(--color-border); color: var(--color-muted);">
        © {{ date('Y') }} AUREX. Built for the next generation of style-forward men.
    </div>
</footer>
