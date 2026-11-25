<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Talks to the same Google Sheet backend AssessmentReport.php reads
 * from, but through the full read/write surface
 * (assessment-system/backend/Code.gs's doGet?action=bootstrap and
 * doPost?action=write) rather than the narrow read-only "report"
 * action - authenticated with the one shared API_TOKEN (the same
 * "legacy/admin" token already pasted into the standalone app's own
 * Settings page, never the per-user signed tokens identity.js hands
 * out to a logged-in LMS user). Used only to get a freshly-booked
 * assessment lead into the right assessment automatically, with zero
 * manual admin work. Every failure here just means "didn't get
 * auto-assigned yet" - logged and swallowed, never thrown, because a
 * parent's booking confirmation must never fail because of this.
 */
class AssessmentSheetClient
{
    private function url(): string
    {
        return trim((string) env('ASSESSMENT_SYSTEM_SHEETS_URL'));
    }

    private function token(): string
    {
        return trim((string) env('ASSESSMENT_SYSTEM_API_TOKEN'));
    }

    public function isConfigured(): bool
    {
        return $this->url() !== '' && $this->token() !== '';
    }

    /**
     * Every published assessment the admin has explicitly marked for the
     * public "book an assessment" page (the leadIntake flag, set in the
     * assessment Builder), or [] on any failure. A published assessment
     * without that flag is never matched to a lead - it's reserved for
     * real enrolled students, same as before this flag existed.
     */
    public function fetchPublishedAssessments(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            // A dedicated, lighter action than 'bootstrap' - that one also
            // reads the entire question bank and every simple set's full
            // question list just for this method to throw all of it away,
            // which on a large bank/sheet risked running long enough to
            // intermittently exceed the timeout below. This reads only the
            // one tab actually needed here.
            $response = Http::timeout(10)->get($this->url(), [
                'action' => 'assessments',
                'token' => $this->token(),
            ]);

            if (! $response->ok()) {
                return [];
            }

            $data = $response->json();
            if (! ($data['ok'] ?? false)) {
                return [];
            }

            $assessments = $data['assessments'] ?? [];

            return array_values(array_filter(
                $assessments,
                fn ($a) => ($a['status'] ?? null) === 'published' && ($a['leadIntake'] ?? false)
            ));
        } catch (\Throwable $e) {
            Log::warning('AssessmentSheetClient::fetchPublishedAssessments failed: '.$e->getMessage());
            return [];
        }
    }

    /**
     * Registers this lead as a lightweight student row (with the real
     * grade from the booking form - never null, unlike the generic
     * "first login" auto-registration identity.js does for any brand
     * new LMS student) and, if a matching assessment was found, adds
     * the lead's id to just that one assessment's assignedTo list
     * without touching anything else about it. One combined write.
     * Returns true only on a confirmed success from the Sheet.
     */
    public function assignLead(string $leadUid, string $name, int $grade, ?array $assessment): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $payload = [
            'students' => [[
                'id' => $leadUid,
                'name' => $name,
                'grade' => $grade,
            ]],
        ];

        if ($assessment) {
            $assignedTo = array_values(array_unique(array_merge(
                array_map('strval', $assessment['assignedTo'] ?? []),
                [$leadUid]
            )));

            $payload['assessments'] = [[
                'id' => $assessment['id'],
                'assignedTo' => $assignedTo,
            ]];
        }

        try {
            $response = Http::asJson()->timeout(10)->post($this->url(), [
                'action' => 'write',
                'kind' => 'snapshot',
                'token' => $this->token(),
                'payload' => $payload,
            ]);

            if (! $response->ok()) {
                return false;
            }

            return (bool) ($response->json()['ok'] ?? false);
        } catch (\Throwable $e) {
            Log::warning('AssessmentSheetClient::assignLead failed: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Flips just the `released` flag on one already-submitted attempt,
     * without touching anything else about it - a normal full attempt
     * write (kind:'attempt', writeAttempt_ in Code.gs) always sends the
     * WHOLE attempt object, so sending only {id, released} through that
     * path would blank out every other column. This instead piggybacks
     * on the generic snapshot merge (kind:'snapshot', upsertManyRows_),
     * which only ever touches the columns present in what's sent.
     * Used so a lead's parent can see the limited report the moment the
     * test is done, without a teacher manually clicking "release" -
     * the exact same `released` flag a teacher's own toggle sets.
     */
    public function releaseAttempt(string $attemptId): bool
    {
        if (! $this->isConfigured() || $attemptId === '') {
            return false;
        }

        try {
            $response = Http::asJson()->timeout(10)->post($this->url(), [
                'action' => 'write',
                'kind' => 'snapshot',
                'token' => $this->token(),
                'payload' => [
                    'attempts' => [[
                        'id' => $attemptId,
                        'released' => true,
                    ]],
                ],
            ]);

            if (! $response->ok()) {
                return false;
            }

            return (bool) ($response->json()['ok'] ?? false);
        } catch (\Throwable $e) {
            Log::warning('AssessmentSheetClient::releaseAttempt failed: '.$e->getMessage());
            return false;
        }
    }
}
