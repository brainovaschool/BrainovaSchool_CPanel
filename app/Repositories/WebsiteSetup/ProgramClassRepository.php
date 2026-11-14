<?php

namespace App\Repositories\WebsiteSetup;

use App\Models\Staff\Staff;
use App\Models\WebsiteSetup\ProgramClass;
use App\Models\WebsiteSetup\ProgramClassActivity;
use App\Models\WebsiteSetup\ProgramClassMaterial;
use App\Models\WebsiteSetup\ProgramClassOutcome;
use App\Services\B2Storage;
use App\Traits\ReturnFormatTrait;

/** Mirrors ClassContentLessonRepository + the review parts of
 *  ClassContentModuleRepository, flattened onto one model and simplified
 *  to a single reviewer (Coordinator's decision is final - no separate
 *  Admin approval tier) since this is meant to replace Class Content for
 *  program-based courses, not add a second heavy workflow next to it. */
class ProgramClassRepository
{
    use ReturnFormatTrait;

    private B2Storage $storage;

    public function __construct(B2Storage $storage)
    {
        $this->storage = $storage;
    }

    public function forProgram(int $programId)
    {
        return ProgramClass::where('program_id', $programId)
            ->withCount(['materials', 'activities', 'outcomes'])
            ->orderBy('sort_order')
            ->get();
    }

    public function show(int $id): ?ProgramClass
    {
        return ProgramClass::with(['materials', 'activities', 'outcomes', 'creator', 'coordinator', 'program'])->find($id);
    }

    public function store($request, int $programId, ?int $staffId): array
    {
        try {
            $row             = new ProgramClass();
            $row->program_id = $programId;
            $row->title       = $request->title;
            $row->description = $request->description;
            $row->video_url   = trim((string) $request->video_url) ?: null;
            $row->class_date  = $request->class_date ?: null;
            $row->sort_order  = (int) $request->sort_order;
            $row->created_by  = $staffId;
            $this->fillProjectAndQuiz($row, $request);
            $row->save();

            $warnings = $this->syncChildren($row, $request);

            $message = ___('alert.created_successfully') . ($warnings ? ' — but: ' . implode(' ', $warnings) : '');
            return $this->responseWithSuccess($message, ['id' => $row->id]);
        } catch (\Throwable $th) {
            \Log::error('ProgramClass Store Error: ' . $th->getMessage());
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function update($request, int $id): array
    {
        try {
            $row              = ProgramClass::findOrFail($id);
            $row->title       = $request->title;
            $row->description = $request->description;
            $row->video_url   = trim((string) $request->video_url) ?: null;
            $row->class_date  = $request->class_date ?: null;
            $row->sort_order  = (int) $request->sort_order;
            $this->fillProjectAndQuiz($row, $request);
            $row->save();

            $warnings = $this->syncChildren($row, $request);

            $message = ___('alert.updated_successfully') . ($warnings ? ' — but: ' . implode(' ', $warnings) : '');
            return $this->responseWithSuccess($message, []);
        } catch (\Throwable $th) {
            \Log::error('ProgramClass Update Error: ' . $th->getMessage());
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy(int $id): array
    {
        try {
            ProgramClass::findOrFail($id)->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function submit(int $id): array
    {
        $row = ProgramClass::find($id);
        if (!$row || !in_array($row->review_status, [ProgramClass::DRAFT, ProgramClass::CHANGES_REQUESTED], true)) {
            return $this->responseWithError('This can only be submitted from Draft or Changes Requested.', []);
        }

        $row->review_status = ProgramClass::SUBMITTED;
        $row->submitted_at  = now();
        $row->save();

        if ($row->creator) {
            $row->creator->coordinators->each(function (Staff $coordinator) use ($row) {
                if ($coordinator->user_id) {
                    \App\Services\Portal\PortalNotifier::send(
                        $coordinator->user_id,
                        'Lesson submitted for review',
                        $row->title,
                        $this->courseDetailsUrl($row->program)
                    );
                }
            });
        }

        // Admin can act on a freshly-Submitted lesson directly too (not
        // just once a coordinator forwards it), so they should hear about
        // it immediately as well - not only after that second step.
        $this->notifyAdmins('Lesson submitted for review', $row);

        return $this->responseWithSuccess('Submitted for review.', []);
    }

    private function coordinateeIds(?int $coordinatorStaffId): ?array
    {
        if ($coordinatorStaffId === null) {
            return null;
        }
        return Staff::find($coordinatorStaffId)?->coordinatees->pluck('id')->toArray() ?? [];
    }

    public function forCoordinator(?string $status = null, ?int $coordinatorStaffId = null)
    {
        $coordinateeIds = $this->coordinateeIds($coordinatorStaffId);

        return ProgramClass::with(['program', 'creator'])
            ->when($coordinateeIds !== null, fn ($q) => $q->whereIn('created_by', $coordinateeIds))
            ->when($status, fn ($q) => $q->where('review_status', $status))
            ->when(!$status, fn ($q) => $q->whereIn('review_status', [ProgramClass::SUBMITTED, ProgramClass::CHANGES_REQUESTED]))
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function outsideCoordinatorScope(ProgramClass $row, ?int $coordinatorStaffId): bool
    {
        $coordinateeIds = $this->coordinateeIds($coordinatorStaffId);
        return $coordinateeIds !== null && !in_array((int) $row->created_by, $coordinateeIds, true);
    }

    public function statusCounts(?int $createdBy = null, ?int $coordinatorStaffId = null): array
    {
        $coordinateeIds = $this->coordinateeIds($coordinatorStaffId);

        $rows = ProgramClass::selectRaw('review_status, count(*) as total')
            ->when($createdBy, fn ($q) => $q->where('created_by', $createdBy))
            ->when($coordinateeIds !== null, fn ($q) => $q->whereIn('created_by', $coordinateeIds))
            ->groupBy('review_status')
            ->pluck('total', 'review_status');

        return [
            ProgramClass::DRAFT             => (int) ($rows[ProgramClass::DRAFT] ?? 0),
            ProgramClass::SUBMITTED         => (int) ($rows[ProgramClass::SUBMITTED] ?? 0),
            ProgramClass::CHANGES_REQUESTED => (int) ($rows[ProgramClass::CHANGES_REQUESTED] ?? 0),
            ProgramClass::APPROVED          => (int) ($rows[ProgramClass::APPROVED] ?? 0),
        ];
    }

    /** Same two-tier flow as Class Content: Coordinator doesn't approve
     *  outright, they send it on to Admin (or send it back to the
     *  teacher). Admin's decision below is what actually makes it live. */
    public function coordinatorDecision($request, int $id, ?int $coordinatorStaffId): array
    {
        $row = ProgramClass::find($id);
        if (!$row) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $decisionIsChanges = $request->input('decision') === 'request_changes';

        $row->coordinator_feedback    = $request->input('feedback');
        $row->coordinator_id          = $coordinatorStaffId;
        $row->coordinator_reviewed_at = now();
        $row->review_status = $decisionIsChanges
            ? ProgramClass::CHANGES_REQUESTED
            : ProgramClass::COORDINATOR_REVIEWED;
        $row->save();

        if ($decisionIsChanges) {
            if ($row->creator && $row->creator->user_id) {
                \App\Services\Portal\PortalNotifier::send(
                    $row->creator->user_id,
                    'Changes requested',
                    $row->title,
                    $this->courseDetailsUrl($row->program)
                );
            }
        } else {
            $this->notifyAdmins('Lesson ready for your approval', $row);
        }

        return $this->responseWithSuccess('Saved.', []);
    }

    /** Admin's decision is final: Approve makes it live on Course Details,
     *  Request changes sends it straight back to the teacher. Admin can
     *  act on a lesson that's merely Submitted too (not just
     *  Coordinator-reviewed) - same "sees/edits everything" bypass used
     *  everywhere else in this app, and it keeps a lesson from getting
     *  stuck forever on a program with no coordinator assigned yet. */
    public function adminDecision($request, int $id, ?int $adminStaffId): array
    {
        $row = ProgramClass::find($id);
        if (!$row) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $decisionIsChanges = $request->input('decision') === 'request_changes';

        $row->admin_feedback = $request->input('feedback');

        if ($decisionIsChanges) {
            $row->review_status = ProgramClass::CHANGES_REQUESTED;
        } else {
            $row->review_status = ProgramClass::APPROVED;
            $row->approved_by   = $adminStaffId;
            $row->approved_at   = now();
        }
        $row->save();

        if ($row->creator && $row->creator->user_id) {
            \App\Services\Portal\PortalNotifier::send(
                $row->creator->user_id,
                $decisionIsChanges ? 'Changes requested' : 'Lesson approved',
                $row->title,
                $this->courseDetailsUrl($row->program)
            );
        }

        return $this->responseWithSuccess('Saved.', []);
    }

    /** No single "the admin" user to notify - send to every User whose
     *  role_id is 1 (Super Admin), same bypass role used everywhere else
     *  in this app. */
    private function notifyAdmins(string $title, ProgramClass $row): void
    {
        \App\Models\User::where('role_id', 1)->get()->each(function ($admin) use ($title, $row) {
            \App\Services\Portal\PortalNotifier::send(
                $admin->id,
                $title,
                $row->title,
                $this->courseDetailsUrl($row->program)
            );
        });
    }

    /** The Teacher this notification goes to doesn't have program_update
     *  (Website Setup is admin-only), so the link has to be their
     *  category's own Course Details page, not program.edit. */
    /** Public so both ProgramClassController (teacher redirects) and
     *  ProgramClassCoordinatorController (coordinator redirects) can reuse
     *  this same Program -> category -> route mapping, instead of each
     *  duplicating it. */
    public function courseDetailsUrl(?\App\Models\WebsiteSetup\Program $program): string
    {
        if (!$program) {
            return route('dashboard');
        }

        $routeName = match ($program->category?->name) {
            'Homeschooling'    => 'homeschooling.course-details',
            'Academics Boost'  => 'core-academics.course-details',
            'Beyond Acedamics' => 'beyond-academics.course-details',
            'Beyond Screens'   => 'beyond-screens.course-details',
            default            => 'dashboard',
        };

        return route($routeName) . '?program=' . $program->id;
    }

    private function fillProjectAndQuiz(ProgramClass $row, $request): void
    {
        $projectTitle = trim((string) $request->input('project_title'));
        $row->project_title       = $projectTitle ?: null;
        $row->project_description = $projectTitle ? $request->input('project_description') : null;
        $row->project_link_url    = $projectTitle ? (trim((string) $request->input('project_link_url')) ?: null) : null;
        $row->project_max_marks   = $projectTitle ? ($request->input('project_max_marks') ?: null) : null;

        $quizTitle = trim((string) $request->input('quiz_title'));
        $row->quiz_title       = $quizTitle ?: null;
        $row->quiz_description = $quizTitle ? $request->input('quiz_description') : null;
        $row->quiz_link_url    = $quizTitle ? (trim((string) $request->input('quiz_link_url')) ?: null) : null;
        $row->quiz_max_marks   = $quizTitle ? ($request->input('quiz_max_marks') ?: null) : null;

        $worksheetTitle = trim((string) $request->input('worksheet_title'));
        $row->worksheet_title       = $worksheetTitle ?: null;
        $row->worksheet_description = $worksheetTitle ? $request->input('worksheet_description') : null;
        $row->worksheet_link_url    = $worksheetTitle ? (trim((string) $request->input('worksheet_link_url')) ?: null) : null;
        $row->worksheet_max_marks   = $worksheetTitle ? ($request->input('worksheet_max_marks') ?: null) : null;

        $assignmentTitle = trim((string) $request->input('assignment_title'));
        $row->assignment_title       = $assignmentTitle ?: null;
        $row->assignment_description = $assignmentTitle ? $request->input('assignment_description') : null;
        $row->assignment_link_url    = $assignmentTitle ? (trim((string) $request->input('assignment_link_url')) ?: null) : null;
        $row->assignment_max_marks   = $assignmentTitle ? ($request->input('assignment_max_marks') ?: null) : null;
    }

    private function syncChildren(ProgramClass $class, $request): array
    {
        $warnings = [];

        ProgramClassMaterial::where('program_class_id', $class->id)->delete();
        foreach ((array) $request->input('materials.label', []) as $i => $label) {
            $label = trim((string) $label);

            $storageKey = $request->input("materials.existing_storage_key.{$i}") ?: null;
            $originalFilename = $request->input("materials.existing_original_filename.{$i}") ?: null;
            $url = trim((string) $request->input("materials.url.{$i}"));

            $file = $request->file("materials.file.{$i}");
            if ($file && !$file->isValid()) {
                $warnings[] = "\"" . ($label ?: 'Untitled material') . "\": the file didn't upload properly (code {$file->getError()}) - try again.";
            } elseif ($file && $file->isValid()) {
                $uploaded = $this->storage->upload($file, 'program-class-materials');
                if ($uploaded['ok']) {
                    $storageKey = $uploaded['storage_key'];
                    $originalFilename = $uploaded['original_filename'];
                    $url = '';
                } else {
                    $warnings[] = "\"" . ($label ?: 'Untitled material') . "\": " . $uploaded['message'];
                }
            }

            if ($label === '' && !$storageKey && $url === '') {
                continue;
            }

            if ($label === '') {
                $label = $originalFilename ?: 'Link';
            }

            ProgramClassMaterial::create([
                'program_class_id' => $class->id,
                'label'             => $label,
                'url'               => $storageKey ? null : $url,
                'storage_key'       => $storageKey,
                'original_filename' => $originalFilename,
                'sort_order'        => $i,
            ]);
        }

        ProgramClassActivity::where('program_class_id', $class->id)->delete();
        foreach ((array) $request->input('activities.title', []) as $i => $title) {
            if (trim((string) $title) === '') {
                continue;
            }
            ProgramClassActivity::create([
                'program_class_id' => $class->id,
                'title'             => $title,
                'description'       => $request->input("activities.description.{$i}"),
                'video_url'         => trim((string) $request->input("activities.video_url.{$i}")) ?: null,
                'link_url'          => trim((string) $request->input("activities.link_url.{$i}")) ?: null,
                'sort_order'        => $i,
            ]);
        }

        ProgramClassOutcome::where('program_class_id', $class->id)->delete();
        foreach ((array) $request->input('outcomes', []) as $i => $text) {
            if (trim((string) $text) === '') {
                continue;
            }
            ProgramClassOutcome::create([
                'program_class_id' => $class->id,
                'outcome_text'      => $text,
                'sort_order'        => $i,
            ]);
        }

        return $warnings;
    }
}
