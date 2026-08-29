<?php

namespace App\Http\Controllers;

use Google\Auth\OAuth2;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GoogleDriveAuthController extends Controller
{
    private const TOKEN_PATH = 'google-drive-oauth-token.json';

    /**
     * Redirects the logged-in owner to Google's consent screen so the app can
     * obtain a refresh token authorised as their real Google account (needed
     * because service accounts have no storage quota of their own on a
     * personal, non-Workspace Google Drive).
     */
    public function authorize(Request $request): RedirectResponse
    {
        $oauth = $this->oauthClient($request);

        $url = $oauth->buildFullAuthorizationUri([
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return redirect((string) $url);
    }

    public function callback(Request $request): string
    {
        $request->validate(['code' => ['required', 'string']]);

        $oauth = $this->oauthClient($request);
        $oauth->setCode($request->string('code'));

        $token = $oauth->fetchAuthToken();

        if (empty($token['refresh_token'])) {
            return 'Google did not return a refresh token. Revoke this app\'s access at '
                .'myaccount.google.com/permissions and try again (consent must be re-granted '
                .'from scratch to get a fresh refresh token).';
        }

        Storage::disk('local')->put(self::TOKEN_PATH, json_encode([
            'client_id' => config('services.google_drive.oauth_client_id'),
            'client_secret' => config('services.google_drive.oauth_client_secret'),
            'refresh_token' => $token['refresh_token'],
        ], JSON_PRETTY_PRINT));

        return 'Google Drive access authorised and saved. You can close this page.';
    }

    private function oauthClient(Request $request): OAuth2
    {
        return new OAuth2([
            'clientId' => config('services.google_drive.oauth_client_id'),
            'clientSecret' => config('services.google_drive.oauth_client_secret'),
            'authorizationUri' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'tokenCredentialUri' => 'https://oauth2.googleapis.com/token',
            'redirectUri' => route('google-drive.callback'),
            'scope' => 'https://www.googleapis.com/auth/drive.file',
        ]);
    }
}
