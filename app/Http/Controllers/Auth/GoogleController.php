<?php

namespace App\Http\Controllers\Noc;

use App\Http\Controllers\Controller;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class GoogleAuthController extends Controller
{
    /**
     * Redirect user ke Google consent screen
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
     * Callback dari Google, tukar code jadi token
     */
    public function callback(Request $request)
    {
        $client = $this->getClient();
        $token  = $client->fetchAccessTokenWithAuthCode($request->input('code'));

        if (isset($token['error'])) {
            return redirect()->route('settings.mail.edit')
                             ->withErrors('Google OAuth error: ' . ($token['error_description'] ?? $token['error']));
        }

        Session::put('google_token',         $token);
        Session::put('google_refresh_token', $token['refresh_token']);

        return redirect()->route('settings.mail.edit')
                         ->with('success', 'Google account berhasil dihubungkan.');
    }

    /**
     * Siapkan Google\Client dengan redirectUri
     */
    private function getClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        return $client;
    }
}
