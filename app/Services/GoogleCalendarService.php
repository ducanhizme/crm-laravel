<?php

namespace App\Services;

use App\Models\Event as CrmEvent;
use App\Models\User;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class GoogleCalendarService
{
    protected Google_Client $client;
    protected Google_Service_Calendar $service;
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->client = $this->initializeGoogleClientForUser();
        $this->service = new Google_Service_Calendar($this->client);
    }

    private function initializeGoogleClientForUser(): Google_Client
    {
        if (!$this->user->google_calendar_access_token) {
            throw new Exception('User has not authenticated with Google Calendar.');
        }

        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect_uri')); // Not strictly needed for API calls after auth, but good practice

        try {
            $accessToken = Crypt::decryptString($this->user->google_calendar_access_token);
            $client->setAccessToken($accessToken);
        } catch (Exception $e) {
            Log::error('Failed to decrypt access token for user ' . $this->user->id, ['exception' => $e]);
            throw new Exception('Invalid access token configuration for user.');
        }

        if ($this->isTokenExpired()) {
            $this->refreshToken($client);
        }

        return $client;
    }

    private function isTokenExpired(): bool
    {
        return $this->user->google_calendar_token_expires_at &&
               Carbon::now()->gte($this->user->google_calendar_token_expires_at->subSeconds(30)); // Check if token expires in next 30s
    }

    private function refreshToken(Google_Client $client): void
    {
        if (!$this->user->google_calendar_refresh_token) {
            Log::warning('No refresh token available for user ' . $this->user->id . ' to refresh Google Calendar token.');
            throw new Exception('Google Calendar refresh token is missing. Please re-authenticate.');
        }

        try {
            $decryptedRefreshToken = Crypt::decryptString($this->user->google_calendar_refresh_token);
            $client->fetchAccessTokenWithRefreshToken($decryptedRefreshToken);
            $newAccessToken = $client->getAccessToken(); // This will include new access_token, expires_in, and potentially a new refresh_token

            $this->user->google_calendar_access_token = Crypt::encryptString($newAccessToken['access_token']);

            // Google might not always return a new refresh token. Only update if one is provided.
            if (isset($newAccessToken['refresh_token'])) {
                $this->user->google_calendar_refresh_token = Crypt::encryptString($newAccessToken['refresh_token']);
            }

            $this->user->google_calendar_token_expires_at = Carbon::now()->addSeconds($newAccessToken['expires_in']);
            $this->user->save();

            // Update the client instance with the new token
            $this->client->setAccessToken($newAccessToken['access_token']);

        } catch (Exception $e) {
            Log::error('Failed to refresh Google Calendar token for user ' . $this->user->id, ['exception' => $e->getMessage()]);
            // Potentially, the refresh token is invalid. User might need to re-authenticate.
            // For now, we throw an exception to indicate failure.
            throw new Exception('Failed to refresh Google Calendar token: ' . $e->getMessage() . '. Please re-authenticate.');
        }
    }

    public function createEvent(CrmEvent $crmEvent): ?string
    {
        try {
            $googleEvent = new Google_Service_Calendar_Event([
                'summary' => $crmEvent->title,
                'description' => $crmEvent->description,
                'start' => new Google_Service_Calendar_EventDateTime([
                    'dateTime' => $crmEvent->start_time->toRfc3339String(),
                    'timeZone' => config('app.timezone'), // Or user's timezone if available
                ]),
                'end' => new Google_Service_Calendar_EventDateTime([
                    'dateTime' => $crmEvent->end_time->toRfc3339String(),
                    'timeZone' => config('app.timezone'), // Or user's timezone if available
                ]),
            ]);

            $calendarId = 'primary';
            $createdEvent = $this->service->events->insert($calendarId, $googleEvent);
            return $createdEvent->getId();
        } catch (Exception $e) {
            Log::error('Google Calendar API Error - Create Event for user ' . $this->user->id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'crm_event_id' => $crmEvent->id,
            ]);
            // Optionally re-throw or handle more gracefully
            // For now, returning null indicates failure to the controller
            return null;
        }
    }

    public function updateEvent(CrmEvent $crmEvent): ?Google_Service_Calendar_Event
    {
        if (!$crmEvent->google_calendar_event_id) {
            Log::warning('Attempted to update Google Calendar event without a google_calendar_event_id for CRM event ' . $crmEvent->id);
            return null;
        }

        try {
            $calendarId = 'primary';
            $googleEvent = $this->service->events->get($calendarId, $crmEvent->google_calendar_event_id);

            $googleEvent->setSummary($crmEvent->title);
            $googleEvent->setDescription($crmEvent->description);

            $startDateTime = new Google_Service_Calendar_EventDateTime();
            $startDateTime->setDateTime($crmEvent->start_time->toRfc3339String());
            $startDateTime->setTimeZone(config('app.timezone'));
            $googleEvent->setStart($startDateTime);

            $endDateTime = new Google_Service_Calendar_EventDateTime();
            $endDateTime->setDateTime($crmEvent->end_time->toRfc3339String());
            $endDateTime->setTimeZone(config('app.timezone'));
            $googleEvent->setEnd($endDateTime);

            $updatedEvent = $this->service->events->update($calendarId, $googleEvent->getId(), $googleEvent);
            return $updatedEvent;
        } catch (Exception $e) {
            Log::error('Google Calendar API Error - Update Event for user ' . $this->user->id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'crm_event_id' => $crmEvent->id,
                'google_event_id' => $crmEvent->google_calendar_event_id,
            ]);
            return null;
        }
    }

    public function deleteEvent(string $googleCalendarEventId): bool
    {
        try {
            $calendarId = 'primary';
            $this->service->events->delete($calendarId, $googleCalendarEventId);
            return true;
        } catch (Exception $e) {
            // Specific check for "410 Gone" which means already deleted
            if ($e instanceof \Google\Service\Exception && $e->getCode() == 410) {
                Log::info('Google Calendar API Info - Delete Event for user ' . $this->user->id . ': Event was already deleted (410 Gone).', [
                    'google_event_id' => $googleCalendarEventId,
                ]);
                return true; // Treat as success if already gone
            }
            Log::error('Google Calendar API Error - Delete Event for user ' . $this->user->id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'google_event_id' => $googleCalendarEventId,
            ]);
            return false;
        }
    }
}
