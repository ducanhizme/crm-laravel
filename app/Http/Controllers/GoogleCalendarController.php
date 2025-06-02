<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Calendar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon;

class GoogleCalendarController extends Controller
{
    private function getGoogleClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect_uri'));
        $client->setScopes([
            Google_Service_Calendar::CALENDAR,
            Google_Service_Calendar::CALENDAR_EVENTS,
        ]);
        $client->setAccessType('offline'); // To get a refresh token
        $client->setPrompt('consent');     // Forces consent screen every time, good for ensuring refresh token
        return $client;
    }

    public function redirectToGoogle()
    {
        $client = $this->getGoogleClient();
        $authUrl = $client->createAuthUrl();
        return Redirect::away($authUrl);
    }

    public function handleGoogleCallback(Request $request)
    {
        if ($request->has('error')) {
            // Handle error, e.g., user denied access
            // You might want to redirect to a frontend page with an error message
            return Redirect::to(env('FRONTEND_URL', '/') . '?google_auth_error=' . $request->input('error'));
        }

        if (!$request->has('code')) {
            return Redirect::to(env('FRONTEND_URL', '/') . '?google_auth_error=missing_code');
        }

        $client = $this->getGoogleClient();
        $authCode = $request->input('code');

        try {
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        } catch (\Exception $e) {
            // Handle error fetching access token
            report($e); // Log the exception
            return Redirect::to(env('FRONTEND_URL', '/') . '?google_auth_error=token_fetch_failed');
        }

        if (isset($accessToken['error'])) {
            report(new \Exception('Google OAuth Error: ' . $accessToken['error'] . ' - ' . ($accessToken['error_description'] ?? 'No description')));
            return Redirect::to(env('FRONTEND_URL', '/') . '?google_auth_error=' . $accessToken['error']);
        }

        $user = Auth::user();
        if (!$user) {
             // Should not happen if routes are protected by auth middleware
            return Redirect::to(env('FRONTEND_URL', '/') . '?google_auth_error=unauthenticated');
        }

        $user->google_calendar_access_token = Crypt::encryptString($accessToken['access_token']);

        if (isset($accessToken['refresh_token'])) {
            $user->google_calendar_refresh_token = Crypt::encryptString($accessToken['refresh_token']);
        }

        if (isset($accessToken['expires_in'])) {
            $user->google_calendar_token_expires_at = Carbon::now()->addSeconds($accessToken['expires_in']);
        } else {
            // Default to 1 hour if not provided, though it usually is
            $user->google_calendar_token_expires_at = Carbon::now()->addHour();
        }

        $user->save();

        // Redirect to a frontend page indicating success
        // You might want to include some query parameters or use a specific route
        return Redirect::to(env('FRONTEND_URL', '/') . '?google_auth_success=true');
    }
}
