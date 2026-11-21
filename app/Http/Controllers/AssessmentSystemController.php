<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\LearningEngine\FeatureAccess;
use App\Models\Role;
use App\Models\Setting;
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
        $claims = $this->claimsForUser($request->user());

        if (! $claims) {
            abort(403, 'Your account cannot open the Assessment System.');
        }

        $claims['exp'] = now()->addHours(3)->timestamp;

        $target = self::buildAssessmentLink($claims, $request);

        if (! $target) {
            abort(500, 'The Assessment System is not configured yet. Set ASSESSMENT_SYSTEM_AUTH_SECRET in .env (see assessment-system/backend/DEPLOY.md).');
        }

        return redirect($target);
    }

    /**
     * Builds the final signed assessment-system URL for a set of claims
     * (uid/role/name[/children], plus 'exp' already set by the caller).
     * Shared by launch() (a logged-in LMS user - unchanged output, same
     * bytes as before this was extracted) and
     * AssessmentBookingController::store() (an anonymous lead whose uid
     * is a LEAD-{id} pseudo id, never a real Laravel user). Returns null
     * if ASSESSMENT_SYSTEM_AUTH_SECRET isn't configured.
     */
    public static function buildAssessmentLink(array $claims, Request $request): ?string
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
            return null;
        }

        $payload = self::base64UrlEncode(json_encode($claims));
        $signature = self::base64UrlEncode(hash_hmac('sha256', $payload, $secret, true));
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

        return $target.'?'.http_build_query($query);
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
            ->with(['session_class_student.class', 'user'])
            ->get()
            ->map(function (Student $s) {
                return [
                    'id' => $s->id,
                    'name' => $s->full_name,
                    'grade' => $this->gradeForStudent($s),
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

    /**
     * Pakistan's usual school-age rule: grade = age in years - 5 (a
     * 6-year-old is in Grade/Class 1, a 7-year-old in Grade 2, and so
     * on). Uses date of birth rather than the LMS's own class/section
     * data on purpose - editing that to carry a clean numeric grade
     * would risk side effects on whatever else in this app depends on
     * it, whereas date of birth is just read, never touched. Falls
     * back to guessing a number out of the class name only if date of
     * birth isn't on file; an admin can always correct the result by
     * hand afterward (the Grade field on the assessment app's own
     * Students page), this is only ever a starting guess.
     */
    private function gradeForStudent(Student $s): ?int
    {
        $dob = optional($s->user)->date_of_birth;

        if ($dob) {
            try {
                $age = \Carbon\Carbon::parse($dob)->age;

                return max(0, $age - 5);
            } catch (\Throwable $e) {
                // fall through to the class-name guess below
            }
        }

        $className = optional(optional($s->session_class_student)->class)->name;
        preg_match('/\d+/', (string) $className, $m);

        return isset($m[0]) ? (int) $m[0] : null;
    }

    /**
     * Two separate gates, deliberately kept apart because they're
     * decided by two different people for two different reasons:
     *   - Students: the existing per-student "Feature Access" toggle
     *     (App\Models\LearningEngine\FeatureAccess) - visible to nobody
     *     until an admin either flips it on for everyone or checks
     *     individual students on that existing screen.
     *   - Any other staff role (Teacher, or a custom role like
     *     Coordinator/HR): see assessmentSystemRoleAllowed() - a
     *     dedicated small checklist (Assessment System -> Staff Access)
     *     rather than this hardcoding which roles count as "staff".
     * Admin/Superadmin always get in; parents follow their child's own
     * link into the tool and aren't separately gated here.
     */
    private function claimsForUser($user): ?array
    {
        $roleId = (int) $user->role_id;

        if (in_array($roleId, [RoleEnum::SUPERADMIN, RoleEnum::ADMIN], true)) {
            return ['uid' => $user->id, 'role' => 'admin', 'name' => $user->name];
        }

        if ($roleId === RoleEnum::STUDENT) {
            if (! $user->student) {
                return null;
            }

            if (! FeatureAccess::isVisibleFor('assessment', $user->student->id)) {
                return null;
            }

            return ['uid' => $user->student->id, 'role' => 'student', 'name' => $user->name];
        }

        if ($roleId === RoleEnum::GUARDIAN) {
            if (! $user->parent) {
                return null;
            }

            return [
                'uid' => $user->parent->id,
                'role' => 'parent',
                'name' => $user->name,
                'children' => $user->parent->children()->pluck('id')->values()->all(),
            ];
        }

        if (assessmentSystemRoleAllowed($roleId)) {
            return ['uid' => $user->id, 'role' => 'teacher', 'name' => $user->name];
        }

        return null;
    }

    /** Admin-only checklist of which staff roles may open the Assessment System. */
    public function accessForm(Request $request)
    {
        if (! in_array((int) $request->user()->role_id, [RoleEnum::SUPERADMIN, RoleEnum::ADMIN], true)) {
            abort(403);
        }

        $roles = Role::active()
            ->whereNotIn('id', [RoleEnum::SUPERADMIN, RoleEnum::ADMIN])
            ->orderBy('name')
            ->get();
        $allowed = assessmentSystemAllowedRoleIds();

        return view('backend.assessment-system.access', compact('roles', 'allowed'));
    }

    public function accessUpdate(Request $request)
    {
        if (! in_array((int) $request->user()->role_id, [RoleEnum::SUPERADMIN, RoleEnum::ADMIN], true)) {
            abort(403);
        }

        $roleIds = array_map('intval', (array) $request->input('role_ids', []));
        Setting::updateOrCreate(['name' => 'assessment_system_allowed_roles'], ['value' => $roleIds]);

        return back()->with('deploySuccess', 'Updated which staff roles can open the Assessment System.');
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
