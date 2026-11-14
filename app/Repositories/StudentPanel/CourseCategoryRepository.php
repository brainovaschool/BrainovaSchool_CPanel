<?php

namespace App\Repositories\StudentPanel;

use App\Models\StudentInfo\Student;
use App\Models\WebsiteSetup\Program;
use App\Models\WebsiteSetup\ProgramCategory;
use App\Models\WebsiteSetup\ProgramClass;
use App\Repositories\WebsiteSetup\ProgramClassMarkRepository;
use App\Traits\ReturnFormatTrait;

/** The student-facing counterpart to the admin/teacher/coordinator
 *  per-category pages (Homeschooling, Academics Boost, Beyond Academics,
 *  Beyond Screens) - same Overview / Course Details / Progress & Report
 *  split into 3 separate pages, but scoped to this one student's own
 *  enrolled Programs in that category, with only Approved lessons and
 *  only their own marks/submissions. */
class CourseCategoryRepository
{
    use ReturnFormatTrait;

    /** Slug (used in the URL and sidebar) -> real ProgramCategory name.
     *  "beyond-academics" maps to the DB's actual (misspelled) category
     *  name - this is not a typo to fix, the admin side already matches
     *  it exactly the same way. */
    public const CATEGORIES = [
        'homeschooling'    => 'Homeschooling',
        'academics-boost'  => 'Academics Boost',
        'beyond-academics' => 'Beyond Acedamics',
        'beyond-screens'   => 'Beyond Screens',
    ];

    private ProgramClassMarkRepository $markRepo;

    public function __construct(ProgramClassMarkRepository $markRepo)
    {
        $this->markRepo = $markRepo;
    }

    private function category(string $slug): ?ProgramCategory
    {
        $categoryName = self::CATEGORIES[$slug] ?? null;
        return $categoryName ? ProgramCategory::where('name', $categoryName)->with('upload')->first() : null;
    }

    /** This student's own enrolled, active Programs in this category -
     *  what both Course Details and Progress & Report pick a course
     *  from. Deliberately NOT what the Overview page's Featured Courses
     *  shows (that's every active program in the category, not just
     *  theirs). */
    private function enrolledPrograms(Student $student, string $slug, ?ProgramCategory $category)
    {
        return $student->programs()->active()
            ->when($category, fn ($q) => $q->where('program_category_id', $category->id))
            ->orderBy('sort_order')
            ->get();
    }

    public function overviewData(string $slug): array
    {
        $category = $this->category($slug);

        // Featured Courses: every active program in this category, same as
        // the admin/teacher page's own Featured Courses - a catalog to
        // browse, not scoped to what this student happens to be enrolled
        // in (that scoping only applies to Course Details/Progress &
        // Report, where real lesson content and grades live).
        $featured = Program::active()
            ->when($category, fn ($q) => $q->where('program_category_id', $category->id))
            ->orderBy('sort_order')
            ->with('upload')
            ->take(8)
            ->get();

        return ['category' => $category, 'featured' => $featured];
    }

    public function courseDetailsData(Student $student, string $slug, ?int $programId): array
    {
        $programs = $this->enrolledPrograms($student, $slug, $this->category($slug));

        $selectedOption = $programId ? $programs->firstWhere('id', $programId) : null;
        $selectedOption = $selectedOption ?? $programs->first();

        $selectedProgram = $selectedOption
            ? Program::with(['teachers', 'programClasses' => fn ($q) => $q->approved()->with(['materials', 'activities', 'outcomes'])])->find($selectedOption->id)
            : null;

        return ['programs' => $programs, 'selectedProgram' => $selectedProgram];
    }

    public function progressReportData(Student $student, string $slug, ?int $programId): array
    {
        $programs = $this->enrolledPrograms($student, $slug, $this->category($slug));

        $selectedOption = $programId ? $programs->firstWhere('id', $programId) : null;
        $selectedOption = $selectedOption ?? $programs->first();

        $selectedProgram = $selectedOption
            ? Program::with(['programClasses.marks'])->find($selectedOption->id)
            : null;

        $progress = $selectedProgram ? $this->markRepo->progressForStudent($selectedProgram, $student) : null;

        return ['programs' => $programs, 'selectedProgram' => $selectedProgram, 'progress' => $progress];
    }

    /** A student submitting their own work for one gradable item - re-
     *  derives both "is this lesson actually live" and "is this student
     *  actually enrolled in it" from the database rather than trusting
     *  the posted ids alone. */
    public function submit(Student $student, $request): array
    {
        $class = ProgramClass::with('program')->find($request->input('program_class_id'));
        if (!$class || !$class->isApproved()) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $enrolled = $student->programs()->active()->where('programs.id', $class->program_id)->exists();
        if (!$enrolled) {
            return $this->responseWithError("You're not enrolled in this course.", []);
        }

        return $this->markRepo->submitForStudent($class, $student, (string) $request->input('assessment_type'), $request);
    }
}
