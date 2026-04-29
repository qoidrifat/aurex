@extends('layouts.auth')

@section('auth-form')
    <h1 class="text-3xl font-semibold tracking-tight">Create your account</h1>
    <p class="mt-2 text-sm" style="color: var(--color-muted);">
        Sign up and get your first AUREX style report in under a minute.
    </p>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-4">
        @csrf

        <div>
            <label for="name" class="aurex-label">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="aurex-input mt-2" placeholder="Your name">
            @error('name') <p class="mt-1 text-xs" style="color: var(--color-rust-soft);">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="aurex-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="aurex-input mt-2" placeholder="you@example.com">
            @error('email') <p class="mt-1 text-xs" style="color: var(--color-rust-soft);">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="aurex-label">Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" class="aurex-input mt-2" placeholder="At least 8 characters">
            @error('password') <p class="mt-1 text-xs" style="color: var(--color-rust-soft);">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="aurex-label">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="aurex-input mt-2" placeholder="Retype your password">
        </div>

        <button type="submit" class="aurex-btn aurex-btn-primary w-full">
            Create account
        </button>
    </form>

    <p class="mt-8 text-center text-sm" style="color: var(--color-muted);">
        Already have an account?
        <a href="{{ route('login') }}" class="aurex-link font-medium">Log in</a>
    </p>
@endsection
