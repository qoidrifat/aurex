@extends('layouts.app')

@section('content')
    <div class="flex min-h-screen">
        <div class="hidden w-1/2 items-center justify-center lg:flex" style="background: radial-gradient(circle at 30% 20%, rgba(183,65,14,0.18), transparent 50%), radial-gradient(circle at 70% 80%, rgba(85,107,47,0.18), transparent 55%), var(--color-ink);">
            <div class="max-w-md px-10">
                <a href="{{ route('landing') }}" class="flex items-center gap-2 text-2xl font-semibold tracking-tight">
                    <x-brand-mark class="h-8 w-8" />
                    AUREX
                </a>
                <h1 class="mt-10 text-4xl font-semibold leading-tight">Upgrade your look with AI.</h1>
                <p class="mt-4 text-sm" style="color: var(--color-muted);">
                    Join AUREX and unlock a personal stylist trained on thousands of face shapes, skin undertones, and outfit combinations.
                </p>
                <div class="mt-10 flex items-center gap-3">
                    <span class="aurex-badge">AI face analysis</span>
                    <span class="aurex-badge">Hairstyle matches</span>
                    <span class="aurex-badge">Color palette</span>
                </div>
            </div>
        </div>

        <div class="flex w-full items-center justify-center px-6 py-12 lg:w-1/2">
            <div class="w-full max-w-md">
                <a href="{{ route('landing') }}" class="mb-10 inline-flex items-center gap-2 text-xl font-semibold lg:hidden">
                    <x-brand-mark class="h-7 w-7" />
                    AUREX
                </a>

                @yield('auth-form')
            </div>
        </div>
    </div>
@endsection
