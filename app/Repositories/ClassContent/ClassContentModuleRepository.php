<?php

namespace App\Repositories\ClassContent;

use App\Enums\Settings;
use App\Models\ClassContent\ClassContentModule;
use App\Models\StudentInfo\Student;
use App\Traits\ReturnFormatTrait;

class ClassContentModuleRepository
{
    use ReturnFormatTrait;

    /** Approved content for every class the student is enrolled in this
     *  session (their main class plus any short courses) — same
     *  multi-class visibility rule as Homework/Attendance. */
    public function forStudent(Student $student)
    {
        $pairs = $student->enrolledClassSectionPairs();
        if ($pairs->isEmpty()) {
            return collect();
        }

        $query = ClassContentModule::approved()
            ->with(['class', 'section', 'subject'])
            ->withCount('lessons')
            ->where('session_id', setting('session'))
            ->orderByDesc('id');

        return matchAnyClassSectionPair($query, $pairs)->get();
    }

    /** A single approved module, but only if it's actually for one of this
     *  student's enrolled classes — a student can't view-by-ID their way
     *  into another class's content. */
    public function showApprovedForStudent(int $id, Student $student): ?ClassContentModule
    {
        $pairs = $student->enrolledClassSectionPairs();
        if ($pairs->isEmpty()) {
            return null;
        }

        $belongs = matchAnyClassSectionPair(ClassContentModule::approved()->where('id', $id), $pairs)->exists();

        return $belongs ? $this->show($id) : null;
    }

    public function forTeacher(int $staffId)
    {
        return ClassContentModule::with(['class', 'section', 'subject', 'creator'])
            ->withCount('lessons')
            ->where('created_by', $staffId)
            ->orderByDesc('id')
            ->paginate(Settings::PAGINATE);
    }

    /** Coordinator's queue — defaults to what actually needs their
     *  attention (submitted / sent back for changes), but can show
     *  everything so they can also browse what's already moved on. */
    public function forCoordinator(?string $status = null)
    {
        return ClassContentModule::with(['class', 'section', 'subject', 'creator'])
            ->when($status, fn ($q) => $q->where('review_status', $status))
            ->when(!$status, fn ($q) => $q->whereIn('review_status', [ClassContentModule::SUBMITTED, ClassContentModule::CHANGES_REQUESTED]))
            ->orderByDesc('id')
            ->paginate(Settings::PAGINATE);
    }

    /** Admin's approval queue — same idea, defaults to what's actually
     *  ready for a decision. */
    public function forAdmin(?string $status = null)
    {
        return ClassContentModule::with(['class', 'section', 'subject', 'creator', 'coordinator'])
            ->when($status, fn ($q) => $q->where('review_status', $status))
            ->when(!$status, fn ($q) => $q->where('review_status', ClassContentModule::COORDINATOR_REVIEWED))
            ->orderByDesc('id')
            ->paginate(Settings::PAGINATE);
    }

    public function show(int $id): ?ClassContentModule
    {
        return ClassContentModule::with(['class', 'section', 'subject', 'creator', 'coordinator', 'approver', 'lessons.materials', 'lessons.activities', 'lessons.outcomes'])
            ->find($id);
    }

    /** $staffId is nullable because a Super Admin account typically has no
     *  linked Staff row at all (the seeded Super Admin never gets one) —
     *  created_by is a nullable FK, so that must stay NULL rather than be
     *  coerced to 0, which would violate the FK constraint outright. */
    public function store($request, ?int $staffId): array
    {
        try {
            $row                = new ClassContentModule();
            $row->session_id    = setting('session');
            $row->classes_id    = $request->classes_id;
            $row->section_id    = $request->section_id ?: null;
            $row->subject_id    = $request->subject_id;
            $row->title         = $request->title;
            $row->description   = $request->description;
            $row->created_by    = $staffId;
            $row->review_status = ClassContentModule::DRAFT;
            $row->sort_order    = (int) $request->sort_order;
            $row->save();

            return $this->responseWithSuccess(___('alert.created_successfully'), ['id' => $row->id]);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    /** Only touches the content fields — a teacher editing their module
     *  (e.g. after "changes requested") never silently changes its own
     *  review state; submit() is the only thing that does that. */
    public function update($request, int $id): array
    {
        try {
            $row              = ClassContentModule::findOrFail($id);
            $row->classes_id  = $request->classes_id;
            $row->section_id  = $request->section_id ?: null;
            $row->subject_id  = $request->subject_id;
            $row->title       = $request->title;
            $row->description = $request->description;
            $row->sort_order  = (int) $request->sort_order;
            $row->save();

            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy(int $id): array
    {
        try {
            ClassContentModule::findOrFail($id)->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    /** Teacher -> Coordinator. Only from draft or changes_requested, so a
     *  module already in someone's review queue can't be resubmitted out
     *  from under them. */
    public function submit(int $id): array
    {
        $row = ClassContentModule::find($id);
        if (!$row || !in_array($row->review_status, [ClassContentModule::DRAFT, ClassContentModule::CHANGES_REQUESTED], true)) {
            return $this->responseWithError('This can only be submitted from Draft or Changes Requested.', []);
        }

        // Server-side mirror of the lessons.index "disabled until a lesson
        // exists" button — the module list's own submit action had no such
        // check, so an empty module could reach the Coordinator's queue by
        // that path alone.
        if (!$row->lessons()->exists()) {
            return $this->responseWithError('Add at least one lesson before submitting this module for review.', []);
        }

        $row->review_status = ClassContentModule::SUBMITTED;
        $row->save();

        return $this->responseWithSuccess('Submitted for review.', []);
    }

    /** Coordinator's decision — either send it on to the admin, or kick
     *  it back to the teacher with feedback. $coordinatorStaffId is
     *  nullable for the same reason as store()'s $staffId — avoids
     *  writing 0 into the nullable coordinator_id FK. */
    public function coordinatorDecision($request, int $id, ?int $coordinatorStaffId): array
    {
        $row = ClassContentModule::find($id);
        if (!$row) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $row->coordinator_feedback   = $request->input('feedback');
        $row->coordinator_id         = $coordinatorStaffId;
        $row->coordinator_reviewed_at = now();
        $row->review_status = $request->input('decision') === 'request_changes'
            ? ClassContentModule::CHANGES_REQUESTED
            : ClassContentModule::COORDINATOR_REVIEWED;
        $row->save();

        return $this->responseWithSuccess('Saved.', []);
    }

    /** Admin's decision — approve and publish, send back to the
     *  coordinator for another look, or kick it straight back to the
     *  teacher. $adminStaffId is nullable for the same reason as store()'s
     *  $staffId — the seeded Super Admin has no Staff row, so approved_by
     *  must be able to stay NULL rather than be coerced to 0. */
    public function adminDecision($request, int $id, ?int $adminStaffId): array
    {
        $row = ClassContentModule::find($id);
        if (!$row) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $decision = $request->input('decision');

        if ($decision === 'approve') {
            $row->review_status = ClassContentModule::APPROVED;
            $row->approved_by   = $adminStaffId;
            $row->approved_at   = now();
        } elseif ($decision === 'back_to_coordinator') {
            $row->review_status           = ClassContentModule::SUBMITTED;
            $row->coordinator_reviewed_at = null;
            if ($request->filled('feedback')) {
                $row->coordinator_feedback = $request->input('feedback');
            }
        } else {
            $row->review_status = ClassContentModule::CHANGES_REQUESTED;
            if ($request->filled('feedback')) {
                $row->coordinator_feedback = $request->input('feedback');
            }
        }

        $row->save();

        return $this->responseWithSuccess('Saved.', []);
    }
}
