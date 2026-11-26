<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal Google Calendar v3 client, same standard OAuth 2.0 user-delegation
 * shape as GoogleDriveClient (the site owner authorizes once via a normal
 * Google sign-in screen; we store the resulting refresh token and use it to
 * keep getting fresh access tokens) - but with its own client credentials
 * and its own refresh token, kept apart from the Drive ones, because adding
 * the Calendar scope requires a fresh consent screen anyway (Google does
 * not retroactively widen an existing refresh token's scope).
 *
 * Used only to create the one-off "book a meeting with the teacher" event
 * on the owner's own primary calendar with conferenceDataVersion=1, which
 * is what makes Google generate a real meet.google.com link for it.
 */
class GoogleCalendarClient
{
    public function isConfigured(): bool
    {
        return (bool) setting('assessment_calendar_client_id')
            && (bool) setting('assessment_calendar_client_secret')
            && (bool) setting('assessment_calendar_refresh_token');
    }

    private function getAccessToken(): ?string
    {
        $clientId     = trim((string) setting('assessment_calendar_client_id'));
        $clientSecret = trim((string) setting('assessment_calendar_client_secret'));
        $refreshToken = trim((string) setting('assessment_calendar_refresh_token'));

        if (!$clientId || !$clientSecret || !$refreshToken) {
            return null;
        }

        return Cache::remember('assessment_calendar_access_token', 3000, function () use ($clientId, $clientSecret, $refreshToken) {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type'    => 'refresh_token',
            ]);

            if (!$response->successful()) {
                Log::warning('Google Calendar token refresh failed: ' . $response->body());
                return null;
            }

            return $response->json('access_token');
        });
    }

    /**
     * Creates a 15-minute (or $end-$start) event on the owner's primary
     * calendar and asks Google to attach a Meet conference to it. Returns
     * ['meetLink' => ..., 'eventId' => ...] or null on any failure - every
     * caller treats null the same as "no Calendar connected yet", so a
     * booking never fails just because this couldn't produce a link.
     */
    public function createMeetEvent(string $summary, string $description, Carbon $start, Carbon $end): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        $requestId = 'brainova-' . bin2hex(random_bytes(8));

        // conferenceDataVersion=1 is what tells the Calendar API to actually
        // honour conferenceData.createRequest below - without it, the
        // request succeeds but no Meet link is generated at all. It's a
        // query param, not a body field, so it goes on the URL.
        $response = Http::withToken($token)->post(
            'https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1',
            [
                'summary'     => $summary,
                'description' => $description,
                'start'       => ['dateTime' => $start->toIso8601String(), 'timeZone' => config('app.timezone', 'UTC')],
                'end'         => ['dateTime' => $end->toIso8601String(), 'timeZone' => config('app.timezone', 'UTC')],
                'conferenceData' => [
                    'createRequest' => [
                        'requestId'            => $requestId,
                        'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                    ],
                ],
            ]
        );

        if (!$response->successful()) {
            Log::warning('Google Calendar event create failed: ' . $response->body());
            return null;
        }

        $entryPoints = $response->json('conferenceData.entryPoints') ?? [];
        $video = collect($entryPoints)->firstWhere('entryPointType', 'video');

        if (!$video || empty($video['uri'])) {
            return null;
        }

        return ['meetLink' => $video['uri'], 'eventId' => $response->json('id')];
    }
}
