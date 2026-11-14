<?php

namespace App\Repositories\Homework;

interface HomeworkInterface
{
    public function all();

    public function getPaginateAll();

    public function search($request);

    public function store($request);

    public function show($id);

    public function update($request, $id);

    public function destroy($id);

    /** Standard homework evaluation: teacher manually assigns marks + optional feedback. */
    public function evaluationSubmit($request);

    /** Answer-sheet marking: teacher grades uploaded file submissions. */
    public function markSubmit($request);

    /** Aggregated statistics for the teacher dashboard charts. */
    public function getStats(): array;

    /**
     * Returns ALL homework across ALL teachers, classes and sessions.
     * Used exclusively by the admin overview page.
     * Bypasses the teacherSubjects() scope that limits teacher access.
     */
    public function getAllForAdmin();

    // ── Tier 2 additions ──────────────────────────────────────────────────────

    /**
     * Per-question accuracy analytics for a quiz homework.
     * Returns question rows with: total_attempts, correct_count, wrong_option_counts.
     */
    public function getQuizAnalytics(int $homeworkId): array;

    /**
     * Export all student results for a homework as a downloadable CSV response.
     * Handles both quiz (auto-graded) and standard (teacher-graded) task types.
     */
    public function exportResultsCsv(int $homeworkId);


    public function getGlobalStats(): array;

public function getFilteredHomeworkReport(array $filters): array;

public function getSectionsByClass(int $classId): array;

public function getSubjectsByClassSection(int $classId, int $sectionId): array;

public function getLessonsByClassSectionSubject(int $classId, int $sectionId, int $subjectId): array;
}
