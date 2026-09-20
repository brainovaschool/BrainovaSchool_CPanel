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
use App\Models\LearningEngine\LearningEvent;
use App\Models\LearningEngine\StudentSkillMastery;
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
use App\Models\StudentInfo\StudentProgramEnrollment;
use App\Models\WebsiteSetup\Program;
use App\Models\WebsiteSetup\ProgramCategory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Repositories\LearningEngine\LearningEventRepository;
use App\Repositories\LearningEngine\ReflectionJournalRepository;
use App\Repositories\StudentInfo\ParentGuardianRepository;
use App\Repositories\StudentInfo\StudentRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;

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
        'dashboard_features' => ['read' => 'dashboard_features_read', 'update' => 'dashboard_features_update', 'delete' => 'dashboard_features_delete'],
        'avatar_item'      => ['read' => 'avatar_item_read', 'create' => 'avatar_item_create', 'update' => 'avatar_item_update', 'delete' => 'avatar_item_delete'],
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

        // Dashboard feature catalogue (idempotent — never overwrites a row's
        // status, only its label/description, so admin toggles survive re-runs).
        $features = 'skipped';
        try {
            (new \Database\Seeders\WebsiteSetup\DashboardFeatureSeeder())->run();
            $features = 'ok — dashboard feature catalogue synced';
        } catch (\Throwable $e) {
            $features = 'error: ' . $e->getMessage();
        }

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . e($migrate) . "\n\nPermissions: " . e($perms)
            . "\nCatalogue seed: " . e($seed)
            . "\nTestimonials seed: " . e($tm)
            . "\nNotice seed: " . e($notice)
            . "\nDashboard features seed: " . e($features)
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

    /** One-off: seeds Phase 2 demo data for the latest demo student — three
     *  skills in different Mistake Bank recovery stages, plus one mastered
     *  skill with its spaced review deliberately backdated to "overdue" —
     *  so every Phase 2 feature is visible on the dashboard at once instead
     *  of manually grading through each one or waiting a real week for a
     *  review to come due. Safe to re-visit; each skill seeds independently
     *  and skips itself if it's already there. */
    public function seedPhase2Demo(string $key, LearningEventRepository $events)
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
            return response('No subject exists yet — create at least one Subject first.', 422);
        }

        $skillNeedsPractice = Skill::firstOrCreate(
            ['title' => 'Subtraction With Borrowing', 'classes_id' => $classSection->classes_id, 'subject_id' => $subject->id],
            ['slug' => 'demo-subtraction-borrowing-' . $classSection->classes_id, 'description' => 'Subtract two numbers that require regrouping.', 'sort_order' => 4, 'status' => 1]
        );
        $skillFirstRecovery = Skill::firstOrCreate(
            ['title' => 'Place Value', 'classes_id' => $classSection->classes_id, 'subject_id' => $subject->id],
            ['slug' => 'demo-place-value-' . $classSection->classes_id, 'description' => 'Identify the value of a digit by its position.', 'sort_order' => 5, 'status' => 1]
        );
        $skillSecondRecovery = Skill::firstOrCreate(
            ['title' => 'Number Patterns', 'classes_id' => $classSection->classes_id, 'subject_id' => $subject->id],
            ['slug' => 'demo-number-patterns-' . $classSection->classes_id, 'description' => 'Continue a sequence by identifying its rule.', 'sort_order' => 6, 'status' => 1]
        );
        $skillDueForReview = Skill::firstOrCreate(
            ['title' => 'Telling Time', 'classes_id' => $classSection->classes_id, 'subject_id' => $subject->id],
            ['slug' => 'demo-telling-time-' . $classSection->classes_id, 'description' => 'Read an analogue clock to the nearest five minutes.', 'sort_order' => 7, 'status' => 1]
        );
        $skillStruggling = Skill::firstOrCreate(
            ['title' => 'Multiplication Facts', 'classes_id' => $classSection->classes_id, 'subject_id' => $subject->id],
            ['slug' => 'demo-multiplication-facts-' . $classSection->classes_id, 'description' => 'Recall multiplication facts up to 10x10 from memory.', 'sort_order' => 8, 'status' => 1]
        );

        // Each skill seeds independently — re-visiting this page only fills
        // in whatever hasn't been seeded yet, instead of an all-or-nothing
        // guard that would block new skills added here later.
        $seeded = fn ($skillId) => LearningEvent::where('student_id', $student->id)->where('skill_id', $skillId)->exists();

        // Seeded in this order (Second recovery skill first, "needs practice"
        // last) so the most urgent one — a fresh, unrecovered mistake — is
        // also the most RECENT event, making it the one the "Next Best
        // Action" card picks out automatically.
        if (!$seeded($skillSecondRecovery->id)) {
            $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillSecondRecovery->id, ['correct' => false, 'source' => 'demo_seed']);
            $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillSecondRecovery->id, ['correct' => true, 'source' => 'demo_seed']);
            $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillSecondRecovery->id, ['correct' => true, 'source' => 'demo_seed']);
        }

        if (!$seeded($skillFirstRecovery->id)) {
            $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillFirstRecovery->id, ['correct' => false, 'source' => 'demo_seed']);
            $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillFirstRecovery->id, ['correct' => true, 'source' => 'demo_seed']);
        }

        if (!$seeded($skillNeedsPractice->id)) {
            $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillNeedsPractice->id, ['correct' => false, 'source' => 'demo_seed']);
        }

        // Telling Time: 8 correct answers reaches Advanced through the same
        // record() path real grading uses, which schedules its first spaced
        // review 7 days out. Backdating next_review_at is the one thing we
        // can't do through record() (it always schedules from "now") — done
        // directly here so "Refresh Time" has something to show immediately
        // instead of waiting a real week.
        if (!$seeded($skillDueForReview->id)) {
            for ($i = 0; $i < 8; $i++) {
                $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillDueForReview->id, ['correct' => true, 'source' => 'demo_seed']);
            }
            \App\Models\LearningEngine\StudentSkillMastery::where('student_id', $student->id)
                ->where('skill_id', $skillDueForReview->id)
                ->update(['next_review_at' => now()->subDays(3)]);
        }

        // Multiplication Facts: 4 attempts, 1 correct (25% accuracy) — crosses
        // the Silent Struggle Detector's threshold (3+ attempts, under 40%).
        // Seeded last so it's also the most recently practiced, making it the
        // one "Your Next Best Action" picks out.
        if (!$seeded($skillStruggling->id)) {
            $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillStruggling->id, ['correct' => false, 'source' => 'demo_seed']);
            $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillStruggling->id, ['correct' => true, 'source' => 'demo_seed']);
            $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillStruggling->id, ['correct' => false, 'source' => 'demo_seed']);
            $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillStruggling->id, ['correct' => false, 'source' => 'demo_seed']);
        }

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . "Phase 2 demo data ready for: {$student->first_name} {$student->last_name} ({$student->email})\n\n"
            . "Subtraction With Borrowing -> Needs practice   (fresh miss, no recovery yet)\n"
            . "Place Value                -> First recovery    (one correct since the last miss)\n"
            . "Number Patterns             -> Second recovery   (two correct in a row since the last miss)\n"
            . "Telling Time                -> Advanced, review overdue by 3 days (backdated for testing)\n"
            . "Multiplication Facts        -> Struggling (4 attempts, 25% correct)\n\n"
            . "Expected on the dashboard:\n"
            . "  \"Kea noticed...\" (Your Next Best Action, purple card) -> Multiplication Facts\n"
            . "  \"Let's Investigate\" -> Subtraction With Borrowing (Needs practice), Place Value (First recovery), Number Patterns (Second recovery)\n"
            . "  Also check /skill-mastery-report as the demo teacher -> this student now shows 1 under \"Possible Struggle\"\n"
            . "  Also check the demo parent's dashboard -> \"Good News This Week\" should mention Telling Time mastered + correct answers this week\n"
            . "  \"Refresh Time\" -> Telling Time (mastered, review overdue)\n"
            . "</pre>"
        );
    }

    /** One-off: backfills a simple 5 XP per pre-existing correct answer (the
     *  XP column didn't exist when those were logged, so they'd otherwise
     *  show as 0 forever), then seeds two fresh skills mastered cleanly so
     *  Brain Level and the Knowledge Tree have something real to show beyond
     *  Level 1 / Seed. Safe to re-visit — backfill only touches xp=0 rows,
     *  and the fresh skills seed independently like the rest of Phase 2/3. */
    public function seedPhase3Demo(string $key, LearningEventRepository $events)
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

        // Simple backfill: 5 XP per correct answer logged before the XP
        // column existed. Deliberately skips the recovery/mastery bonuses
        // here (replaying exact historical order to know which of those
        // applied isn't worth the complexity for a backfill) — the fresh
        // skills below demonstrate those bonuses properly instead.
        $backfilled = LearningEvent::where('student_id', $student->id)
            ->where('event_type', LearningEventRepository::EVENT_ANSWER_SUBMITTED)
            ->where('xp', 0)
            ->where('payload->correct', true)
            ->update(['xp' => 5]);

        $classSection = SessionClassStudent::where('session_id', setting('session'))
            ->where('student_id', $student->id)
            ->first();
        $subject = Subject::first();

        $freshlySeeded = [];
        if ($classSection && $subject) {
            $seeded = fn ($skillId) => LearningEvent::where('student_id', $student->id)->where('skill_id', $skillId)->exists();

            foreach (['Long Division' => 9, 'Reading Comprehension' => 10] as $title => $sortOrder) {
                $skill = Skill::firstOrCreate(
                    ['title' => $title, 'classes_id' => $classSection->classes_id, 'subject_id' => $subject->id],
                    ['slug' => 'demo-' . \Illuminate\Support\Str::slug($title) . '-' . $classSection->classes_id, 'sort_order' => $sortOrder, 'status' => 1]
                );

                if (!$seeded($skill->id)) {
                    for ($i = 0; $i < 8; $i++) {
                        $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skill->id, ['correct' => true, 'source' => 'demo_seed']);
                    }
                    $freshlySeeded[] = $title;
                }
            }
        }

        // Personal Best compares this week's accuracy to last week's, but
        // everything seeded so far happened today — there's no "last week"
        // to compare against yet. Backdated raw rows purely for that
        // comparison (mastery/XP untouched — these bypass record() on
        // purpose) so the feature has something to show without waiting a
        // real week. 7 correct / 3 wrong = 70% last week, for a real student
        // to beat. Eloquent overwrites created_at on create() regardless of
        // what's passed, so the rows are inserted first, then backdated via
        // a plain query-builder update (which doesn't touch timestamps).
        $personalBestBackfilled = false;
        if ($classSection) {
            $anySkill = Skill::where('classes_id', $classSection->classes_id)->first();
            if ($anySkill && !LearningEvent::where('student_id', $student->id)->where('created_at', '<', now()->startOfWeek())->exists()) {
                $ids = [];
                for ($i = 0; $i < 10; $i++) {
                    $ids[] = LearningEvent::create([
                        'student_id' => $student->id,
                        'skill_id'   => $anySkill->id,
                        'event_type' => LearningEventRepository::EVENT_ANSWER_SUBMITTED,
                        'payload'    => ['correct' => $i < 7, 'source' => 'demo_seed'],
                        'xp'         => $i < 7 ? 5 : 0,
                    ])->id;
                }
                $lastWeek = now()->subWeek()->startOfWeek()->addDays(2);
                LearningEvent::whereIn('id', $ids)->update(['created_at' => $lastWeek, 'updated_at' => $lastWeek]);
                $personalBestBackfilled = true;
            }
        }

        $totalXp = $events->totalXp($student->id);

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . "Phase 3 demo data ready for: {$student->first_name} {$student->last_name} ({$student->email})\n\n"
            . "Backfilled XP on {$backfilled} pre-existing correct answers (5 XP each)\n"
            . (count($freshlySeeded) ? 'Freshly mastered: ' . implode(', ', $freshlySeeded) . " (8/8 correct each — 40 base + 50 mastery bonus = 90 XP apiece)\n" : "Long Division / Reading Comprehension already seeded — skipped\n")
            . "\nTotal XP now: {$totalXp}\n\n"
            . ($personalBestBackfilled ? "Backdated 10 answers to last week (7 correct = 70%) so Personal Best has something to compare against\n" : "Personal Best baseline already present — skipped\n")
            . "\nExpected on the dashboard:\n"
            . "  Brain Level badge (top of hero) and the Knowledge Tree tile (first stat tile)\n"
            . "  should both reflect this XP total — reload and check the numbers moved.\n"
            . "  Badges section should show a Bronze " . ($subject->name ?? 'subject') . " Explorer (4 skills mastered in one subject)\n"
            . "  Personal Best should show Last Week 70% -> This Week (your real accuracy today)\n"
            . "</pre>"
        );
    }

    /** One-off: seeds a demo Quiz (skill-tagged questions, submitted and
     *  graded), a demo Project, and a demo Assignment — all graded with real
     *  marks — for the latest demo student, created by the latest demo
     *  teacher. The quiz submission goes through the exact same skill-event
     *  recording the real student-facing quiz submit uses, so Brain Level,
     *  Badges, Mistake Bank etc. all pick it up like any other source. Safe
     *  to re-visit — each piece checks for its own existing title first. */
    public function seedHomeworkDemo(string $key, LearningEventRepository $events)
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
            return response('No subject exists yet — create at least one Subject first.', 422);
        }

        $teacher   = Staff::where('email', 'like', 'demo.teacher.%')->latest('id')->first();
        $createdBy = $teacher->user_id ?? null;
        $sessionId = setting('session');
        $today     = now()->format('Y-m-d');

        $skillA = Skill::where('title', 'Two-Digit Addition')->where('classes_id', $classSection->classes_id)->first();
        $skillB = Skill::where('title', 'Fractions Basics')->where('classes_id', $classSection->classes_id)->first();

        $report = [];

        // ---- Quiz ----
        $quizId = DB::table('homework')->where('title', 'Demo Math Quiz')->where('classes_id', $classSection->classes_id)->value('id');
        if (!$quizId) {
            $quizId = DB::table('homework')->insertGetId([
                'session_id'      => $sessionId,
                'classes_id'      => $classSection->classes_id,
                'section_id'      => $classSection->section_id,
                'subject_id'      => $subject->id,
                'title'           => 'Demo Math Quiz',
                'topic'           => 'Addition & Fractions',
                'task_type'       => 'quiz',
                'date'            => $today,
                'submission_date' => now()->addDays(3)->format('Y-m-d'),
                'marks'           => 30,
                'description'     => 'A short demo quiz covering two skills, for testing the homework-to-skill integration.',
                'status'          => 1,
                'created_by'      => $createdBy,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $skillAId = optional($skillA)->id;
            $skillBId = optional($skillB)->id;

            $questions = [
                ['question' => 'What is 27 + 15?', 'option_a' => '42', 'option_b' => '32', 'option_c' => '45', 'option_d' => '52', 'correct_answer' => 'A', 'skill_id' => $skillAId],
                ['question' => 'What is 48 + 36?', 'option_a' => '74', 'option_b' => '84', 'option_c' => '94', 'option_d' => '64', 'correct_answer' => 'B', 'skill_id' => $skillAId],
                ['question' => 'What is 1/2 + 1/4?', 'option_a' => '1/6', 'option_b' => '2/6', 'option_c' => '3/4', 'option_d' => '1/2', 'correct_answer' => 'C', 'skill_id' => $skillBId],
                ['question' => 'Which country has the most population?', 'option_a' => 'USA', 'option_b' => 'India', 'option_c' => 'Brazil', 'option_d' => 'Russia', 'correct_answer' => 'B', 'skill_id' => null],
            ];

            $questionIds = [];
            foreach ($questions as $q) {
                $questionIds[] = DB::table('homework_quiz_questions')->insertGetId([
                    'homework_id'    => $quizId,
                    'skill_id'       => $q['skill_id'],
                    'question'       => $q['question'],
                    'option_a'       => $q['option_a'],
                    'option_b'       => $q['option_b'],
                    'option_c'       => $q['option_c'],
                    'option_d'       => $q['option_d'],
                    'correct_answer' => $q['correct_answer'],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            // Simulate the student submitting — 3 of 4 correct — through the
            // exact same steps submitInteractiveQuiz() takes: grade, insert
            // homework_students + homework_quiz_answers, then record skill
            // events only after that "submission" is in.
            $studentAnswers = ['A', 'B', 'C', 'D']; // question 4 wrong on purpose
            $earnedMarks    = 0;
            $marksPerQ      = 30 / count($questions);
            $answerRows     = [];
            $skillEvents    = [];

            foreach ($questions as $i => $q) {
                $isCorrect = strtoupper($studentAnswers[$i]) === strtoupper($q['correct_answer']);
                if ($isCorrect) {
                    $earnedMarks += $marksPerQ;
                }
                $answerRows[] = [
                    'homework_id'     => $quizId,
                    'student_id'      => $student->id,
                    'question_id'     => $questionIds[$i],
                    'selected_answer' => $studentAnswers[$i],
                    'is_correct'      => $isCorrect ? 1 : 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
                if ($q['skill_id']) {
                    $skillEvents[] = ['skill_id' => $q['skill_id'], 'correct' => $isCorrect];
                }
            }

            DB::table('homework_students')->insert([
                'student_id'  => $student->id,
                'homework_id' => $quizId,
                'homework'    => null,
                'marks'       => round($earnedMarks, 2),
                'date'        => $today,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            DB::table('homework_quiz_answers')->insert($answerRows);

            foreach ($skillEvents as $event) {
                $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $event['skill_id'], ['correct' => $event['correct'], 'source' => 'homework_quiz']);
            }

            $report[] = "Demo Math Quiz: 4 questions (2 tagged with skills), submitted 3/4 correct, {$earnedMarks}/30 marks";
        } else {
            $report[] = "Demo Math Quiz already exists — skipped";
        }

        // ---- Project ----
        $projectId = DB::table('homework')->where('title', 'Demo Science Project')->where('classes_id', $classSection->classes_id)->value('id');
        if (!$projectId) {
            $projectId = DB::table('homework')->insertGetId([
                'session_id'      => $sessionId,
                'classes_id'      => $classSection->classes_id,
                'section_id'      => $classSection->section_id,
                'subject_id'      => $subject->id,
                'title'           => 'Demo Science Project',
                'topic'           => 'Water Cycle Model',
                'task_type'       => 'project',
                'date'            => $today,
                'submission_date' => now()->addDays(7)->format('Y-m-d'),
                'marks'           => 50,
                'description'     => 'Build a simple water-cycle model and explain each stage.',
                'status'          => 1,
                'created_by'      => $createdBy,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::table('homework_students')->insert([
                'student_id'  => $student->id,
                'homework_id' => $projectId,
                'homework'    => null,
                'marks'       => 42,
                'date'        => $today,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $report[] = "Demo Science Project: graded 42/50 marks";
        } else {
            $report[] = "Demo Science Project already exists — skipped";
        }

        // ---- Assignment ----
        $assignmentId = DB::table('homework')->where('title', 'Demo Reading Assignment')->where('classes_id', $classSection->classes_id)->value('id');
        if (!$assignmentId) {
            $assignmentId = DB::table('homework')->insertGetId([
                'session_id'      => $sessionId,
                'classes_id'      => $classSection->classes_id,
                'section_id'      => $classSection->section_id,
                'subject_id'      => $subject->id,
                'title'           => 'Demo Reading Assignment',
                'topic'           => 'Chapter 4 Summary',
                'task_type'       => 'assignment',
                'date'            => $today,
                'submission_date' => now()->addDays(2)->format('Y-m-d'),
                'marks'           => 20,
                'description'     => 'Write a one-page summary of chapter 4.',
                'status'          => 1,
                'created_by'      => $createdBy,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::table('homework_students')->insert([
                'student_id'  => $student->id,
                'homework_id' => $assignmentId,
                'homework'    => null,
                'marks'       => 18,
                'date'        => $today,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $report[] = "Demo Reading Assignment: graded 18/20 marks";
        } else {
            $report[] = "Demo Reading Assignment already exists — skipped";
        }

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . "Homework demo data for: {$student->first_name} {$student->last_name} ({$student->email})\n\n"
            . implode("\n", $report) . "\n\n"
            . "Check as the demo TEACHER: Homework & Tasks -> should show 3 new tasks (quiz/project/assignment) with real marks,\n"
            . "  and \"View Questions\" on Demo Math Quiz should show the Skill column already tagged on 2 of 4 questions.\n"
            . "Check as the demo STUDENT: dashboard should reflect the quiz's 2 tagged skills in mastery/XP —\n"
            . "  Two-Digit Addition and Fractions Basics both got one more real attempt each from this quiz.\n"
            . "</pre>"
        );
    }

    /** One-off: prints the exact numbers HomeworkRepository::index() and the
     *  dashboard's marks query use, side by side with what the seeded
     *  homework rows actually have — so a mismatch (class/section/session)
     *  is visible directly instead of guessed at. Read-only, changes nothing. */
    public function inspectHomeworkDemo(string $key)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        $student = Student::where('email', 'like', 'demo.student.%')->latest('id')->first();
        if (!$student) {
            return response('No demo student found.', 422);
        }

        $scs = SessionClassStudent::where('session_id', setting('session'))
            ->where('student_id', $student->id)
            ->first();

        $rows = DB::table('homework')
            ->where('title', 'like', 'Demo %')
            ->select('id', 'title', 'classes_id', 'section_id', 'subject_id', 'session_id', 'status', 'task_type')
            ->get();

        $homeworkStudentCount = DB::table('homework_students')->where('student_id', $student->id)->count();

        // The exact query StudentPanel\Homework\HomeworkRepository::index() runs.
        $visibleCount = \App\Models\Homework::active()
            ->where('classes_id', @$scs->classes_id)
            ->where('section_id', @$scs->section_id)
            ->where('session_id', setting('session'))
            ->count();

        $lines = [];
        $lines[] = "Demo student: {$student->first_name} {$student->last_name} (student_id {$student->id})";
        $lines[] = "Student's session_class_student -> classes_id: " . (@$scs->classes_id ?? 'MISSING') . ", section_id: " . (@$scs->section_id ?? 'MISSING') . ", session used: " . setting('session');
        $lines[] = '';
        $lines[] = 'Seeded "Demo *" homework rows (' . $rows->count() . ' found):';
        foreach ($rows as $r) {
            $lines[] = "  #{$r->id} \"{$r->title}\" [{$r->task_type}] classes_id={$r->classes_id} section_id={$r->section_id} subject_id={$r->subject_id} session_id={$r->session_id} status={$r->status}";
        }
        $lines[] = '';
        $lines[] = "homework_students rows for this student: {$homeworkStudentCount}";
        $lines[] = '';
        $lines[] = "Homework page's exact query (active + matching classes_id/section_id/session_id) would show: {$visibleCount} row(s)";

        return response('<pre style="font:14px/1.5 monospace;padding:24px">' . e(implode("\n", $lines)) . '</pre>');
    }

    /** One-off: seeds two "Teach Kea" events (one fully understood, one a
     *  genuine attempt) for the latest demo student, so the XP/Brain Level
     *  impact is visible immediately without needing a live Gemini call —
     *  the live "Teach Kea" form on /student-panel-ai-help still needs a
     *  real AI Helper API key configured (Website Setup -> AI Helper) to
     *  work when the demo student actually types into it themselves. */
    public function seedTeachKeaDemo(string $key, LearningEventRepository $events)
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

        $skill = Skill::where('classes_id', $classSection->classes_id)->orderBy('sort_order')->first();
        if (!$skill) {
            return response('No skills exist yet for the demo student\'s class — visit /db/seed-phase3-demo/' . self::KEY . ' first.', 422);
        }

        $alreadySeeded = LearningEvent::where('student_id', $student->id)
            ->where('event_type', LearningEventRepository::EVENT_TAUGHT_KEA)
            ->exists();

        if (!$alreadySeeded) {
            $events->record($student->id, LearningEventRepository::EVENT_TAUGHT_KEA, $skill->id, [
                'understood' => true,
                'explanation' => "Demo explanation: {$skill->title} is when you break it down step by step and check your answer at the end.",
                'feedback'    => 'Kea says: "You explained the key steps clearly — nice work!"',
                'source'      => 'demo_seed',
            ]);
            $events->record($student->id, LearningEventRepository::EVENT_TAUGHT_KEA, $skill->id, [
                'understood' => false,
                'explanation' => "Demo explanation: {$skill->title} is just something you memorise.",
                'feedback'    => 'Kea says: "Good try, but you missed the main idea — want to have another go?"',
                'source'      => 'demo_seed',
            ]);
        }

        $apiKeyConfigured = (bool) setting('ai_helper_api_key');
        $totalXp          = $events->totalXp($student->id);

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . "Teach Kea demo data for: {$student->first_name} {$student->last_name} ({$student->email})\n\n"
            . ($alreadySeeded
                ? "Already seeded — skipped (2 Teach Kea events already exist for this student)\n"
                : "Seeded 2 Teach Kea events on skill \"{$skill->title}\": 1 understood (+30 XP), 1 attempt (+10 XP)\n")
            . "\nTotal XP now: {$totalXp}\n\n"
            . "AI Helper API key configured: " . ($apiKeyConfigured ? 'YES' : 'NO — the live Teach Kea form will show an error until one is added in Website Setup -> AI Helper') . "\n\n"
            . "To try it live as the demo student:\n"
            . "  1. Log in as the demo student\n"
            . "  2. Go to AI Study Helper in the student menu (/student-panel-ai-help)\n"
            . "  3. Scroll to \"Teach Kea\", pick a skill, and type an explanation\n"
            . "</pre>"
        );
    }

    /** One-off: backdates one of the demo student's not-yet-mastered skills
     *  so it looks untouched for 6 days, to demonstrate Kea's inactivity
     *  nudge ("You haven't practiced X in N days") without waiting a real
     *  week. Only touches last_practiced_at — attempts/correct counts and
     *  mastery level are untouched, so this can't fake a mastery it didn't
     *  earn. */
    public function seedInactivityNudgeDemo(string $key)
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

        $mastery = StudentSkillMastery::where('student_id', $student->id)
            ->where('mastery_level', '!=', 'advanced')
            ->whereNotNull('last_practiced_at')
            ->with('skill')
            ->first();

        if (!$mastery || !$mastery->skill) {
            return response('The demo student has no not-yet-mastered practiced skill to backdate yet — visit /db/seed-phase2-demo/' . self::KEY . ' or /db/seed-phase3-demo/' . self::KEY . ' first.', 422);
        }

        $mastery->last_practiced_at = now()->subDays(6);
        $mastery->save();

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . "Inactivity nudge demo data for: {$student->first_name} {$student->last_name} ({$student->email})\n\n"
            . "Backdated \"{$mastery->skill->title}\" to look last practiced 6 days ago.\n\n"
            . "Expected: log in as the demo student, go to the dashboard, and tap the Kea avatar —\n"
            . "she should now mention \"{$mastery->skill->title}\" hasn't been practiced in 6 days,\n"
            . "in addition to her usual welcome/Brain Level/next-action lines.\n"
            . "</pre>"
        );
    }

    /** One-off: seeds today's reflection journal entry for the demo student,
     *  so the "Today's Reflection" section on the dashboard shows a filled,
     *  saved state (and its +10 XP) without hand-typing it. Skips silently
     *  if today's entry already exists, matching the repository's own
     *  one-entry-per-day rule — running this twice never double-awards XP. */
    public function seedReflectionDemo(string $key, ReflectionJournalRepository $journal)
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

        $alreadyToday = (bool) $journal->today($student->id);

        $journal->save(
            $student->id,
            'Remembering the steps for long division without mixing up the order.',
            'Writing each step down on paper before doing it in my head helped a lot.'
        );

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . "Reflection journal demo data for: {$student->first_name} {$student->last_name} ({$student->email})\n\n"
            . ($alreadyToday
                ? "Today's entry already existed — updated in place, no extra XP (one entry per day, by design)\n"
                : "Saved today's entry — +10 XP\n")
            . "\nExpected on the dashboard: the \"Today's Reflection\" section (below Today's Goals) should show\n"
            . "both answers filled in, with a \"Saved today\" checkmark next to the Save button.\n"
            . "</pre>"
        );
    }

    /** One-off: the big one — 5 students in ONE class, each with a distinct
     *  learning story, all given the SAME quiz/project/assignment by the
     *  same demo teacher, graded with real (simulated) marks. The point is
     *  contrast: log in as each one and the dashboards should look
     *  genuinely different from each other, driven entirely by real
     *  event-log data — nothing hardcoded per dashboard.
     *
     *  The 5 stories:
     *    Aliza Khan   — the star: masters 3 skills, high Brain Level, badge.
     *    Bilal Ahmed  — steady average: partial accuracy, still developing.
     *    Sara Malik   — struggling: repeated wrong answers on one skill,
     *                   trips the Silent Struggle Detector.
     *    Usman Tariq  — comeback story: mastered 2 skills 10 days ago, then
     *                   went quiet — comeback greeting + inactivity nudge.
     *    Zara Iqbal   — brand new: only turned in the assignment so far,
     *                   quiz/project still pending, almost no learning data.
     *
     *  Fully idempotent per student — safe to re-visit; each student is
     *  skipped once they already have any learning event or homework
     *  submission recorded. */
    public function seedFiveStudentCohort(string $key, StudentRepository $studentRepo, LearningEventRepository $events, UserRepository $users)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        $class = Classes::first();
        if (!$class) {
            return response('No class exists yet — create at least one Class (Academic → Classes) first.', 422);
        }
        $section = Section::first();
        $subject = Subject::first();
        if (!$subject) {
            return response('No subject exists yet — create at least one Subject first.', 422);
        }

        // Reuse an existing demo teacher if one was made earlier (e.g. via
        // /db/create-demo-family), otherwise create one here so this endpoint
        // is fully self-contained — no separate prerequisite steps needed.
        $teacher      = Staff::where('email', 'like', 'demo.teacher.%')->latest('id')->first();
        $teacherNote  = null;
        if (!$teacher) {
            $designation = Designation::first();
            $department  = Department::first();
            if (!$designation || !$department) {
                return response('Create at least one Designation and Department (Staff → Designations / Departments) before this can auto-create a demo teacher.', 422);
            }

            $suffix       = now()->format('YmdHis');
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
                return response('Could not create a demo teacher (code ' . $teacherResult . ' — 2 means the staff subscription limit was reached).', 422);
            }

            $teacher = Staff::where('email', $teacherEmail)->latest('id')->first();

            // Assign this teacher to the cohort's class/section/subject so
            // Skill Mastery Report and the teacher's Homework page both
            // recognise them for this class — the same assignment
            // createDemoFamily() would otherwise have set up.
            $assign               = new SubjectAssign();
            $assign->session_id   = setting('session');
            $assign->classes_id   = $class->id;
            $assign->section_id   = optional($section)->id;
            $assign->status       = 1;
            $assign->save();

            $assignChild                    = new SubjectAssignChildren();
            $assignChild->subject_assign_id = $assign->id;
            $assignChild->subject_id        = $subject->id;
            $assignChild->staff_id          = $teacher->id;
            $assignChild->status            = 1;
            $assignChild->save();

            $teacherNote = "Created a new demo teacher login: {$teacherEmail} / password 123456 (this app's default for new staff accounts).";
        }

        $createdBy = $teacher->user_id;
        $sessionId = setting('session');
        $today     = now()->format('Y-m-d');

        // ---- 1. Students (fixed emails so re-visiting this URL is safe) ----
        $roster = [
            'star'       => ['first' => 'Aliza', 'last' => 'Khan'],
            'average'    => ['first' => 'Bilal', 'last' => 'Ahmed'],
            'struggling' => ['first' => 'Sara',  'last' => 'Malik'],
            'comeback'   => ['first' => 'Usman', 'last' => 'Tariq'],
            'new'        => ['first' => 'Zara',  'last' => 'Iqbal'],
        ];

        $studentsByRole = [];
        $created        = [];
        foreach ($roster as $role => $person) {
            $email = "demo.cohort.{$role}@brainovaschool.com";
            $student = Student::where('email', $email)->first();

            if (!$student) {
                $fake = new \Illuminate\Http\Request();
                $fake->merge([
                    'first_name'        => $person['first'],
                    'last_name'         => $person['last'],
                    'email'             => $email,
                    'mobile'            => '0300000' . random_int(1000, 9999),
                    'admission_no'      => 'COHORT-' . strtoupper($role),
                    'password_type'     => 'custom',
                    'password'          => 'Demo@12345',
                    'date_of_birth'     => '2015-01-01',
                    'admission_date'    => now()->format('Y-m-d'),
                    'status'            => 1,
                    'class'             => $class->id,
                    'section'           => optional($section)->id ?? '',
                    'siblings_discount' => 0,
                ]);

                $result = $studentRepo->store($fake);
                if (!$result['status']) {
                    return response("Could not create student for role \"{$role}\": " . $result['message'], 422);
                }
                $student   = Student::where('email', $email)->latest('id')->first();
                $created[] = "{$person['first']} {$person['last']} ({$role})";
            }

            $studentsByRole[$role] = $student;
        }

        // ---- 2. Skills used across the cohort ----
        $skillA = Skill::firstOrCreate(
            ['title' => 'Two-Digit Addition', 'classes_id' => $class->id, 'subject_id' => $subject->id],
            ['slug' => 'cohort-two-digit-addition-' . $class->id, 'sort_order' => 20, 'status' => 1]
        );
        $skillB = Skill::firstOrCreate(
            ['title' => 'Fractions Basics', 'classes_id' => $class->id, 'subject_id' => $subject->id],
            ['slug' => 'cohort-fractions-basics-' . $class->id, 'sort_order' => 21, 'status' => 1]
        );
        $skillC = Skill::firstOrCreate(
            ['title' => 'Multiplication Tables', 'classes_id' => $class->id, 'subject_id' => $subject->id],
            ['slug' => 'cohort-multiplication-tables-' . $class->id, 'sort_order' => 22, 'status' => 1]
        );

        // ---- 3. Homework — one quiz, one project, one assignment, shared by the whole class ----
        $quizId = DB::table('homework')->where('title', 'Cohort Math Quiz')->where('classes_id', $class->id)->value('id');
        if (!$quizId) {
            $quizId = DB::table('homework')->insertGetId([
                'session_id' => $sessionId, 'classes_id' => $class->id, 'section_id' => optional($section)->id,
                'subject_id' => $subject->id, 'title' => 'Cohort Math Quiz', 'topic' => 'Addition & Fractions',
                'task_type' => 'quiz', 'date' => $today, 'submission_date' => now()->addDays(3)->format('Y-m-d'),
                'marks' => 30, 'description' => 'Class quiz covering two tagged skills.', 'status' => 1,
                'created_by' => $createdBy, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $questions = [
            ['question' => 'What is 27 + 15?', 'option_a' => '42', 'option_b' => '32', 'option_c' => '45', 'option_d' => '52', 'correct_answer' => 'A', 'skill_id' => $skillA->id],
            ['question' => 'What is 48 + 36?', 'option_a' => '74', 'option_b' => '84', 'option_c' => '94', 'option_d' => '64', 'correct_answer' => 'B', 'skill_id' => $skillA->id],
            ['question' => 'What is 1/2 + 1/4?', 'option_a' => '1/6', 'option_b' => '2/6', 'option_c' => '3/4', 'option_d' => '1/2', 'correct_answer' => 'C', 'skill_id' => $skillB->id],
            ['question' => 'Which country has the most population?', 'option_a' => 'USA', 'option_b' => 'India', 'option_c' => 'Brazil', 'option_d' => 'Russia', 'correct_answer' => 'B', 'skill_id' => null],
        ];
        $questionIds = DB::table('homework_quiz_questions')->where('homework_id', $quizId)->orderBy('id')->pluck('id')->all();
        if (count($questionIds) !== count($questions)) {
            $questionIds = [];
            foreach ($questions as $q) {
                $questionIds[] = DB::table('homework_quiz_questions')->insertGetId([
                    'homework_id' => $quizId, 'skill_id' => $q['skill_id'], 'question' => $q['question'],
                    'option_a' => $q['option_a'], 'option_b' => $q['option_b'], 'option_c' => $q['option_c'], 'option_d' => $q['option_d'],
                    'correct_answer' => $q['correct_answer'], 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        $projectId = DB::table('homework')->where('title', 'Cohort Science Project')->where('classes_id', $class->id)->value('id');
        if (!$projectId) {
            $projectId = DB::table('homework')->insertGetId([
                'session_id' => $sessionId, 'classes_id' => $class->id, 'section_id' => optional($section)->id,
                'subject_id' => $subject->id, 'title' => 'Cohort Science Project', 'topic' => 'Water Cycle Model',
                'task_type' => 'project', 'date' => $today, 'submission_date' => now()->addDays(7)->format('Y-m-d'),
                'marks' => 50, 'description' => 'Build a simple water-cycle model and explain each stage.', 'status' => 1,
                'created_by' => $createdBy, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $assignmentId = DB::table('homework')->where('title', 'Cohort Reading Assignment')->where('classes_id', $class->id)->value('id');
        if (!$assignmentId) {
            $assignmentId = DB::table('homework')->insertGetId([
                'session_id' => $sessionId, 'classes_id' => $class->id, 'section_id' => optional($section)->id,
                'subject_id' => $subject->id, 'title' => 'Cohort Reading Assignment', 'topic' => 'Chapter 4 Summary',
                'task_type' => 'assignment', 'date' => $today, 'submission_date' => now()->addDays(2)->format('Y-m-d'),
                'marks' => 20, 'description' => 'Write a one-page summary of chapter 4.', 'status' => 1,
                'created_by' => $createdBy, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // ---- 4. Each student submits + gets graded, per their story ----
        $report = [];

        foreach ($studentsByRole as $role => $student) {
            $alreadySeeded = LearningEvent::where('student_id', $student->id)->exists()
                || DB::table('homework_students')->where('student_id', $student->id)->exists();

            if ($alreadySeeded) {
                $report[] = ucfirst($role) . " ({$student->first_name} {$student->last_name}): already seeded — skipped";
                continue;
            }

            switch ($role) {
                case 'star':
                    $marks = $this->simulateQuizSubmission($student->id, $quizId, $questionIds, $questions, ['A', 'B', 'C', 'B'], $events);
                    $this->practiceSkill($student->id, $skillA->id, 8, 8, $events);
                    $this->practiceSkill($student->id, $skillB->id, 8, 8, $events);
                    $this->practiceSkill($student->id, $skillC->id, 8, 8, $events);
                    DB::table('homework_students')->insert([
                        ['student_id' => $student->id, 'homework_id' => $projectId, 'homework' => null, 'marks' => 48, 'date' => $today, 'created_at' => now(), 'updated_at' => now()],
                        ['student_id' => $student->id, 'homework_id' => $assignmentId, 'homework' => null, 'marks' => 19, 'date' => $today, 'created_at' => now(), 'updated_at' => now()],
                    ]);
                    $report[] = "Aliza Khan (star): quiz {$marks}/30, project 48/50, assignment 19/20 — 3 skills mastered (Bronze badge expected), high Brain Level";
                    break;

                case 'average':
                    $marks = $this->simulateQuizSubmission($student->id, $quizId, $questionIds, $questions, ['A', 'A', 'C', 'A'], $events);
                    $this->practiceSkill($student->id, $skillA->id, 2, 1, $events); // +2 correct, +1 wrong on top of the quiz's 1 correct/1 wrong
                    DB::table('homework_students')->insert([
                        ['student_id' => $student->id, 'homework_id' => $projectId, 'homework' => null, 'marks' => 35, 'date' => $today, 'created_at' => now(), 'updated_at' => now()],
                        ['student_id' => $student->id, 'homework_id' => $assignmentId, 'homework' => null, 'marks' => 14, 'date' => $today, 'created_at' => now(), 'updated_at' => now()],
                    ]);
                    $report[] = "Bilal Ahmed (average): quiz {$marks}/30, project 35/50, assignment 14/20 — skills still developing, no mastery yet";
                    break;

                case 'struggling':
                    $marks = $this->simulateQuizSubmission($student->id, $quizId, $questionIds, $questions, ['B', 'A', 'A', 'A'], $events);
                    $this->practiceSkill($student->id, $skillA->id, 0, 2, $events); // 2 more wrong attempts on top of the quiz's 0/2
                    DB::table('homework_students')->insert([
                        ['student_id' => $student->id, 'homework_id' => $projectId, 'homework' => null, 'marks' => 20, 'date' => $today, 'created_at' => now(), 'updated_at' => now()],
                        ['student_id' => $student->id, 'homework_id' => $assignmentId, 'homework' => null, 'marks' => 8, 'date' => $today, 'created_at' => now(), 'updated_at' => now()],
                    ]);
                    $report[] = "Sara Malik (struggling): quiz {$marks}/30, project 20/50, assignment 8/20 — Two-Digit Addition should trip the Silent Struggle Detector";
                    break;

                case 'comeback':
                    $marks = $this->simulateQuizSubmission($student->id, $quizId, $questionIds, $questions, ['A', 'B', 'C', 'B'], $events);
                    $this->practiceSkill($student->id, $skillA->id, 8, 0, $events);
                    $this->practiceSkill($student->id, $skillB->id, 8, 0, $events);
                    DB::table('homework_students')->insert([
                        ['student_id' => $student->id, 'homework_id' => $projectId, 'homework' => null, 'marks' => 45, 'date' => $today, 'created_at' => now(), 'updated_at' => now()],
                        ['student_id' => $student->id, 'homework_id' => $assignmentId, 'homework' => null, 'marks' => 17, 'date' => $today, 'created_at' => now(), 'updated_at' => now()],
                    ]);

                    // Push everything 10 days into the past — mastered, then went quiet.
                    $backdate = now()->subDays(10);
                    LearningEvent::where('student_id', $student->id)->update(['created_at' => $backdate, 'updated_at' => $backdate]);
                    StudentSkillMastery::where('student_id', $student->id)->update([
                        'last_practiced_at' => $backdate, 'mastered_at' => $backdate, 'next_review_at' => $backdate->copy()->addDays(7),
                    ]);
                    DB::table('homework_students')->where('student_id', $student->id)->update(['created_at' => $backdate, 'updated_at' => $backdate, 'date' => $backdate->format('Y-m-d')]);
                    DB::table('homework_quiz_answers')->where('student_id', $student->id)->update(['created_at' => $backdate, 'updated_at' => $backdate]);

                    $report[] = "Usman Tariq (comeback): quiz {$marks}/30, project 45/50, assignment 17/20 — mastered 2 skills, then everything backdated 10 days (comeback greeting + inactivity nudge expected)";
                    break;

                case 'new':
                    // Deliberately only the assignment — quiz and project left
                    // pending, almost no learning-engine data at all yet.
                    DB::table('homework_students')->insert([
                        'student_id' => $student->id, 'homework_id' => $assignmentId, 'homework' => null, 'marks' => 15, 'date' => $today, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $report[] = 'Zara Iqbal (new): only the assignment turned in (15/20) — quiz and project still pending, Brain Level 1, Knowledge Tree still a Seed';
                    break;
            }
        }

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . "5-student cohort seeded in class: {$class->name}" . ($section ? " ({$section->name})" : '') . "\n\n"
            . ($teacherNote ? $teacherNote . "\n\n" : '')
            . (count($created) ? "Created: " . implode(', ', $created) . "\n\n" : "All 5 students already existed — reused them\n\n")
            . implode("\n", $report) . "\n\n"
            . "All 5 log in with password: Demo@12345\n"
            . "Emails: demo.cohort.star@ / demo.cohort.average@ / demo.cohort.struggling@ / demo.cohort.comeback@ / demo.cohort.new@brainovaschool.com\n\n"
            . "Check as the demo TEACHER: Homework & Tasks -> \"Cohort Math Quiz\" / \"Cohort Science Project\" / \"Cohort Reading Assignment\"\n"
            . "  should each show submissions from 4 of the 5 students (Zara only submitted the assignment), with real, varied marks.\n"
            . "  Skill Mastery Report -> this class should show 5 very different rows (mastery counts + who's flagged as struggling).\n"
            . "Check as each STUDENT: dashboards should look genuinely different — compare Brain Level, Knowledge Tree stage,\n"
            . "  badges, Next Best Action, and (for Usman) the comeback greeting + Kea's inactivity nudge on tap.\n"
            . "</pre>"
        );
    }

    /** Shared by seedHomeworkDemo-style methods: grades one student's quiz
     *  submission against a fixed answer key, inserting homework_students +
     *  homework_quiz_answers exactly as submitInteractiveQuiz() does, then
     *  records a learning event for every skill-tagged question — after the
     *  "submission" rows exist, matching the real controller's post-commit
     *  ordering. Returns the earned marks. */
    private function simulateQuizSubmission(int $studentId, int $quizId, array $questionIds, array $questions, array $studentAnswers, LearningEventRepository $events): float
    {
        $marksPerQ  = 30 / count($questions);
        $earned     = 0;
        $answerRows = [];
        $skillEvents = [];

        foreach ($questions as $i => $q) {
            $isCorrect = strtoupper($studentAnswers[$i]) === strtoupper($q['correct_answer']);
            if ($isCorrect) {
                $earned += $marksPerQ;
            }
            $answerRows[] = [
                'homework_id' => $quizId, 'student_id' => $studentId, 'question_id' => $questionIds[$i],
                'selected_answer' => $studentAnswers[$i], 'is_correct' => $isCorrect ? 1 : 0,
                'created_at' => now(), 'updated_at' => now(),
            ];
            if ($q['skill_id']) {
                $skillEvents[] = ['skill_id' => $q['skill_id'], 'correct' => $isCorrect];
            }
        }

        DB::table('homework_students')->insert([
            'student_id' => $studentId, 'homework_id' => $quizId, 'homework' => null,
            'marks' => round($earned, 2), 'date' => now()->format('Y-m-d'), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('homework_quiz_answers')->insert($answerRows);

        foreach ($skillEvents as $event) {
            $events->record($studentId, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $event['skill_id'], ['correct' => $event['correct'], 'source' => 'homework_quiz']);
        }

        return round($earned, 2);
    }

    /** Records $correct correct-answer events followed by $wrong wrong-answer
     *  events for one skill — extra practice on top of a quiz, e.g. to push
     *  a skill to "advanced" or to demonstrate the Silent Struggle Detector. */
    private function practiceSkill(int $studentId, int $skillId, int $correct, int $wrong, LearningEventRepository $events): void
    {
        for ($i = 0; $i < $correct; $i++) {
            $events->record($studentId, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillId, ['correct' => true, 'source' => 'demo_seed']);
        }
        for ($i = 0; $i < $wrong; $i++) {
            $events->record($studentId, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $skillId, ['correct' => false, 'source' => 'demo_seed']);
        }
    }

    /** One-off, read+reset: lists every demo-prefixed account (students,
     *  teachers, parents/guardians) created across this whole testing setup,
     *  and resets each one's password to a fixed, known value. Several of
     *  these accounts were originally created with a timestamp-based
     *  password shown once in a response that's since scrolled away — this
     *  makes every login recoverable in one place, any time. Safe to
     *  re-run — only the password changes, no other data is touched. */
    /** One-off: builds a full test ecosystem — 2 teachers, 2 parents, 5
     *  students (demo1..demo5), program enrolments spanning Homeschooling +
     *  an Electives coding programme + Tutoring/academic-support, plus
     *  quizzes and homework with varied real scores — so the whole
     *  dashboard (points, Level, Knowledge Tree, mastery, marked work,
     *  achievements, Skill Mastery Report, parent view) has real data to
     *  exercise end to end. Idempotent: safe to re-visit. */
    public function seedFullDemoEcosystem(string $key, StudentRepository $studentRepo, ParentGuardianRepository $parents, UserRepository $users, LearningEventRepository $events)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        $classes = Classes::active()->orderBy('id')->take(2)->get();
        if ($classes->isEmpty()) {
            return response('No class exists yet — create at least one Class (Academic → Classes) first.', 422);
        }
        $section = Section::first();

        $subjects = Subject::active()->orderBy('id')->take(3)->get();
        if ($subjects->isEmpty()) {
            return response('No subject exists yet — create at least one Subject first.', 422);
        }

        $designation = Designation::first();
        $department  = Department::first();
        if (!$designation || !$department) {
            return response('Create at least one Designation and Department (Staff → Designations / Departments) before this can create demo teachers.', 422);
        }

        $sessionId      = setting('session');
        $today          = now()->format('Y-m-d');
        $sharedPassword = 'Demo@12345';
        $report         = [];

        // ---- 1. Teachers (2), one per class where possible ----
        $teacherDefs = [
            ['first' => 'Hina',   'last' => 'Rizvi',  'email' => 'demo.teacher1@brainovaschool.com'],
            ['first' => 'Farooq', 'last' => 'Sheikh', 'email' => 'demo.teacher2@brainovaschool.com'],
        ];
        $teachers = [];
        foreach ($teacherDefs as $i => $t) {
            $staff = Staff::where('email', $t['email'])->first();
            if (!$staff) {
                $fakeTeacher = new \Illuminate\Http\Request();
                $fakeTeacher->merge([
                    'first_name'  => $t['first'],
                    'last_name'   => $t['last'],
                    'email'       => $t['email'],
                    'phone'       => '030001' . str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                    'role'        => 5,
                    'designation' => $designation->id,
                    'department'  => $department->id,
                    'staff_id'    => 'DEMOT-' . ($i + 1),
                    'status'      => 1,
                ]);
                $result = $users->store($fakeTeacher);
                if ($result !== 1) {
                    return response("Could not create teacher {$t['email']} (code {$result} — 2 means the staff subscription limit was reached).", 422);
                }
                $staff    = Staff::where('email', $t['email'])->latest('id')->first();
                $report[] = "Created teacher: {$t['first']} {$t['last']} ({$t['email']})";
            }
            if ($staff->user) {
                $staff->user->password = Hash::make($sharedPassword);
                $staff->user->save();
            }
            $teachers[] = $staff;
        }

        // Assign both teachers to every class, each teaching a different subject.
        foreach ($classes as $ci => $class) {
            foreach ($teachers as $ti => $teacher) {
                $subject = $subjects[($ci + $ti) % $subjects->count()];

                $assign = SubjectAssign::where('session_id', $sessionId)
                    ->where('classes_id', $class->id)
                    ->where('section_id', optional($section)->id)
                    ->first();
                if (!$assign) {
                    $assign               = new SubjectAssign();
                    $assign->session_id   = $sessionId;
                    $assign->classes_id   = $class->id;
                    $assign->section_id   = optional($section)->id;
                    $assign->status       = 1;
                    $assign->save();
                }

                $exists = SubjectAssignChildren::where('subject_assign_id', $assign->id)
                    ->where('subject_id', $subject->id)
                    ->where('staff_id', $teacher->id)
                    ->exists();
                if (!$exists) {
                    $child                    = new SubjectAssignChildren();
                    $child->subject_assign_id = $assign->id;
                    $child->subject_id        = $subject->id;
                    $child->staff_id          = $teacher->id;
                    $child->status            = 1;
                    $child->save();
                }
            }
        }

        // ---- 2. Parents (2) ----
        $parentDefs = [
            ['name' => 'Ayesha Malik', 'email' => 'demo.parent1@brainovaschool.com'],
            ['name' => 'Tariq Nadeem', 'email' => 'demo.parent2@brainovaschool.com'],
        ];
        $parentRows = [];
        foreach ($parentDefs as $i => $p) {
            $pg = ParentGuardian::where('guardian_email', $p['email'])->first();
            if (!$pg) {
                $fakeParent = new \Illuminate\Http\Request();
                $fakeParent->merge([
                    'guardian_name'     => $p['name'],
                    'guardian_email'    => $p['email'],
                    'guardian_mobile'   => '030002' . str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                    'guardian_relation' => 'Father',
                    'password_type'     => 'custom',
                    'password'          => $sharedPassword,
                    'status'            => 1,
                ]);
                $result = $parents->store($fakeParent);
                if (!$result['status']) {
                    return response("Could not create parent {$p['email']}: {$result['message']}", 422);
                }
                $pg       = ParentGuardian::where('guardian_email', $p['email'])->latest('id')->first();
                $report[] = "Created parent: {$p['name']} ({$p['email']})";
            } elseif ($pg->user) {
                $pg->user->password = Hash::make($sharedPassword);
                $pg->user->save();
            }
            $parentRows[] = $pg;
        }

        // ---- 3. Students (5): demo1..demo5, spread across the classes above ----
        $studentDefs = [
            ['first' => 'Demo', 'last' => 'One',   'email' => 'demo1@brainovaschool.com'],
            ['first' => 'Demo', 'last' => 'Two',   'email' => 'demo2@brainovaschool.com'],
            ['first' => 'Demo', 'last' => 'Three', 'email' => 'demo3@brainovaschool.com'],
            ['first' => 'Demo', 'last' => 'Four',  'email' => 'demo4@brainovaschool.com'],
            ['first' => 'Demo', 'last' => 'Five',  'email' => 'demo5@brainovaschool.com'],
        ];

        $students        = [];
        $studentsByClass = [];
        foreach ($studentDefs as $i => $s) {
            $class   = $classes[$i % $classes->count()];
            $student = Student::where('email', $s['email'])->first();

            if (!$student) {
                $fake = new \Illuminate\Http\Request();
                $fake->merge([
                    'first_name'        => $s['first'],
                    'last_name'         => $s['last'],
                    'email'             => $s['email'],
                    'mobile'            => '030010' . str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                    'admission_no'      => 'DEMO-' . ($i + 1),
                    'password_type'     => 'custom',
                    'password'          => $sharedPassword,
                    'date_of_birth'     => '2015-01-01',
                    'admission_date'    => now()->format('Y-m-d'),
                    'status'            => 1,
                    'class'             => $class->id,
                    'section'           => optional($section)->id ?? '',
                    'siblings_discount' => 0,
                ]);
                $result = $studentRepo->store($fake);
                if (!$result['status']) {
                    return response("Could not create student {$s['email']}: {$result['message']}", 422);
                }
                $student  = Student::where('email', $s['email'])->latest('id')->first();
                $report[] = "Created student: {$s['first']} {$s['last']} ({$s['email']}) in {$class->name}";
            } elseif ($student->user) {
                $student->user->password = Hash::make($sharedPassword);
                $student->user->save();
            }

            $student->parent_guardian_id = $parentRows[$i % count($parentRows)]->id;
            $student->save();

            $students[]                   = $student;
            $studentsByClass[$class->id][] = $student;
        }

        // ---- 4. Program enrolments — Homeschooling + a coding elective +
        // a Tutoring/academic-support programme per student, 2-3 total ----
        $categoryIds   = ProgramCategory::pluck('id', 'slug');
        $homeschooling = Program::where('program_category_id', $categoryIds['homeschooling'] ?? 0)->orderBy('sort_order')->get();
        $tutoring      = Program::where('program_category_id', $categoryIds['tutoring'] ?? 0)->get();
        $electives     = Program::where('program_category_id', $categoryIds['electives-enrichment'] ?? 0)->get();
        $clubs         = Program::where('program_category_id', $categoryIds['social-clubs'] ?? 0)->get();

        $coding = $electives->first(fn ($p) => str_contains(strtolower($p->title), 'coding'))
            ?? $electives->first();

        if ($homeschooling->isEmpty() && $tutoring->isEmpty() && $electives->isEmpty()) {
            $report[] = 'NOTE: the Programs catalogue is empty, so no program enrolments were created — run the Program Catalog seeder (or visit the public Programs pages once) first if you want the track chips populated.';
        }

        foreach ($students as $i => $student) {
            $picks = collect();
            if ($homeschooling->isNotEmpty()) {
                $picks->push($homeschooling[$i % $homeschooling->count()]);
            }
            if ($coding) {
                $picks->push($coding);
            }
            if ($tutoring->isNotEmpty()) {
                $picks->push($tutoring[$i % $tutoring->count()]);
            }
            if ($i % 2 === 0 && $clubs->isNotEmpty()) {
                $picks->push($clubs[$i % $clubs->count()]);
            }
            $picks = $picks->filter()->unique('id')->values();

            foreach ($picks as $program) {
                StudentProgramEnrollment::firstOrCreate([
                    'student_id' => $student->id,
                    'program_id' => $program->id,
                ]);
            }

            if ($picks->isNotEmpty()) {
                $report[] = "{$student->first_name} {$student->last_name}: enrolled in " . $picks->pluck('title')->implode(', ');
            }
        }

        // ---- 5. Homework + a skill-tagged quiz per class, varied real scores ----
        $createdBy = $teachers[0]->user_id ?? null;

        foreach ($classes as $ci => $class) {
            $classStudents = collect($studentsByClass[$class->id] ?? [])->values();
            if ($classStudents->isEmpty()) {
                continue;
            }

            $subject = $subjects[$ci % $subjects->count()];
            $skillA  = Skill::where('classes_id', $class->id)->orderBy('id')->skip(0)->first();
            $skillB  = Skill::where('classes_id', $class->id)->orderBy('id')->skip(1)->first();

            // ---- Quiz, 4 skill-tagged questions, every student submits with a different score ----
            $quizTitle = "Demo Cohort Quiz — {$class->name}";
            $quizId    = DB::table('homework')->where('title', $quizTitle)->where('classes_id', $class->id)->value('id');
            if (!$quizId) {
                $quizId = DB::table('homework')->insertGetId([
                    'session_id'      => $sessionId,
                    'classes_id'      => $class->id,
                    'section_id'      => optional($section)->id,
                    'subject_id'      => $subject->id,
                    'title'           => $quizTitle,
                    'topic'           => 'Mixed skills practice',
                    'task_type'       => 'quiz',
                    'date'            => $today,
                    'submission_date' => now()->addDays(5)->format('Y-m-d'),
                    'marks'           => 20,
                    'description'     => 'Seeded demo quiz for cohort testing — dashboard/mastery/points flow.',
                    'status'          => 1,
                    'created_by'      => $createdBy,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                $questions = [
                    ['question' => 'Sample question 1 for ' . $class->name, 'a' => 'Correct answer', 'b' => 'Wrong answer', 'c' => 'Wrong answer', 'd' => 'Wrong answer', 'correct' => 'A', 'skill' => optional($skillA)->id],
                    ['question' => 'Sample question 2 for ' . $class->name, 'a' => 'Wrong answer', 'b' => 'Correct answer', 'c' => 'Wrong answer', 'd' => 'Wrong answer', 'correct' => 'B', 'skill' => optional($skillA)->id],
                    ['question' => 'Sample question 3 for ' . $class->name, 'a' => 'Wrong answer', 'b' => 'Wrong answer', 'c' => 'Correct answer', 'd' => 'Wrong answer', 'correct' => 'C', 'skill' => optional($skillB)->id],
                    ['question' => 'Sample question 4 for ' . $class->name, 'a' => 'Wrong answer', 'b' => 'Wrong answer', 'c' => 'Wrong answer', 'd' => 'Correct answer', 'correct' => 'D', 'skill' => optional($skillB)->id],
                ];

                $questionIds = [];
                foreach ($questions as $q) {
                    $questionIds[] = DB::table('homework_quiz_questions')->insertGetId([
                        'homework_id'    => $quizId,
                        'skill_id'       => $q['skill'],
                        'question'       => $q['question'],
                        'option_a'       => $q['a'],
                        'option_b'       => $q['b'],
                        'option_c'       => $q['c'],
                        'option_d'       => $q['d'],
                        'correct_answer' => $q['correct'],
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }

                $scorePattern = [4, 3, 2, 1, 4];
                foreach ($classStudents as $si => $student) {
                    $correctCount = $scorePattern[$si % count($scorePattern)];
                    $marksPerQ    = 20 / count($questions);
                    $earned       = 0;
                    $answerRows   = [];

                    foreach ($questions as $qi => $q) {
                        $isCorrect = $qi < $correctCount;
                        if ($isCorrect) {
                            $earned += $marksPerQ;
                        }
                        $selected     = $isCorrect ? $q['correct'] : (($q['correct'] === 'A') ? 'B' : 'A');
                        $answerRows[] = [
                            'homework_id'     => $quizId,
                            'student_id'      => $student->id,
                            'question_id'     => $questionIds[$qi],
                            'selected_answer' => $selected,
                            'is_correct'      => $isCorrect ? 1 : 0,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ];
                        if ($q['skill']) {
                            $events->record($student->id, LearningEventRepository::EVENT_ANSWER_SUBMITTED, $q['skill'], ['correct' => $isCorrect, 'source' => 'homework_quiz']);
                        }
                    }

                    DB::table('homework_students')->insert([
                        'student_id'  => $student->id,
                        'homework_id' => $quizId,
                        'homework'    => null,
                        'marks'       => round($earned, 2),
                        'date'        => $today,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                    DB::table('homework_quiz_answers')->insert($answerRows);
                }

                $report[] = "{$quizTitle}: 4 skill-tagged questions, {$classStudents->count()} students submitted with varied scores";
            }

            // ---- A plain graded assignment too, for marks/points variety beyond quizzes ----
            $assignTitle = "Demo Cohort Assignment — {$class->name}";
            $assignId    = DB::table('homework')->where('title', $assignTitle)->where('classes_id', $class->id)->value('id');
            if (!$assignId) {
                $assignId = DB::table('homework')->insertGetId([
                    'session_id'      => $sessionId,
                    'classes_id'      => $class->id,
                    'section_id'      => optional($section)->id,
                    'subject_id'      => $subject->id,
                    'title'           => $assignTitle,
                    'topic'           => 'Cohort writing task',
                    'task_type'       => 'assignment',
                    'date'            => $today,
                    'submission_date' => now()->addDays(3)->format('Y-m-d'),
                    'marks'           => 30,
                    'description'     => 'Seeded demo assignment for cohort testing.',
                    'status'          => 1,
                    'created_by'      => $createdBy,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                $assignScores = [28, 22, 17, 9, 30];
                foreach ($classStudents as $si => $student) {
                    DB::table('homework_students')->insert([
                        'student_id'  => $student->id,
                        'homework_id' => $assignId,
                        'homework'    => null,
                        'marks'       => $assignScores[$si % count($assignScores)],
                        'date'        => $today,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }

                $report[] = "{$assignTitle}: graded (out of 30) for {$classStudents->count()} students";
            }
        }

        // ---- Report ----
        $lines   = [];
        $lines[] = '=== EVERYONE SHARES THIS PASSWORD ===';
        $lines[] = $sharedPassword;
        $lines[] = '';
        $lines[] = '=== STUDENTS ===';
        foreach ($students as $student) {
            $scs       = SessionClassStudent::where('session_id', $sessionId)->where('student_id', $student->id)->first();
            $className = optional(Classes::find(optional($scs)->classes_id))->name ?? '?';
            $lines[]   = "{$student->first_name} {$student->last_name}  |  {$student->email}  |  class {$className}";
        }
        $lines[] = '';
        $lines[] = '=== TEACHERS ===';
        foreach ($teachers as $teacher) {
            $lines[] = "{$teacher->first_name} {$teacher->last_name}  |  {$teacher->email}";
        }
        $lines[] = '';
        $lines[] = '=== PARENTS ===';
        foreach ($parentRows as $pg) {
            $lines[] = "{$pg->guardian_name}  |  {$pg->guardian_email}";
        }
        $lines[] = '';
        $lines[] = '=== WHAT WAS CREATED / UPDATED THIS RUN ===';
        $lines   = array_merge($lines, $report ?: ['Nothing new — everything already existed from a previous run.']);
        $lines[] = '';
        $lines[] = 'Safe to re-visit this URL any time — it only creates what\'s missing and keeps the password in sync.';

        return response('<pre style="font:14px/1.5 monospace;padding:24px">' . e(implode("\n", $lines)) . '</pre>');
    }

    public function listDemoCredentials(string $key)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        $studentPassword = 'Demo@12345';
        $parentPassword  = 'Demo@12345';
        $staffPassword   = '123456';

        $resetPassword = function ($ownerWithUser, string $plain) {
            $user = $ownerWithUser->user;
            if ($user) {
                $user->password = Hash::make($plain);
                $user->save();
            }
        };

        $lines = [];

        $lines[] = '=== STUDENTS (password: ' . $studentPassword . ') ===';
        $students = Student::where('email', 'like', 'demo.%')->with('user')->orderBy('id')->get();
        foreach ($students as $s) {
            $resetPassword($s, $studentPassword);
            $lines[] = "{$s->first_name} {$s->last_name}  |  {$s->email}";
        }
        if ($students->isEmpty()) {
            $lines[] = '(none found)';
        }

        $lines[] = '';
        $lines[] = '=== TEACHERS (password: ' . $staffPassword . ') ===';
        $teachers = Staff::where('email', 'like', 'demo.%')->with('user')->orderBy('id')->get();
        foreach ($teachers as $t) {
            $resetPassword($t, $staffPassword);
            $lines[] = "{$t->first_name} {$t->last_name}  |  {$t->email}";
        }
        if ($teachers->isEmpty()) {
            $lines[] = '(none found)';
        }

        $lines[] = '';
        $lines[] = '=== PARENTS / GUARDIANS (password: ' . $parentPassword . ') ===';
        $parents = ParentGuardian::where('guardian_email', 'like', 'demo.%')->with('user')->orderBy('id')->get();
        foreach ($parents as $p) {
            $resetPassword($p, $parentPassword);
            $lines[] = "{$p->guardian_name}  |  {$p->guardian_email}";
        }
        if ($parents->isEmpty()) {
            $lines[] = '(none found)';
        }

        return response('<pre style="font:14px/1.5 monospace;padding:24px">' . e(implode("\n", $lines)) . '</pre>');
    }

    /** One-off: credits every demo. student account with 5000 spendable
     *  Coins (xp stays 0, so Brain Level/mastery are untouched) so the
     *  avatar shop's buy flow can be tested end to end. */
    public function grantTestCoins(string $key)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        $amount   = 5000;
        $students = Student::where('email', 'like', 'demo.%')->get();

        if ($students->isEmpty()) {
            return response('No demo student accounts found (email must start with "demo.").', 422);
        }

        foreach ($students as $student) {
            LearningEvent::create([
                'student_id' => $student->id,
                'event_type' => 'coin_grant',
                'xp'         => 0,
                'coins'      => $amount,
                'payload'    => ['reason' => 'manual test grant via /db/grant-test-coins'],
            ]);
        }

        $names = $students->map(fn ($s) => "{$s->first_name} {$s->last_name}")->implode(', ');

        return response("Granted {$amount} coins each to: {$names}. Reload the My Avatar page to see the new balance.");
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
