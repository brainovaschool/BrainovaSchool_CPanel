<?php

namespace App\Repositories\StudentPanel;

use App\Models\StudentInfo\Student;
use App\Models\WebsiteSetup\Program;
use App\Repositories\WebsiteSetup\ProgramClassMarkRepository;

/** A student's own enrolled Programs, and the read-only Course Details +
 *  Progress & Report for whichever one is selected - the single-student
 *  counterpart to the admin/teacher/coordinator Course Details page.
 *  Only Approved lessons are ever shown (ProgramClass::scopeApproved()),
 *  and only this student's own marks - never a classmate's. */
class MyCoursesRepository
{
    private ProgramClassMarkRepository $markRepo;

    public function __construct(ProgramClassMarkRepository $markRepo)
    {
        $this->markRepo = $markRepo;
    }

    public function dashboardForStudent(Student $student, ?int $programId): array
    {
        $programs = $student->programs()->active()->orderBy('program_category_id')->orderBy('sort_order')->get();

        $selectedOption = $programId ? $programs->firstWhere('id', $programId) : null;
        $selectedOption = $selectedOption ?? $programs->first();

        $selectedProgram = $selectedOption
            ? Program::with(['teachers', 'programClasses' => fn ($q) => $q->approved()->with(['materials', 'activities', 'outcomes'])])->find($selectedOption->id)
            : null;

        $progress = $selectedProgram ? $this->markRepo->progressForStudent($selectedProgram, $student) : null;

        return [
            'programs'        => $programs,
            'selectedProgram' => $selectedProgram,
            'progress'        => $progress,
        ];
    }
}
