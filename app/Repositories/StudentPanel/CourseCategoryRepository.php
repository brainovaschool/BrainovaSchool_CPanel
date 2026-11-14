<?php

namespace App\Repositories\StudentPanel;

use App\Models\StudentInfo\Student;
use App\Models\WebsiteSetup\Program;
use App\Models\WebsiteSetup\ProgramCategory;
use App\Models\WebsiteSetup\ProgramClass;
use App\Repositories\WebsiteSetup\ProgramClassMarkRepository;
use App\Traits\ReturnFormatTrait;

/** The student-facing counterpart to the admin/teacher/coordinator
 *  per-category Overview pages (Homeschooling, Academics Boost, Beyond
 *  Academics, Beyond Screens) - same Overview/Course Details/Progress &
 *  Report layout, but scoped to this one student's own enrolled Programs
 *  in that category, with only Approved lessons and only their own
 *  marks/submissions. */
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

    public function dashboardForStudent(Student $student, string $slug, ?int $programId): array
    {
        $categoryName = self::CATEGORIES[$slug] ?? null;
        $category = $categoryName ? ProgramCategory::where('name', $categoryName)->with('upload')->first() : null;

        $programs = $student->programs()->active()
            ->when($category, fn ($q) => $q->where('program_category_id', $category->id))
            ->orderBy('sort_order')
            ->get();

        $selectedOption = $programId ? $programs->firstWhere('id', $programId) : null;
        $selectedOption = $selectedOption ?? $programs->first();

        $selectedProgram = $selectedOption
            ? Program::with(['teachers', 'programClasses' => fn ($q) => $q->approved()->with(['materials', 'activities', 'outcomes'])])->find($selectedOption->id)
            : null;

        $progress = $selectedProgram ? $this->markRepo->progressForStudent($selectedProgram, $student) : null;

        return [
            'category'        => $category,
            'programs'        => $programs,
            'selectedProgram' => $selectedProgram,
            'progress'        => $progress,
        ];
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
