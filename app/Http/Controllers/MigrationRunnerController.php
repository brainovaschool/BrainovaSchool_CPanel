<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use App\Models\Academic\Classes;
use App\Models\Academic\Section;
use App\Models\Academic\Subject;
use App\Models\Academic\SubjectAssign;
use App\Models\Academic\SubjectAssignChildren;
use App\Models\LearningEngine\Skill;
use App\Models\OnlineExamination\Answer;
use App\Models\OnlineExamination\AnswerChildren;
use App\Models\OnlineExamination\OnlineExam;
use App\Models\OnlineExamination\OnlineExamChildrenQuestions;
use App\Models\OnlineExamination\OnlineExamChildrenStudents;
use App\Models\OnlineExamination\QuestionBank;
use App\Models\OnlineExamination\QuestionBankChildren;
use App\Models\OnlineExamination\QuestionGroup;
use App\Models\Staff\Department;
use App\Models\Staff\Designation;
use App\Models\Staff\Staff;
use App\Models\StudentInfo\ParentGuardian;
use App\Models\StudentInfo\SessionClassStudent;
use App\Models\StudentInfo\Student;
use App\Repositories\LearningEngine\LearningEventRepository;
use App\Repositories\StudentInfo\ParentGuardianRepository;
use App\Repositories\StudentInfo\StudentRepository;
use App\Repositories\UserRepository;

/**
 * Runs pending tenant migrations from the browser, for hosts without shell /
 * artisan access, and syncs the permissions that ship alongside them.
 * Guarded by a fixed key + main-administrator login. Idempotent.
 */
class MigrationRunnerController extends Controller
{
    private const KEY = 'brainova-db-2026';

    /** Permission groups that must exist for the newer Website Setup modules. */
    private const PERMISSION_GROUPS = [
        'program_category' => ['read' => 'program_category_read', 'create' => 'program_category_create', 'update' => 'program_category_update', 'delete' => 'program_category_delete'],
        'program_focus'    => ['read' => 'program_focus_read', 'create' => 'program_focus_create', 'update' => 'program_focus_update', 'delete' => 'program_focus_delete'],
        'program'          => ['read' => 'program_read', 'create' => 'program_create', 'update' => 'program_update', 'delete' => 'program_delete'],
        'testimonial'      => ['read' => 'testimonial_read', 'create' => 'testimonial_create', 'update' => 'testimonial_update', 'delete' => 'testimonial_delete'],
        'trial_slot'       => ['read' => 'trial_slot_read', 'create' => 'trial_slot_create', 'update' => 'trial_slot_update', 'delete' => 'trial_slot_delete'],
        'skill'            => ['read' => 'skill_read', 'create' => 'skill_create', 'update' => 'skill_update', 'delete' => 'skill_delete'],
    ];

    public function run(string $key)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        Artisan::call('migrate', [
            '--path'  => 'database/migrations/tenant',
            '--force' => true,
        ]);
        $migrate = trim(Artisan::output()) ?: 'Nothing to migrate.';

        $perms = $this->syncPermissions();

        // Program catalogue seeder is idempotent (updateOrCreate on slug) — safe to re-run.
        $seed = 'skipped';
        try {
            (new \Database\Seeders\WebsiteSetup\ProgramCatalogSeeder())->run();
            $seed = 'ok — categories/focuses/programs synced';
        } catch (\Throwable $e) {
            $seed = 'error: ' . $e->getMessage();
        }

        // Starter testimonials + reviews (idempotent — matched on name + type).
        $tm = 'skipped';
        try {
            (new \Database\Seeders\WebsiteSetup\TestimonialSeeder())->run();
            $tm = 'ok — testimonials/reviews synced';
        } catch (\Throwable $e) {
            $tm = 'error: ' . $e->getMessage();
        }

        // Coding demo notice (idempotent — matched on title).
        $notice = 'skipped';
        try {
            (new \Database\Seeders\WebsiteSetup\StarterContentSeeder())->run();
            $notice = 'ok — coding demo notice synced';
        } catch (\Throwable $e) {
            $notice = 'error: ' . $e->getMessage();
        }

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . e($migrate) . "\n\nPermissions: " . e($perms)
            . "\nCatalogue seed: " . e($seed)
            . "\nTestimonials seed: " . e($tm)
            . "\nNotice seed: " . e($notice)
            . "</pre>"
        );
    }

    /** One-off: create a dummy student login for testing, reusing the real
     *  student-creation logic so every required relationship is set up
     *  correctly (user account, class/section assignment, etc). Safe to
     *  visit more than once — each visit makes a new, separate dummy student. */
    public function createDemoStudent(string $key, StudentRepository $students)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        $class = Classes::first();
        if (!$class) {
            return response('No class exists yet — create at least one Class (Academic → Classes) before generating a demo student.', 422);
        }
        $section = Section::first();

        $suffix   = now()->format('YmdHis');
        $email    = "demo.student.{$suffix}@brainovaschool.com";
        $password = 'Demo@' . substr($suffix, -6);

        $fake = new \Illuminate\Http\Request();
        $fake->merge([
            'first_name'     => 'Demo',
            'last_name'      => 'Student',
            'email'          => $email,
            'mobile'         => '03000000000',
            'admission_no'   => 'DEMO-' . $suffix,
            'password_type'  => 'custom',
            'password'       => $password,
            'date_of_birth'  => '2015-01-01',
            'admission_date' => now()->format('Y-m-d'),
            'status'         => 1,
            'class'          => $class->id,
            'section'        => $section->id ?? '',
            'siblings_discount' => 0,
        ]);

        $result = $students->store($fake);

        if (!$result['status']) {
            return response('Could not create the demo student: ' . $result['message'], 422);
        }

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . "Demo student created.\n\n"
            . "Email: {$email}\n"
            . "Password: {$password}\n\n"
            . "Log in at the normal login page with these details."
            . "</pre>"
        );
    }

    /** One-off: creates a demo Parent (linked to the latest demo student) and
     *  a demo Teacher (assigned to that student's class/section/subject), for
     *  checking the Parent Learning Snapshot and the Skill Mastery Report as
     *  real logins instead of just as the super admin. Reuses the real
     *  ParentGuardianRepository/UserRepository creation logic. Safe to
     *  revisit — each visit makes a new, separate parent and teacher. */
    public function createDemoFamily(string $key, ParentGuardianRepository $parents, UserRepository $users)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        $student = Student::where('email', 'like', 'demo.student.%')->latest('id')->first();
        if (!$student) {
            return response('No demo student found yet — visit /db/create-demo-student/' . self::KEY . ' first.', 422);
        }

        $classSection = SessionClassStudent::where('session_id', setting('session'))
            ->where('student_id', $student->id)
            ->first();
        if (!$classSection) {
            return response('The demo student has no class/section assignment yet.', 422);
        }

        $subject = Subject::first();
        if (!$subject) {
            return response('No subject exists yet — create at least one Subject (Academic → Subjects) first.', 422);
        }

        $designation = Designation::first();
        $department  = Department::first();
        if (!$designation || !$department) {
            return response('Create at least one Designation and Department (Staff → Designations / Departments) before generating a demo teacher.', 422);
        }

        $suffix = now()->format('YmdHis');

        // ---- Parent, linked to the demo student ----
        $parentEmail    = "demo.parent.{$suffix}@brainovaschool.com";
        $parentPassword = 'Demo@' . substr($suffix, -6);

        $fakeParent = new \Illuminate\Http\Request();
        $fakeParent->merge([
            'guardian_name'     => 'Demo Parent',
            'guardian_email'    => $parentEmail,
            'guardian_mobile'   => '03000000001',
            'guardian_relation' => 'Father',
            'password_type'     => 'custom',
            'password'          => $parentPassword,
            'status'            => 1,
        ]);

        $parentResult = $parents->store($fakeParent);
        if (!$parentResult['status']) {
            return response('Could not create the demo parent: ' . $parentResult['message'], 422);
        }

        $parentGuardian = ParentGuardian::where('guardian_email', $parentEmail)->latest('id')->first();
        $student->parent_guardian_id = $parentGuardian->id;
        $student->save();

        // ---- Teacher, assigned to the demo student's class/section/subject ----
        $teacherEmail = "demo.teacher.{$suffix}@brainovaschool.com";

        $fakeTeacher = new \Illuminate\Http\Request();
        $fakeTeacher->merge([
            'first_name'  => 'Demo',
            'last_name'   => 'Teacher',
            'email'       => $teacherEmail,
            'phone'       => '03000000002',
            'role'        => 5, // Teacher role id
            'designation' => $designation->id,
            'department'  => $department->id,
            'staff_id'    => 'DEMOT-' . $suffix,
            'status'      => 1,
        ]);

        $teacherResult = $users->store($fakeTeacher);
        if ($teacherResult !== 1) {
            return response('Could not create the demo teacher (code ' . $teacherResult . ' — 2 means the staff subscription limit was reached).', 422);
        }

        $teacherStaff = Staff::where('email', $teacherEmail)->latest('id')->first();

        $assign               = new SubjectAssign();
        $assign->session_id   = setting('session');
        $assign->classes_id   = $classSection->classes_id;
        $assign->section_id   = $classSection->section_id;
        $assign->status       = 1;
        $assign->save();

        $assignChild                    = new SubjectAssignChildren();
        $assignChild->subject_assign_id = $assign->id;
        $assignChild->subject_id        = $subject->id;
        $assignChild->staff_id          = $teacherStaff->id;
        $assignChild->status            = 1;
        $assignChild->save();

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . "Demo parent and teacher created and linked to: {$student->first_name} {$student->last_name}\n\n"
            . "PARENT LOGIN\n"
            . "  Email: {$parentEmail}\n"
            . "  Password: {$parentPassword}\n\n"
            . "TEACHER LOGIN\n"
            . "  Email: {$teacherEmail}\n"
            . "  Password: 123456   (staff accounts always start with this password, unrelated to this tool)\n\n"
            . "The teacher is now assigned to teach {$subject->name} in the demo student's class/section,\n"
            . "so Skill Mastery Report -> that class should show the demo student's real data."
            . "</pre>"
        );
    }

    /** One-off: builds a full test fixture for the Phase 1 learning-engine
     *  work — 3 skills, a real graded exam (visible in Online Examination →
     *  Question Bank / Online Exam like any other), and enough additional
     *  practice history (via the same LearningEventRepository::record() every
     *  real grading action uses) to show all four mastery states on the
     *  dashboard. Finds the most recently created demo student. Safe to
     *  re-visit — reuses existing rows by name instead of duplicating them. */
    public function seedDemoExam(string $key, LearningEventRepository $events)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        $student = Student::where('email', 'like', 'demo.student.%')->latest('id')->first();
        if (!$student) {
            return response('No demo student found yet — visit /db/create-demo-student/' . self::KEY . ' first.', 422);
        }

        $classSection = SessionClassStudent::where('session_id', setting('session'))
            ->where('student_id', $student->id)
            ->first();
        if (!$classSection) {
            return response('The demo student has no class/section assignment yet.', 422);
        }

        $subject = Subject::first();
        if (!$subject) {
            return response('No subject exists yet — create at least one Subject (Academic → Subjects) first.', 422);
        }

        $skillA = Skill::firstOrCreate(
            ['title' => 'Two-Digit Addition', 'classes_id' => $classSection->classes_id, 'subject_id' => $subject->id],
            ['slug' => 'demo-two-digit-addition-' . $classSection->classes_id, 'description' => 'Add two two-digit numbers with regrouping.', 'sort_order' => 1, 'status' => 1]
        );
        $skillB = Skill::firstOrCreate(
            ['title' => 'Fractions Basics', 'classes_id' => $classSection->classes_id, 'subject_id' => $subject->id],
            ['slug' => 'demo-fractions-basics-' . $classSection->classes_id, 'description' => 'Identify and compare simple fractions.', 'sort_order' => 2, 'status' => 1]
        );
        Skill::firstOrCreate(
            ['title' => 'Multiplication Tables', 'classes_id' => $classSection->classes_id, 'subject_id' => $subject->id],
            ['slug' => 'demo-multiplication-tables-' . $classSection->classes_id, 'description' => 'Recall multiplication facts up to 12x12.', 'sort_order' => 3, 'status' => 1]
        );

        $group = QuestionGroup::firstOrCreate(
            ['name' => 'Demo Skill Test', 'session_id' => setting('session')],
            ['status' => 1]
        );

        $q1 = QuestionBank::firstOrCreate(
            ['question' => 'True or False: 27 + 15 = 42', 'question_group_id' => $group->id],
            ['session_id' => setting('session'), 'skill_id' => $skillA->id, 'type' => 3, 'answer' => 1, 'mark' => 10, 'status' => 1]
        );

        $q2 = QuestionBank::firstOrCreate(
            ['question' => 'What is 1/2 + 1/4?', 'question_group_id' => $group->id],
            ['session_id' => setting('session'), 'skill_id' => $skillB->id, 'type' => 1, 'total_option' => 4, 'answer' => '3/4', 'mark' => 10, 'status' => 1]
        );
        if ($q2->wasRecentlyCreated) {
            foreach (['3/4', '1/6', '2/6', '1/2'] as $option) {
                QuestionBankChildren::create(['question_bank_id' => $q2->id, 'option' => $option]);
            }
        }

        $exam = OnlineExam::firstOrCreate(
            ['name' => 'Demo Skill Test', 'classes_id' => $classSection->classes_id, 'section_id' => $classSection->section_id],
            [
                'session_id'        => setting('session'),
                'subject_id'        => $subject->id,
                'total_mark'        => 20,
                'start'             => now()->subDay(),
                'end'               => now()->addDay(),
                'published'         => now()->subHour(),
                'question_group_id' => $group->id,
                'status'            => 1,
            ]
        );

        OnlineExamChildrenQuestions::firstOrCreate(['online_exam_id' => $exam->id, 'question_bank_id' => $q1->id]);
        OnlineExamChildrenQuestions::firstOrCreate(['online_exam_id' => $exam->id, 'question_bank_id' => $q2->id]);
        OnlineExamChildrenStudents::firstOrCreate(['online_exam_id' => $exam->id, 'student_id' => $student->id]);

        // A real submitted + graded attempt — right answer on Q1, wrong on Q2 —
        // visible and re-gradeable in the admin UI like any other exam.
        $answer = Answer::firstOrCreate(
            ['online_exam_id' => $exam->id, 'student_id' => $student->id],
            ['result' => 10]
        );
        $ac1 = AnswerChildren::firstOrCreate(
            ['answer_id' => $answer->id, 'question_bank_id' => $q1->id],
            ['answer' => '1', 'evaluation_mark' => 10]
        );
        $ac2 = AnswerChildren::firstOrCreate(
            ['answer_id' => $answer->id, 'question_bank_id' => $q2->id],
            ['answer' => '1/6', 'evaluation_mark' => 0]
        );

        if ($ac1->wasRecentlyCreated) {
            $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillA->id, ['correct' => true, 'source' => 'online_exam']);
        }
        if ($ac2->wasRecentlyCreated) {
            $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillB->id, ['correct' => false, 'source' => 'online_exam']);
        }

        // Extra practice history so the dashboard shows every mastery state,
        // not just "developing" — same record() call any real grading uses.
        if ($ac1->wasRecentlyCreated) {
            for ($i = 0; $i < 8; $i++) {
                $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillA->id, ['correct' => $i !== 3, 'source' => 'demo_seed']);
            }
        }
        if ($ac2->wasRecentlyCreated) {
            $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillB->id, ['correct' => false, 'source' => 'demo_seed']);
        }

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . "Demo exam fixture ready for: {$student->first_name} {$student->last_name} ({$student->email})\n\n"
            . "Skills created: Two-Digit Addition, Fractions Basics, Multiplication Tables\n"
            . "Online Exam: \"Demo Skill Test\" — published, assigned, one attempt submitted and graded.\n\n"
            . "Expected dashboard state:\n"
            . "  Two-Digit Addition  -> advanced (won't appear in \"what's next\")\n"
            . "  Fractions Basics    -> developing, appears in \"Let's Investigate\"\n"
            . "  Multiplication Tables -> not started, appears in \"what's next\"\n\n"
            . "Also visible in the admin panel under Online Examination -> Question Bank / Online Exam,\n"
            . "so you can re-grade it by hand there too if you want to see that flow."
            . "</pre>"
        );
    }

    /** Shows the tail of storage/logs/laravel.log in the browser, newest first —
     *  for a host with no SSH and no confirmed file-manager access to logs. */
    public function viewLogs(string $key)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        // The default 'single' file is laravel.log, but a 'daily' log channel
        // (common on shared hosting) writes dated files instead — check both
        // and use whichever log file was modified most recently.
        $candidates = array_merge(
            [storage_path('logs/laravel.log')],
            glob(storage_path('logs/laravel-*.log')) ?: []
        );
        $candidates = array_filter($candidates, 'file_exists');

        if (empty($candidates)) {
            return response('No log file found yet in ' . storage_path('logs'));
        }

        usort($candidates, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $path = $candidates[0];

        $size = filesize($path);
        $tail = $size > 60000
            ? file_get_contents($path, false, null, $size - 60000)
            : file_get_contents($path);

        // Newest entries last in the file — reverse by blank-line-separated
        // blocks so the most recent error is the first thing visible.
        $blocks = preg_split('/\n(?=\[\d{4}-\d{2}-\d{2})/', $tail);
        $blocks = array_reverse($blocks);

        return response(
            '<pre style="font:12px/1.5 monospace;padding:24px;white-space:pre-wrap;word-break:break-word">'
            . e('Showing: ' . basename($path) . "\n\n")
            . e(implode("\n\n", $blocks))
            . '</pre>'
        );
    }

    private function syncPermissions(): string
    {
        $added      = [];
        $allKeywords = [];

        foreach (self::PERMISSION_GROUPS as $attribute => $keywords) {
            if (!Permission::where('attribute', $attribute)->exists()) {
                Permission::create(['attribute' => $attribute, 'keywords' => $keywords]);
                $added[] = $attribute;
            }
            $allKeywords = array_merge($allKeywords, array_values($keywords));
        }

        foreach (Role::all() as $role) {
            $rolePerms = is_array($role->permissions) ? $role->permissions : [];
            if ((int) $role->id === 1 || in_array('news_read', $rolePerms, true)) {
                $role->permissions = array_values(array_unique(array_merge($rolePerms, $allKeywords)));
                $role->save();
            }
        }

        return $added ? ('added ' . implode(', ', $added)) : 'all present';
    }
}
