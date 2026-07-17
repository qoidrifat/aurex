@extends('layouts.auth')

@section('auth-form')
    <h1 class="text-3xl font-semibold tracking-tight">Welcome back</h1>
    <p class="mt-2 text-sm" style="color: var(--color-muted);">
        Sign in to see your style reports and start a new analysis.
    </p>

    @if (session('status'))
        <div class="mt-6 rounded-xl border px-4 py-3 text-sm" style="border-color: var(--color-border); color: var(--color-cream-dim);">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4">
        @csrf

        <div>
            <label for="email" class="aurex-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="aurex-input mt-2" placeholder="you@example.com">
            @error('email') <p class="mt-1 text-xs" style="color: var(--color-rust-soft);">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="aurex-label">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="aurex-input mt-2" placeholder="••••••••">
            @error('password') <p class="mt-1 text-xs" style="color: var(--color-rust-soft);">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between text-xs" style="color: var(--color-muted);">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded border" style="border-color: var(--color-border); background-color: var(--color-surface-elevated);">
                Remember me
            </label>
            <a href="#" class="aurex-link">Forgot password?</a>
        </div>

        <button type="submit" class="aurex-btn aurex-btn-primary w-full">
            Log in
        </button>
    </form>

    <div class="my-6 flex items-center gap-3 text-[10px] uppercase tracking-widest" style="color: var(--color-muted);">
        <span class="h-px flex-1" style="background-color: var(--color-border);"></span>
        Or
        <span class="h-px flex-1" style="background-color: var(--color-border);"></span>
    </div>

    <a href="{{ route('auth.google') }}" class="aurex-btn aurex-btn-secondary w-full">
        <svg class="h-4 w-4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path fill="#EA4335" d="M12 10.2v3.9h5.5c-.24 1.4-1.7 4.1-5.5 4.1-3.3 0-6-2.7-6-6.2s2.7-6.2 6-6.2c1.9 0 3.1.8 3.8 1.5l2.6-2.5C16.8 3.5 14.6 2.5 12 2.5 6.9 2.5 2.8 6.6 2.8 12s4.1 9.5 9.2 9.5c5.3 0 8.8-3.7 8.8-8.9 0-.6-.06-1-.14-1.4H12z"/>
        </svg>
        Continue with Google
    </a>

    <p class="mt-8 text-center text-sm" style="color: var(--color-muted);">
        Don't have an account?
        <a href="{{ route('register') }}" class="aurex-link font-medium">Create one</a>
    </p>
@endsection
