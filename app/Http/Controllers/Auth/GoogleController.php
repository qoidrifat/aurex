<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class GoogleController extends Controller
{
    /**
     * Placeholder for the Google OAuth flow. Wire Laravel Socialite here when a
     * Google client ID is configured. For now we surface a friendly message so
     * the button on the login page doesn't silently fail.
     */
    public function redirect(): RedirectResponse
    {
        if (! config('services.google.client_id')) {
            return redirect()
                ->route('login')
                ->with('status', 'Google sign-in is coming soon. Configure GOOGLE_CLIENT_ID in .env to enable it.');
        }

        return redirect()
            ->route('login')
            ->with('status', 'Socialite redirect is not yet wired in this scaffold.');
    }

    public function callback(): RedirectResponse
    {
        return redirect()->route('login');
    }
}
