<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $data['avatar_path'] = $request->file('avatar')->store('avatars/'.$user->id, 'public');
        }

        unset($data['avatar']);

        $user->fill($data)->save();

        return redirect()->route('profile.edit')->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($user->password && ! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('Current password is incorrect.'),
            ]);
        }

        $user->password = $data['password'];
        $user->save();

        Auth::logoutOtherDevices($data['password']);

        return redirect()->route('profile.edit')->with('status', 'Password updated.');
    }

    public function settings(Request $request): View
    {
        return view('profile.settings', [
            'user' => $request->user(),
        ]);
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'notify_analysis_complete' => ['nullable', 'boolean'],
            'notify_product_updates' => ['nullable', 'boolean'],
        ]);

        $user->preferences = array_merge((array) $user->preferences, [
            'notify_analysis_complete' => (bool) ($data['notify_analysis_complete'] ?? false),
            'notify_product_updates' => (bool) ($data['notify_product_updates'] ?? false),
        ]);
        $user->save();

        return redirect()->route('profile.settings')->with('status', 'Preferences saved.');
    }
}
