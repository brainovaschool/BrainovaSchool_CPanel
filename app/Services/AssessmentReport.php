<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Reads a student's latest RELEASED Assessment & Placement result
 * straight from the Google Sheet (see assessment-system/backend/Code.gs's
 * "report" action) - this app's own database never stores assessment
 * data; that system was deliberately built to keep its data in Google
 * Sheets only, separate from here. Reads env() directly rather than
 * config('services.*') for the same reason as B2Storage::credential():
 * this app's .cpanel.yml never deploys config/ to the live server.
 *
 * Every failure mode (not configured yet, Sheet unreachable, nothing
 * released yet) all just mean "nothing to show" - callers get null and
 * show no card, never an error on a page that happens to display this.
 */
class AssessmentReport
{
    public static function forStudent(int $studentId): ?array
    {
        $url = env('ASSESSMENT_SYSTEM_SHEETS_URL');
        $token = env('ASSESSMENT_SYSTEM_REPORT_TOKEN');

        if (! $url || ! $token) {
            return null;
        }

        return Cache::remember("assessment_report_student_{$studentId}", 600, function () use ($url, $token, $studentId) {
            try {
                $response = Http::timeout(5)->get($url, [
                    'action' => 'report',
                    'token' => $token,
                    'studentId' => $studentId,
                ]);
            } catch (\Throwable $e) {
                return null;
            }

            if (! $response->ok()) {
                return null;
            }

            $data = $response->json();

            return ($data['ok'] ?? false) ? ($data['report'] ?? null) : null;
        });
    }
}
