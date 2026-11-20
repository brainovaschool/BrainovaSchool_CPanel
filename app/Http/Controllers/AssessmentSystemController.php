<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\StudentInfo\ParentGuardian;
use App\Models\StudentInfo\Student;
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
        // Reads .env directly rather than config('services.assessment_system.*') -
        // this app's .cpanel.yml deploy script doesn't copy config/ to the
        // live server, so a config() value here would always be stale/null
        // in production regardless of what's set in .env.
        $secret = trim((string) env('ASSESSMENT_SYSTEM_AUTH_SECRET'));
        // A blank or whitespace-only .env value (e.g. ASSESSMENT_SYSTEM_URL=" ")
        // still counts as "set" to env() - trim it and treat blank the
        // same as missing, so this always falls back to the real default
        // instead of redirecting to a literal space.
        $url = trim((string) env('ASSESSMENT_SYSTEM_URL'));
        if ($url === '') {
            $url = '/assessment-system/brainova-demo.html';
        }

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
        $sheetsUrl = trim((string) env('ASSESSMENT_SYSTEM_SHEETS_URL'));
        if ($sheetsUrl !== '') {
            $query['su'] = $sheetsUrl;
        }

        // Built from the CURRENT request's own host rather than trusting
        // redirect()'s default root resolution (APP_URL / tenancy root) -
        // this app is multi-tenant and .env.example's own APP_URL is a
        // placeholder domain, so a bare redirect($relativePath) risks
        // sending the browser to the wrong host entirely. getSchemeAndHttpHost()
        // is always exactly the domain the person is on right now.
        $target = preg_match('#^https?://#i', $url)
            ? $url
            : $request->getSchemeAndHttpHost().'/'.ltrim($url, '/');

        return redirect($target.'?'.http_build_query($query));
    }

    /** Admin-only upload form for replacing the live assessment HTML. */
    public function deployForm(Request $request)
    {
        if (! in_array((int) $request->user()->role_id, [RoleEnum::SUPERADMIN, RoleEnum::ADMIN], true)) {
            abort(403);
        }

        return view('backend.assessment-system.deploy');
    }

    /**
     * Replaces the live assessment HTML with an uploaded file, no git or
     * cPanel needed - this is the "do it myself if Claude isn't here"
     * tool. It will only deploy a file that still contains the Google
     * Sheets/LMS wiring (checked by looking for the two markers every
     * integrated build has), unless the person explicitly confirms they
     * want to deploy an unwired file anyway. The previously-live file is
     * always kept as a timestamped backup first, so this is reversible.
     */
    public function deploy(Request $request)
    {
        if (! in_array((int) $request->user()->role_id, [RoleEnum::SUPERADMIN, RoleEnum::ADMIN], true)) {
            abort(403);
        }

        $request->validate([
            'html' => ['required', 'file', 'max:10240'], // 10MB
        ]);

        $file = $request->file('html');
        $ext = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, ['html', 'htm'], true)) {
            return back()->with('deployError', 'That file is not an .html file.');
        }

        $content = file_get_contents($file->getRealPath());
        $wired = str_contains($content, 'BrainovaAPI') && str_contains($content, 'applyLmsIdentity');

        if (! $wired && ! $request->boolean('confirm')) {
            return back()->with('deployWarning', 'This file does not look wired up to your Google Sheet or LMS login yet - it is missing the integration code a developer needs to add first. If you deploy it anyway, assessments taken through it will not save anywhere, and signing in through the LMS link will not work.')
                ->with('pendingUpload', true);
        }

        $live = public_path('assessment-system/brainova-demo.html');
        $backupDir = public_path('assessment-system/backups');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        if (file_exists($live)) {
            copy($live, $backupDir.'/brainova-demo-'.now()->format('Y-m-d-His').'.html');
        }

        file_put_contents($live, $content);

        return back()->with('deploySuccess', $wired
            ? 'Deployed. The Assessment System is now running this file.'
            : 'Deployed anyway, as confirmed. Remember: it will not save data or respond to LMS logins until the integration code is added.');
    }

    /**
     * The admin/teacher's own "Import from LMS" button (Students page)
     * calls this to pull the current roster into the assessment app, so
     * a student can be assigned an assessment without ever having to
     * log into the tool themselves first. Same-origin JSON, no CORS -
     * the assessment app is hosted on this app's own domain (Step 7),
     * so a plain fetch() with the existing LMS session cookie is enough;
     * nothing new needs opening up.
     */
    public function roster(Request $request)
    {
        $user = $request->user();

        if (! in_array((int) $user->role_id, [RoleEnum::SUPERADMIN, RoleEnum::ADMIN, RoleEnum::TEACHER], true)) {
            abort(403);
        }

        $students = Student::active()
            ->with('session_class_student.class')
            ->get()
            ->map(function (Student $s) {
                $className = optional(optional($s->session_class_student)->class)->name;
                preg_match('/\d+/', (string) $className, $m);

                return [
                    'id' => $s->id,
                    'name' => $s->full_name,
                    'grade' => isset($m[0]) ? (int) $m[0] : null,
                    'parentId' => $s->parent_guardian_id,
                ];
            })
            ->values();

        $parents = ParentGuardian::active()
            ->with(['user', 'children'])
            ->get()
            ->map(function (ParentGuardian $p) {
                return [
                    'id' => $p->id,
                    'name' => optional($p->user)->name ?: 'Parent',
                    'childIds' => $p->children->pluck('id')->values()->all(),
                ];
            })
            ->values();

        return response()->json(['students' => $students, 'parents' => $parents]);
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
