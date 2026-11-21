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

    /** Every published assessment currently in the Sheet, or [] on any failure. */
    public function fetchPublishedAssessments(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::timeout(10)->get($this->url(), [
                'action' => 'bootstrap',
                'token' => $this->token(),
            ]);

            if (! $response->ok()) {
                return [];
            }

            $data = $response->json();
            if (! ($data['ok'] ?? false)) {
                return [];
            }

            $assessments = $data['state']['assessments'] ?? [];

            return array_values(array_filter(
                $assessments,
                fn ($a) => ($a['status'] ?? null) === 'published'
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
}
