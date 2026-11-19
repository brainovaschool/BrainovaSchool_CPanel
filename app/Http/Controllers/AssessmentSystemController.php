<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use Illuminate\Http\Request;

/**
 * Hands a logged-in LMS user off to the standalone Assessment & Placement
 * System (assessment-system/) with a signed, short-lived token instead of
 * that app's old demo role switcher. The token only carries who the LMS
 * says this is (uid/role/name) - the assessment app's front end reads
 * those claims to set its own role/identity, but nothing here checks the
 * signature on the way back in yet. That's Step 5: the Google Apps
 * Script backend verifying this same signature on every request, once
 * ASSESSMENT_SYSTEM_AUTH_SECRET is also pasted into its Script Properties.
 */
class AssessmentSystemController extends Controller
{
    public function launch(Request $request)
    {
        $secret = config('services.assessment_system.auth_secret');
        $url = config('services.assessment_system.url');

        if (! $secret || ! $url) {
            abort(500, 'The Assessment System is not configured yet. Set ASSESSMENT_SYSTEM_AUTH_SECRET and ASSESSMENT_SYSTEM_URL in .env.');
        }

        $claims = $this->claimsForUser($request->user());

        if (! $claims) {
            abort(403, 'Your account cannot open the Assessment System.');
        }

        $claims['exp'] = now()->addHours(3)->timestamp;

        $payload = $this->base64UrlEncode(json_encode($claims));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $payload, $secret, true));
        $token = $payload.'.'.$signature;

        return redirect(rtrim($url, '/').'?t='.$token);
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
