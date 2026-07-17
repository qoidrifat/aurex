@extends('layouts.dashboard')

@section('dashboard')
    <h1 class="text-3xl font-semibold tracking-tight">Your profile</h1>
    <p class="mt-1 text-sm" style="color: var(--color-muted);">Update your name, email, and profile photo.</p>

    <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_2fr]">
        {{-- Avatar summary --}}
        <div class="aurex-card flex flex-col items-center text-center">
            @if ($user->avatar_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path) }}" class="h-24 w-24 rounded-full object-cover" alt="Avatar">
            @else
                <div class="flex h-24 w-24 items-center justify-center rounded-full border text-2xl font-semibold" style="border-color: var(--color-border); background-color: var(--color-surface-elevated);">
                    {{ $user->initials() }}
                </div>
            @endif
            <p class="mt-4 text-lg font-semibold">{{ $user->name }}</p>
            <p class="text-xs" style="color: var(--color-muted);">{{ $user->email }}</p>
            <span class="aurex-badge mt-4">{{ $user->isPro() ? 'AUREX Pro' : 'Free plan' }}</span>
        </div>

        <div class="space-y-6">
            {{-- Profile form --}}
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="aurex-card space-y-4">
                @csrf
                @method('PUT')
                <h2 class="text-lg font-semibold">Account details</h2>

                <div>
                    <label for="name" class="aurex-label">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" class="aurex-input mt-2" required>
                    @error('name') <p class="mt-1 text-xs" style="color: var(--color-rust-soft);">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="aurex-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="aurex-input mt-2" required>
                    @error('email') <p class="mt-1 text-xs" style="color: var(--color-rust-soft);">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="avatar" class="aurex-label">Profile photo</label>
                    <input id="avatar" name="avatar" type="file" accept="image/*" class="aurex-input mt-2 p-2 text-xs">
                    @error('avatar') <p class="mt-1 text-xs" style="color: var(--color-rust-soft);">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="aurex-btn aurex-btn-primary">Save changes</button>
                </div>
            </form>

            {{-- Password form --}}
            <form method="POST" action="{{ route('profile.password') }}" class="aurex-card space-y-4">
                @csrf
                @method('PUT')
                <h2 class="text-lg font-semibold">Password</h2>

                <div>
                    <label for="current_password" class="aurex-label">Current password</label>
                    <input id="current_password" name="current_password" type="password" class="aurex-input mt-2">
                    @error('current_password') <p class="mt-1 text-xs" style="color: var(--color-rust-soft);">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="password" class="aurex-label">New password</label>
                        <input id="password" name="password" type="password" class="aurex-input mt-2">
                        @error('password') <p class="mt-1 text-xs" style="color: var(--color-rust-soft);">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="aurex-label">Confirm new</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="aurex-input mt-2">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="aurex-btn aurex-btn-primary">Update password</button>
                </div>
            </form>
        </div>
    </div>
@endsection
