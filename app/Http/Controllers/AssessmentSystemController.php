<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use Illuminate\Http\Request;

/**
 * Hands a logged-in LMS user off to the standalone Assessment & Placement
 * System (assessment-system/) with a signed, short-lived token instead of
 * that app's old demo role switcher. The token carries who the LMS says
 * this is (uid/role/name/children) - the Google Apps Script backend
 * (assessment-system/backend/Code.gs) verifies this same signature on
 * every request and scopes a student/parent to their own data, as long
 * as ASSESSMENT_SYSTEM_AUTH_SECRET here matches the AUTH_SECRET pasted
 * into that script's Script Properties.
 */
class AssessmentSystemController extends Controller
{
    public function launch(Request $request)
    {
        $secret = config('services.assessment_system.auth_secret');
        $url = config('services.assessment_system.url');

        if (! $secret || ! $url) {
            abort(500, 'The Assessment System is not configured yet. Set ASSESSMENT_SYSTEM_AUTH_SECRET in .env (see assessment-system/backend/DEPLOY.md).');
        }

        $claims = $this->claimsForUser($request->user());

        if (! $claims) {
            abort(403, 'Your account cannot open the Assessment System.');
        }

        $claims['exp'] = now()->addHours(3)->timestamp;

        $payload = $this->base64UrlEncode(json_encode($claims));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $payload, $secret, true));
        $token = $payload.'.'.$signature;

        $query = ['t' => $token];

        // Not a secret - just points a brand-new device at the right
        // Sheet automatically. The shared write-access token itself is
        // never sent here; it stays only in the app's own Settings page.
        $sheetsUrl = config('services.assessment_system.sheets_url');
        if ($sheetsUrl) {
            $query['su'] = $sheetsUrl;
        }

        return redirect(rtrim($url, '/').'?'.http_build_query($query));
    }

    private function claimsForUser($user): ?array
    {
        switch ((int) $user->role_id) {
            case RoleEnum::SUPERADMIN:
            case RoleEnum::ADMIN:
                return ['uid' => $user->id, 'role' => 'admin', 'name' => $user->name];

            case RoleEnum::TEACHER:
                return ['uid' => $user->id, 'role' => 'teacher', 'name' => $user->name];

            case RoleEnum::STUDENT:
                if (! $user->student) {
                    return null;
                }

                return ['uid' => $user->student->id, 'role' => 'student', 'name' => $user->name];

            case RoleEnum::GUARDIAN:
                if (! $user->parent) {
                    return null;
                }

                return [
                    'uid' => $user->parent->id,
                    'role' => 'parent',
                    'name' => $user->name,
                    'children' => $user->parent->children()->pluck('id')->values()->all(),
                ];

            default:
                return null;
        }
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
