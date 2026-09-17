<?php

namespace App\Repositories\ParentPanel\Homework;

use App\Models\Homework;
use App\Models\HomeworkStudent;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use App\Models\StudentInfo\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\StudentInfo\ParentGuardian;
use App\Models\StudentInfo\SessionClassStudent;

class HomeworkRepository implements HomeworkInterface
{
    use ReturnFormatTrait, CommonHelperTrait;

    private $model;

    public function __construct(Homework $model)
    {
        $this->model = $model;
    }

    /**
     * Initial page load.
     * Mirrors the same parent-lookup pattern as ParentPanel\DashboardRepository
     * which is known to work correctly on this installation.
     *
     * Pattern: ParentGuardian.user_id → Student.parent_guardian_id
     */
    public function indexParent(): array
    {
        // Default safe return — blade always gets the expected keys
        $data = [
            'students' => collect(),
            'student'  => null,
            'homeworks' => collect(),
        ];

        try {
            $parent = ParentGuardian::where('user_id', Auth::user()->id)->first();

            if (!$parent) {
                return $data;
            }

            $data['students'] = Student::where('parent_guardian_id', $parent->id)->get();

            // Restore last-selected child from session
            $studentId = Session::get('student_id');
            if ($studentId) {
                $data['student'] = Student::where('id', $studentId)
                    ->where('parent_guardian_id', $parent->id)
                    ->first();
            }

            // A child was already selected in an earlier request (session
            // carried it over) — load their homework now too, instead of
            // leaving the page showing 0/0/0 until the parent re-clicks the
            // child card to fire search() again.
            if ($data['student']) {
                $data['homeworks'] = $this->loadHomeworkFor($data['student']);
            }

            return $data;

        } catch (\Throwable $th) {
            \Log::error('Parent Homework indexParent Error: ' . $th->getMessage());
            return $data;
        }
    }

    /**
     * Parent selects a child → load that child's homework with submission records.
     * Uses get() (not paginate) so the blade can compute stats across all homework.
     */
    public function search($request): array
    {
        $data = [
            'students' => collect(),
            'student'  => null,
            'homeworks' => collect(),
        ];

        try {
            $parent = ParentGuardian::where('user_id', Auth::user()->id)->first();

            if (!$parent) {
                return $data;
            }

            $studentId = (int) $request->student;
            Session::put('student_id', $studentId);

            $data['students'] = Student::where('parent_guardian_id', $parent->id)->get();
            $data['student']  = Student::where('id', $studentId)
                ->where('parent_guardian_id', $parent->id)
                ->first();

            if (!$data['student']) {
                return $data;
            }

            $data['homeworks'] = $this->loadHomeworkFor($data['student']);
            return $data;

        } catch (\Throwable $th) {
            \Log::error('Parent Homework Search Error: ' . $th->getMessage());
            return $data;
        }
    }

    /** Shared by indexParent() (session-restored child) and search() (freshly
     *  picked child) so both paths load homework the same way. Submission
     *  records are batch-loaded with one whereIn + keyBy, not one query per
     *  homework row. */
    private function loadHomeworkFor(Student $student)
    {
        $classSection = SessionClassStudent::where('session_id', setting('session'))
            ->where('student_id', $student->id)
            ->latest()
            ->first();

        if (!$classSection) {
            return collect();
        }

        $homeworks = $this->model::with(['subject', 'class', 'section', 'upload'])
            ->active()
            ->where('session_id', setting('session'))
            ->where('classes_id', $classSection->classes_id)
            ->where('section_id', $classSection->section_id)
            ->orderByDesc('id')
            ->get();

        $submissions = HomeworkStudent::where('student_id', $student->id)
            ->whereIn('homework_id', $homeworks->pluck('id'))
            ->get()
            ->keyBy('homework_id');

        $homeworks->transform(function ($hw) use ($submissions) {
            $hw->submission_record = $submissions->get($hw->id);
            return $hw;
        });

        return $homeworks;
    }

    // Legacy method kept for compatibility — not used by our new blade
    public function index($request)
    {
        return collect();
    }
}
