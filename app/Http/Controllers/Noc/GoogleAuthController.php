<?php

namespace App\Http\Controllers\Noc;

use App\Http\Controllers\Controller;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleAuthController extends Controller
{
    /**
     * Redirect user to Google consent screen
     */
    public function redirect()
    {
        $client = $this->getClient();
        $client->addScope([
            Gmail::GMAIL_READONLY,
            Gmail::GMAIL_MODIFY,
        ]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return redirect()->away($client->createAuthUrl());
    }

    /**
     * Handle callback and store tokens on the User model
     */
    public function callback(Request $request)
    {
        $client = $this->getClient();
        $token  = $client->fetchAccessTokenWithAuthCode($request->input('code'));

        if (isset($token['error'])) {
            return redirect()->route('settings.mail')
                             ->withErrors('Google OAuth error: ' . ($token['error_description'] ?? $token['error']));
        }

        $user = Auth::user();
        $user->update([
            'google_token'          => $token,
            'google_refresh_token'  => $token['refresh_token'] ?? $user->google_refresh_token,
        ]);

        return redirect()->route('settings.mail')
                         ->with('success', 'Google account berhasil dihubungkan.');
    }

    /**
     * Build a new GoogleClient with redirectUri
     */
    private function getClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));

        return $client;
    }
}
