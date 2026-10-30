<?php

namespace App\Repositories\Portal;

use App\Models\Portal\PortalAudienceMetric;
use App\Models\Portal\PortalCategoryTarget;
use App\Models\Portal\PortalDailyPlan;
use App\Models\Portal\PortalPageFix;
use App\Models\Portal\PortalProductionPlaceholder;
use App\Models\Portal\PortalReel;
use App\Models\Portal\PortalReelCustomField;
use App\Models\Portal\PortalReelSeries;
use App\Models\Portal\PortalResponsibility;
use App\Models\Portal\PortalSetting;
use App\Models\Portal\PortalSettingList;
use App\Models\Portal\PortalTask;
use App\Models\Portal\PortalWeeklyGoal;
use App\Models\Portal\ReelProject;
use App\Models\Staff\Staff;
use App\Services\Portal\PortalNotifier;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Carbon\Carbon;
use Illuminate\Support\Str;

/** Social board — handoff spec phase 8 (reel pipeline, month plan,
 *  audience numbers, page fixes). Storage-only against the rest of the
 *  LMS: thumbnails/prompt files reuse the existing Upload model, nothing
 *  else here touches any non-portal table. Daily plans/weekly goals and
 *  Growth Pay are deliberately left out of this pass — see the written
 *  summary for why. */
class SocialRepository
{
    use ReturnFormatTrait, CommonHelperTrait;

    // ---- Reels -----------------------------------------------------

    public function reels(array $filters)
    {
        return PortalReel::with(['suggestedBy.staff.designation', 'creator', 'assignee', 'seriesModel'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when($filters['format'] ?? null, fn ($q, $v) => $q->where('format', $v))
            ->when($filters['series_name'] ?? null, fn ($q, $v) => $q->where('series_name', $v))
            ->orderByDesc('id')
            ->get();
    }

    public function showReel(int $id): ?PortalReel
    {
        return PortalReel::with(['suggestedBy', 'creator', 'reviewedBy', 'assignee', 'promptUpload', 'thumbUpload', 'seriesModel'])->find($id);
    }

    public function storeReel($request, int $userId): array
    {
        try {
            $promptUploadId = $request->hasFile('prompt_file') ? $this->UploadImageCreate($request->file('prompt_file'), 'uploads/portal-reels') : null;
            $thumbUploadId  = $request->hasFile('thumb_file') ? $this->UploadImageCreate($request->file('thumb_file'), 'uploads/portal-reels') : null;

            $defaults = $this->resolveSeriesDefaults($request->series_name);
            $customFields = $this->collectCustomFieldValues($request);
            $this->fillBlankFieldsFromSeries($customFields, $defaults);

            $reel = PortalReel::create([
                'title'            => $request->title,
                'format'           => $request->format ?: ($defaults['format'] ?? 'Reel'),
                'category'         => $request->category ?: ($defaults['category'] ?? null),
                'series_name'      => $request->series_name,
                'hook'             => $request->hook,
                'status'           => PortalReel::SUGGESTED,
                'suggested_by'     => $userId,
                'created_by'       => $userId,
                'planned_date'     => $request->planned_date ?: null,
                'drive_link'       => $request->drive_link,
                'prompt_text'      => $request->prompt_text,
                'prompt_upload_id' => $promptUploadId,
                'prompt_link'      => $request->prompt_link,
                'thumb_upload_id'  => $thumbUploadId,
                'note'             => $request->note,
                'custom_fields'    => $customFields,
            ]);

            return $this->responseWithSuccess('Reel added.', ['id' => $reel->id]);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function updateReel($request, int $id): array
    {
        $reel = PortalReel::find($id);
        if (!$reel) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        if ($request->hasFile('prompt_file')) {
            $reel->prompt_upload_id = $this->UploadImageCreate($request->file('prompt_file'), 'uploads/portal-reels');
        }
        if ($request->hasFile('thumb_file')) {
            $reel->thumb_upload_id = $this->UploadImageCreate($request->file('thumb_file'), 'uploads/portal-reels');
        }

        $existingCustomFields = $reel->custom_fields ?: [];
        $defaults = $this->resolveSeriesDefaults($request->series_name);
        $customFields = $this->collectCustomFieldValues($request, $existingCustomFields);
        $this->fillBlankFieldsFromSeries($customFields, $defaults);

        $reel->title        = $request->title;
        $reel->format       = $request->format ?: ($defaults['format'] ?? $reel->format);
        $reel->category     = $request->category ?: ($defaults['category'] ?? $reel->category);
        $reel->series_name  = $request->series_name;
        $reel->hook          = $request->hook;
        $reel->planned_date = $request->planned_date ?: null;
        $reel->drive_link   = $request->drive_link;
        $reel->prompt_text  = $request->prompt_text;
        $reel->prompt_link  = $request->prompt_link;
        $reel->note         = $request->note;
        $reel->custom_fields = $customFields;
        $reel->save();

        return $this->responseWithSuccess('Saved.', []);
    }

    /** $request->custom_fields: [field_key => value] - only for whatever
     *  fields currently exist (a deleted field's old value in a reel's
     *  JSON just stays there, unused, rather than being validated away).
     *  $existing: the reel's current custom_fields, so a File field with
     *  no new upload on this submit keeps whatever it already had instead
     *  of being wiped (same rule as the built-in Prompt file/Thumbnail). */
    private function collectCustomFieldValues($request, array $existing = []): array
    {
        $submitted = (array) $request->input('custom_fields', []);
        $values = [];
        foreach ($this->reelCustomFields() as $field) {
            if ($field->type === PortalReelCustomField::TYPE_CHECKBOX) {
                $values[$field->key] = isset($submitted[$field->key]);
                continue;
            }
            if ($field->type === PortalReelCustomField::TYPE_FILE) {
                if ($request->hasFile("custom_fields.{$field->key}")) {
                    $values[$field->key] = $this->UploadImageCreate($request->file("custom_fields.{$field->key}"), 'uploads/portal-reels');
                } elseif (isset($existing[$field->key])) {
                    $values[$field->key] = $existing[$field->key];
                }
                continue;
            }
            // The Characters field can hold more than one - a series (and
            // so a reel) can feature several recurring characters at once.
            if ($field->list_key === PortalReelCustomField::SOURCE_REEL_CHARACTERS) {
                $arr = array_values(array_filter((array) ($submitted[$field->key] ?? [])));
                if ($arr) {
                    $values[$field->key] = $arr;
                }
                continue;
            }
            $v = trim((string) ($submitted[$field->key] ?? ''));
            if ($v !== '') {
                $values[$field->key] = $v;
            }
        }
        return $values;
    }

    /** Fills in any custom field the submitter left blank with the
     *  series' default for that field - never overwrites something
     *  already provided. */
    private function fillBlankFieldsFromSeries(array &$customFields, array $seriesDefaults): void
    {
        foreach (($seriesDefaults['custom_fields'] ?? []) as $key => $value) {
            if (empty($customFields[$key])) {
                $customFields[$key] = $value;
            }
        }
    }

    // ---- Series — every Reel form field can default per-series -------

    public function reelSeries()
    {
        return PortalReelSeries::orderBy('sort_order')->get();
    }

    /** Shared with the mobile API so both sides enforce the exact same
     *  rule for who can suggest/manage reel content -- portal_manage,
     *  the dedicated suggest permission, or whoever currently holds the
     *  Content responsibility. Moved here (was private on SocialController)
     *  so it can't drift between web and mobile. */
    public function canManageContent(?int $actingStaffId): bool
    {
        if (hasPermission('portal_manage') || hasPermission('portal_social_suggest_reel_read')) {
            return true;
        }
        return $actingStaffId !== null && in_array($actingStaffId, PortalResponsibility::ownerStaffIdsForKey(PortalResponsibility::KEY_CONTENT), true);
    }

    /** [] if no series picked, or the series was renamed/deleted since -
     *  callers already treat a missing default as "leave that field as
     *  the submitter set it". Shape: {category, format, custom_fields:
     *  {field_key: value}}. */
    private function resolveSeriesDefaults(?string $seriesName): array
    {
        if (!$seriesName) {
            return [];
        }
        $series = PortalReelSeries::where('name', $seriesName)->first();
        return $series ? ($series->defaults ?: []) : [];
    }

    /** One JSON `defaults` column per series holds Pillar, Format, and
     *  every current Reel form custom field - same add/rename/delete-in-
     *  one-submit pattern as updateSocialSettings() and
     *  updateReelCustomFields(). */
    public function updateReelSeries($request): array
    {
        $rows = (array) $request->input('series', []);
        foreach ($rows as $id => $data) {
            $series = PortalReelSeries::find($id);
            if (!$series) {
                continue;
            }
            if (!empty($data['delete'])) {
                $series->delete();
                continue;
            }
            $name = trim($data['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $series->name      = $name;
            $series->code      = trim($data['code'] ?? '') ?: null;
            $series->color     = trim($data['color'] ?? '') ?: null;
            $series->defaults  = $this->buildSeriesDefaults($data, $request, "series.{$id}.custom_fields", $series->defaults ?: []);
            $series->save();
        }

        $newRows = (array) $request->input('new_series', []);
        $nextOrder = ((int) PortalReelSeries::max('sort_order')) + 1;
        foreach ($newRows as $index => $data) {
            $name = trim($data['name'] ?? '');
            if ($name === '') {
                continue;
            }
            PortalReelSeries::create([
                'name'       => $name,
                'code'       => trim($data['code'] ?? '') ?: null,
                'color'      => trim($data['color'] ?? '') ?: null,
                'defaults'   => $this->buildSeriesDefaults($data, $request, "new_series.{$index}.custom_fields", []),
                'sort_order' => $nextOrder++,
            ]);
        }

        return $this->responseWithSuccess('Saved.', []);
    }

    /** $data: the submitted series[...]/new_series[...] row - category,
     *  format, and custom_fields[field_key] (an array for the Characters
     *  field, a plain value for everything else, an uploaded file for a
     *  File/Image field - read via $request directly using
     *  $fileFieldPrefix since an upload never comes through $data). A
     *  File field with no new upload this submit keeps whatever Upload
     *  id $existingDefaults already had, same rule as a reel's own
     *  Prompt file/Thumbnail/Cover. */
    private function buildSeriesDefaults(array $data, $request, string $fileFieldPrefix, array $existingDefaults): array
    {
        $existingCustomFields = $existingDefaults['custom_fields'] ?? [];
        $submitted = (array) ($data['custom_fields'] ?? []);

        $customFields = [];
        foreach ($this->reelCustomFields() as $field) {
            $key = $field->key;
            if ($field->type === PortalReelCustomField::TYPE_FILE) {
                if ($request->hasFile("{$fileFieldPrefix}.{$key}")) {
                    $customFields[$key] = $this->UploadImageCreate($request->file("{$fileFieldPrefix}.{$key}"), 'uploads/portal-reels');
                } elseif (!empty($existingCustomFields[$key])) {
                    $customFields[$key] = $existingCustomFields[$key];
                }
                continue;
            }

            $value = $submitted[$key] ?? null;
            if (is_array($value)) {
                $value = array_values(array_filter($value));
                if ($value) {
                    $customFields[$key] = $value;
                }
                continue;
            }
            $value = trim((string) $value);
            if ($value !== '') {
                $customFields[$key] = $value;
            }
        }

        return [
            'category'      => $data['category'] ?: null,
            'format'        => $data['format'] ?: null,
            'custom_fields' => $customFields,
        ];
    }

    // ---- Reel workflow: assign, prompt/video stages, schedule --------

    /** Reel Studio (scenes + AI prompt generation) was built as a
     *  separate system with no link to this one - this is the bridge.
     *  First "Open" on an approved reel creates its project, pre-filled
     *  from whatever the reel already has (title/hook/note, plus a
     *  best-effort read of the Orientation/Time custom fields if those
     *  were set up); every later "Open" just reuses it. */
    public function openOrCreateReelProject(PortalReel $reel, int $userId): ReelProject
    {
        if ($reel->reel_project_id) {
            $existing = ReelProject::find($reel->reel_project_id);
            if ($existing) {
                return $existing;
            }
        }

        $customFields = $reel->custom_fields ?: [];
        $orientationValue = null;
        $timeValue = null;
        foreach ($this->reelCustomFields() as $field) {
            $value = $customFields[$field->key] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            if (str_contains(strtolower($field->label), 'orientation')) {
                $orientationValue = $value;
            }
            if (str_contains(strtolower($field->label), 'time')) {
                $timeValue = $value;
            }
        }

        $format = ($orientationValue && stripos((string) $orientationValue, 'land') !== false) ? 'horizontal' : 'vertical';

        $project = ReelProject::create([
            'title'                   => $reel->title,
            'hook'                    => $reel->hook,
            'description'             => $reel->note,
            'format'                  => $format,
            'target_duration_seconds' => is_numeric($timeValue) ? (int) $timeValue : null,
            'created_by'              => $userId,
        ]);

        $reel->reel_project_id = $project->id;
        $reel->save();

        return $project;
    }

    public function assignReel(int $id, ?int $staffId, bool $openPaid, ?string $deadline, int $assignedByUserId): array
    {
        $reel = PortalReel::find($id);
        if (!$reel) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        $reel->assigned_to  = $openPaid ? null : $staffId;
        $reel->is_open_paid = $openPaid;
        $reel->deadline     = $deadline ?: null;
        if ($reel->status === PortalReel::SUGGESTED) {
            $reel->status = PortalReel::ACCEPTED;
        }
        $reel->save();

        $this->syncReelTask($reel, $openPaid ? null : $staffId, $openPaid, $deadline, $assignedByUserId);

        return $this->responseWithSuccess('Assigned.', []);
    }

    /** Bulk version of assignReel() - one staff member (never open-paid)
     *  and one deadline applied to a whole batch of ticked ids, each
     *  still going through the same task-sync/notification as normal. */
    public function bulkAssignReels(array $ids, int $staffId, ?string $deadline, int $assignedByUserId): array
    {
        foreach ($ids as $id) {
            $this->assignReel((int) $id, $staffId, false, $deadline, $assignedByUserId);
        }

        return $this->responseWithSuccess(count($ids) . ' reel(s) assigned.', []);
    }

    /** Marks a batch of reels as open paid tasks - same effect as ticking
     *  "Open — Paid Task" on a reel's own Assign card, just over several
     *  at once, with an amount and (optionally) a specific list of staff
     *  it's restricted to instead of open to everyone. */
    public function bulkMarkReelsPaid(array $ids, float $amount, ?string $deadline, array $visibleToStaffIds, int $assignedByUserId): array
    {
        foreach ($ids as $id) {
            $reel = PortalReel::find((int) $id);
            if (!$reel) {
                continue;
            }
            $reel->assigned_to  = null;
            $reel->is_open_paid = true;
            $reel->deadline     = $deadline ?: $reel->deadline;
            if ($reel->status === PortalReel::SUGGESTED) {
                $reel->status = PortalReel::ACCEPTED;
            }
            $reel->save();

            $this->syncReelTask($reel, null, true, $deadline ?: $reel->deadline?->format('Y-m-d'), $assignedByUserId, $amount, $visibleToStaffIds);
        }

        return $this->responseWithSuccess(count($ids) . ' reel(s) marked as paid tasks.', []);
    }

    /** Keeps one Team Portal task in step with a reel's assignment -
     *  created the first time it's assigned, updated (never duplicated)
     *  on every re-assign - so "as soon as the task is assigned the
     *  content creator will see the task in her task list" holds true
     *  the same way it would for any other task. */
    private function syncReelTask(PortalReel $reel, ?int $staffId, bool $openPaid, ?string $deadline, int $assignedByUserId, ?float $amount = null, array $visibleToStaffIds = []): void
    {
        $task = PortalTask::where('reel_id', $reel->id)->first();

        $attributes = [
            'reel_id'     => $reel->id,
            'title'       => 'Content: ' . $reel->title,
            'category'    => 'Content',
            'assigned_to' => $openPaid ? null : $staffId,
            'assigned_by' => $assignedByUserId,
            // due_date has no DB default and isn't nullable - the reel's
            // own deadline is optional, so a week out stands in when
            // it's left blank rather than failing to sync at all.
            'due_date'    => $deadline ?: now()->addDays(7)->format('Y-m-d'),
            'paid'        => $openPaid,
            'status'      => $openPaid ? PortalTask::OPEN : PortalTask::ASSIGNED,
        ];
        // Only touch amount/visibility when this call actually carries
        // them (the bulk-mark-paid path) - a plain re-assign through the
        // single-reel Assign card passes neither, and must never blank out
        // an amount or a visibility restriction already set on the task.
        if ($openPaid && $amount !== null) {
            $attributes['amount'] = $amount;
        }
        if ($openPaid && $visibleToStaffIds) {
            $attributes['visible_to_staff_ids'] = $visibleToStaffIds;
        }

        if ($task) {
            // pay_status only gets initialised the moment a task first
            // becomes paid - never reset on a later re-sync, so it can't
            // stomp on 'due'/'paid' a completed task has already reached.
            if ($openPaid && !$task->paid) {
                $attributes['pay_status'] = 'unpaid';
            }
            $task->update($attributes);
        } else {
            $attributes['pay_status'] = $openPaid ? 'unpaid' : null;
            $task = PortalTask::create($attributes + ['assigned_date' => now()->format('Y-m-d')]);
        }

        if ($openPaid) {
            Staff::active()->whereNotNull('user_id')
                ->when($visibleToStaffIds, fn ($q) => $q->whereIn('id', $visibleToStaffIds))
                ->get()
                ->each(function (Staff $staff) use ($task) {
                    PortalNotifier::send($staff->user_id, 'Paid task available', $task->title, route('portal-tasks.show', $task->id));
                });
        } elseif ($task->assignee && $task->assignee->user_id) {
            PortalNotifier::send($task->assignee->user_id, 'New task assigned', $task->title, route('portal-tasks.show', $task->id));
        }
    }

    /** The assignee submits a link for whichever stage is next - three
     *  stages in order (Prompt -> Canva -> Video/Drive), each blocked
     *  until the previous one is approved. Resubmitting a stage clears
     *  its own approval (a new link needs a fresh look), never another
     *  stage's. Notifies whoever last reviewed this reel that something's
     *  waiting on them. */
    public function submitStageLink(int $id, string $stage, string $link, int $staffId): array
    {
        $reel = PortalReel::find($id);
        if (!$reel) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if ((int) $reel->assigned_to !== $staffId) {
            return $this->responseWithError("This isn't your reel/topic.", []);
        }

        $result = $this->recordStageLink($reel, $stage, $link);
        if (!$result['status']) {
            return $result;
        }

        return $this->responseWithSuccess('Submitted for approval.', []);
    }

    /** "Send Prompt for Approval" in Reel Studio - always (re)submits the
     *  Reel Studio script download as this reel's prompt link, clears any
     *  previous approval, and notifies the reviewer. Unlike
     *  syncStageLinkFromReelStudio() below this never skips: the download
     *  URL itself is stable even when the scenes behind it changed, so
     *  "nothing changed" can't be inferred from the link text here. */
    public function sendPromptFromReelStudio(PortalReel $reel, string $scriptUrl): array
    {
        return $this->recordStageLink($reel, 'prompt', $scriptUrl);
    }

    /** Same stage-link write as submitStageLink(), minus the "is this
     *  your reel" ownership check - used when Reel Studio's own Reel
     *  Setup save propagates a Canva/Drive link, since ReelStudioRepository
     *  already ran its own (broader) authorization check. Silently skips
     *  if the stage isn't unlocked yet or the link hasn't actually
     *  changed, rather than failing the whole Reel Setup save over it. */
    public function syncStageLinkFromReelStudio(PortalReel $reel, string $stage, ?string $link): void
    {
        $link = trim((string) $link);
        $current = $stage === 'canva' ? $reel->canva_link : $reel->drive_link;
        if ($link === '' || $link === $current) {
            return;
        }
        $this->recordStageLink($reel, $stage, $link);
    }

    /** The actual field-level write + gating + notification for one stage
     *  submission, shared by both entry points above. */
    private function recordStageLink(PortalReel $reel, string $stage, string $link): array
    {
        if ($stage === 'prompt') {
            $reel->prompt_link        = $link;
            $reel->prompt_approved_at = null;
            if ($reel->status === PortalReel::ACCEPTED) {
                $reel->status = PortalReel::PRODUCTION;
            }
        } elseif ($stage === 'canva') {
            if (!$reel->prompt_approved_at) {
                return $this->responseWithError('The prompt needs to be approved first.', []);
            }
            $reel->canva_link        = $link;
            $reel->canva_approved_at = null;
        } elseif ($stage === 'video') {
            if (!$reel->canva_approved_at) {
                return $this->responseWithError('The Canva link needs to be approved first.', []);
            }
            $reel->drive_link       = $link;
            $reel->video_approved_at = null;
        } else {
            return $this->responseWithError('Not a valid stage.', []);
        }
        $reel->save();

        $this->notifyReviewer($reel, ucfirst($stage) . ' submitted for review', $reel->title);

        return $this->responseWithSuccess('Saved.', []);
    }

    public function approveStage(int $id, string $stage, int $reviewerUserId, ?string $feedback = null, $feedbackFile = null): array
    {
        $reel = PortalReel::find($id);
        if (!$reel) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $uploadId = $feedbackFile ? $this->UploadImageCreate($feedbackFile, 'uploads/portal-reels') : null;

        if ($stage === 'prompt') {
            if (!$reel->prompt_link) {
                return $this->responseWithError('No prompt link to approve yet.', []);
            }
            $reel->prompt_approved_at = now();
            $reel->prompt_feedback    = $feedback;
            if ($uploadId) { $reel->prompt_feedback_upload_id = $uploadId; }
            $this->notifyAssignee($reel, 'Prompt approved', 'You can start on the Canva link now.');
        } elseif ($stage === 'canva') {
            if (!$reel->canva_link) {
                return $this->responseWithError('No Canva link to approve yet.', []);
            }
            $reel->canva_approved_at = now();
            $reel->canva_feedback    = $feedback;
            if ($uploadId) { $reel->canva_feedback_upload_id = $uploadId; }
            $this->notifyAssignee($reel, 'Canva approved', 'Go ahead and upload the video to Drive now.');
        } elseif ($stage === 'video') {
            if (!$reel->drive_link) {
                return $this->responseWithError('No video link to approve yet.', []);
            }
            $reel->video_approved_at = now();
            $reel->video_feedback    = $feedback;
            if ($uploadId) { $reel->video_feedback_upload_id = $uploadId; }
            $reel->status            = PortalReel::READY;
        } else {
            return $this->responseWithError('Not a valid stage.', []);
        }
        $reel->reviewed_by = $reviewerUserId;
        $reel->reviewed_at = now();
        $reel->save();

        return $this->responseWithSuccess(ucfirst($stage) . ' approved.', []);
    }

    /** Lets whoever last reviewed this reel know something's waiting on
     *  them - falls back to nothing (not every reel has a reviewer yet,
     *  e.g. one approved in bulk) rather than guessing a recipient. */
    private function notifyReviewer(PortalReel $reel, string $title, string $message): void
    {
        if ($reel->reviewed_by) {
            PortalNotifier::send((int) $reel->reviewed_by, $title, $message, route('portal-social-reels.show', $reel->id));
        }
    }

    private function notifyAssignee(PortalReel $reel, string $title, string $message): void
    {
        $userId = optional($reel->assignee)->user_id;
        if ($userId) {
            PortalNotifier::send((int) $userId, $title, $message, route('portal-social-reels.show', $reel->id));
        }
    }

    /** Who shows up in the "Assign to" dropdown - every staff member
     *  whose Role is one of the admin-picked Roles in Social Board
     *  Settings (e.g. "Content Creator", or "Content Creator" + "Teacher"
     *  for staff doing dual duty). Separate from contentTeam() above
     *  (Daily Plan/Weekly Goals keeps using the Content responsibility
     *  list unchanged - this is specifically for reel assignment). */
    public function assignableStaff()
    {
        $roleIds = PortalSetting::current()->content_assignee_role_ids ?: [];
        if (!$roleIds) {
            return collect();
        }
        return Staff::whereIn('role_id', $roleIds)->orderBy('first_name')->get();
    }

    public function updateContentAssigneeRoles($request): array
    {
        $roleIds = array_values(array_map('intval', (array) $request->input('content_assignee_role_ids', [])));
        PortalSetting::current()->update(['content_assignee_role_ids' => $roleIds]);
        return $this->responseWithSuccess('Saved.', []);
    }

    /** The last step - picking when it actually goes live. Locked behind
     *  video approval so a date can't be set before the work is done. */
    public function scheduleUpload(int $id, string $date): array
    {
        $reel = PortalReel::find($id);
        if (!$reel) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if (!$reel->video_approved_at) {
            return $this->responseWithError('The video needs to be approved before scheduling an upload date.', []);
        }
        $reel->planned_date = $date;
        $reel->status        = PortalReel::PUBLISHED;
        $reel->save();

        return $this->responseWithSuccess('Upload date set.', []);
    }

    /** Accept, reject, or move to the next pipeline status — always with
     *  a note, per "Review reels: accept, reject, change status, add a
     *  note." */
    public function reviewReel(int $id, string $newStatus, ?string $note, int $reviewerUserId): array
    {
        if (!in_array($newStatus, [PortalReel::ACCEPTED, PortalReel::PRODUCTION, PortalReel::READY, PortalReel::PUBLISHED, PortalReel::REJECTED], true)) {
            return $this->responseWithError('Not a valid status.', []);
        }

        $reel = PortalReel::find($id);
        if (!$reel) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $reel->status      = $newStatus;
        $reel->note         = $note ?: $reel->note;
        $reel->reviewed_by  = $reviewerUserId;
        $reel->reviewed_at  = now();
        $reel->save();

        return $this->responseWithSuccess('Updated.', []);
    }

    /** Bulk version of reviewReel() - same per-reel behaviour (status,
     *  reviewed_by/at, and the model's own code-on-approval hook), just
     *  looped over a batch of ticked ids. */
    public function bulkReviewReels(array $ids, string $status, int $reviewerUserId): array
    {
        foreach ($ids as $id) {
            $this->reviewReel((int) $id, $status, null, $reviewerUserId);
        }

        return $this->responseWithSuccess(count($ids) . ' reel(s) ' . ($status === PortalReel::REJECTED ? 'discarded.' : 'approved.'), []);
    }

    public function destroyReel(int $id): array
    {
        $reel = PortalReel::find($id);
        if (!$reel) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        $reel->delete();
        return $this->responseWithSuccess('Removed.', []);
    }

    // ---- Month plan / targets -----------------------------------------

    public function categoryTargets()
    {
        return PortalCategoryTarget::orderBy('series_name')->orderBy('format')->get();
    }

    /** The selected month's actual count per Series + Format against the
     *  configured targets — business rule 10: counts from accepted onward,
     *  by planned_date falling in that month. */
    public function monthPlan(string $month): array
    {
        $targets = $this->categoryTargets();
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $thisMonth = PortalReel::whereIn('status', PortalReel::COUNTS_TOWARD_MONTH)
            ->whereBetween('planned_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

        return $targets->map(function (PortalCategoryTarget $t) use ($thisMonth) {
            return [
                'id'     => $t->id,
                'series' => $t->series_name,
                'format' => $t->format,
                'target' => $t->reel_target,
                'actual' => $thisMonth->where('series_name', $t->series_name)->where('format', $t->format)->count(),
            ];
        })->values()->all();
    }

    /** One row per calendar day of the selected month - the admin picks
     *  a Series then a ready-to-upload reel from it per day; Format /
     *  Created by / Drive link are read straight off that reel, never
     *  entered separately. "Ready to upload" = its video is approved and
     *  it's either not yet scheduled anywhere, or already scheduled within
     *  this same month (so it still shows on its day here). One reel per
     *  day, keyed by `planned_date` - the actual go-live date. */
    public function monthCalendar(string $month): array
    {
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $reels = PortalReel::with('assignee')
            ->whereNotNull('video_approved_at')
            ->where(function ($q) use ($start, $end) {
                $q->whereNull('planned_date')
                    ->orWhereBetween('planned_date', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
            })
            ->orderBy('series_name')
            ->orderBy('title')
            ->get();

        $byDate = $reels->filter(fn (PortalReel $r) => $r->planned_date)
            ->keyBy(fn (PortalReel $r) => $r->planned_date->format('Y-m-d'));

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[] = ['date' => $d->copy(), 'reel' => $byDate->get($d->format('Y-m-d'))];
        }

        return ['days' => $days, 'reels' => $reels];
    }

    /** One PortalReel::scheduleUpload() per day that got a reel picked -
     *  a day left blank is simply skipped, not unscheduled. */
    public function updateMonthCalendar($request): array
    {
        $assignments = (array) $request->input('assignments', []);
        foreach ($assignments as $date => $reelId) {
            if (!$reelId) {
                continue;
            }
            $this->scheduleUpload((int) $reelId, $date);
        }

        return $this->responseWithSuccess('Upload plan saved.', []);
    }

    /** Like monthCalendar(), but for deciding WHO works on WHAT and BY
     *  WHEN rather than when it goes live - every approved reel regardless
     *  of video status, keyed by `deadline` instead of `planned_date`, and
     *  more than one reel can share a day (several people can have work
     *  due the same day). Each day's slots mix two kinds: a real reel
     *  (deadline already set) and a placeholder (series picked, no topic
     *  yet) - the view doesn't care which, it just reads slot.reel (null
     *  for a placeholder) and slot.series_name. */
    public function productionCalendar(string $month): array
    {
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $reels = PortalReel::with('assignee')
            ->whereIn('status', PortalReel::COUNTS_TOWARD_MONTH)
            ->where(function ($q) use ($start, $end) {
                $q->whereNull('deadline')
                    ->orWhereBetween('deadline', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
            })
            ->orderBy('series_name')
            ->orderBy('title')
            ->get();

        $reelsByDate = $reels->filter(fn (PortalReel $r) => $r->deadline)
            ->groupBy(fn (PortalReel $r) => $r->deadline->format('Y-m-d'));

        $placeholdersByDate = PortalProductionPlaceholder::whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->orderBy('id')
            ->get()
            ->groupBy(fn (PortalProductionPlaceholder $p) => $p->date->format('Y-m-d'));

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dateKey = $d->format('Y-m-d');
            $slots = $reelsByDate->get($dateKey, collect())
                ->map(fn (PortalReel $r) => ['reel' => $r, 'series_name' => $r->series_name, 'pillar' => $r->category])
                ->concat(
                    $placeholdersByDate->get($dateKey, collect())
                        ->map(fn (PortalProductionPlaceholder $p) => ['reel' => null, 'series_name' => $p->series_name ?: null, 'pillar' => $p->pillar])
                )
                ->values();
            $days[] = ['date' => $d->copy(), 'slots' => $slots];
        }

        return ['days' => $days, 'reels' => $reels];
    }

    /** assignments[date][topic][] = reelId (or '' for a blank slot),
     *  assignments[date][series][] and [pillar][] = whatever's picked for
     *  that SAME position - a topic picked wins (sets that reel's
     *  deadline); with no topic yet, either a series or just a pillar
     *  alone becomes a placeholder, so a day can be reserved before even
     *  the series is decided. Each day's placeholders are replaced
     *  wholesale from what's submitted, since the whole day's slots are
     *  always resubmitted together. Unlike the Upload Plan this never
     *  touches status or requires video approval, since it's only "which
     *  day is this due". */
    public function updateProductionCalendar($request): array
    {
        $assignments = (array) $request->input('assignments', []);
        foreach ($assignments as $date => $row) {
            $topics  = (array) ($row['topic'] ?? []);
            $series  = (array) ($row['series'] ?? []);
            $pillars = (array) ($row['pillar'] ?? []);

            PortalProductionPlaceholder::where('date', $date)->delete();

            foreach ($topics as $i => $reelId) {
                if ($reelId) {
                    $reel = PortalReel::find((int) $reelId);
                    if ($reel) {
                        $reel->deadline = $date;
                        $reel->save();
                    }
                    continue;
                }

                $seriesName = trim($series[$i] ?? '');
                $pillar     = trim($pillars[$i] ?? '');
                if ($seriesName !== '' || $pillar !== '') {
                    PortalProductionPlaceholder::create([
                        'date'        => $date,
                        'series_name' => $seriesName,
                        'pillar'      => $pillar ?: null,
                    ]);
                }
            }
        }

        return $this->responseWithSuccess('Production plan saved.', []);
    }

    /** `category` keeps the old NOT NULL + unique constraint it can't be
     *  freed of without the doctrine/dbal package (see the migration) - a
     *  harmless "Series — Format" value satisfies it without being read
     *  anywhere. */
    public function updateCategoryTargets($request): array
    {
        $seriesNames = (array) $request->series_name;
        $formats     = (array) $request->format;
        $targets     = (array) $request->target;

        foreach ($seriesNames as $i => $series) {
            $series = trim($series);
            $format = trim($formats[$i] ?? '');
            if ($series === '' || $format === '') {
                continue;
            }
            PortalCategoryTarget::updateOrCreate(
                ['series_name' => $series, 'format' => $format],
                ['category' => $series . ' — ' . $format, 'reel_target' => (int) ($targets[$i] ?? 0)]
            );
        }

        return $this->responseWithSuccess('Targets saved.', []);
    }

    // ---- Audience numbers --------------------------------------------

    public function metricsForMonth(string $month)
    {
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        return PortalAudienceMetric::whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->orderBy('date')->get();
    }

    public function saveMetric($request, int $userId): array
    {
        $values = [];
        foreach ((array) $request->metric_name as $i => $name) {
            $name = trim($name);
            if ($name !== '' && isset($request->metric_value[$i]) && $request->metric_value[$i] !== '') {
                $values[$name] = (int) $request->metric_value[$i];
            }
        }
        if (empty($values)) {
            return $this->responseWithError('Enter at least one metric value.', []);
        }

        $metric = PortalAudienceMetric::updateOrCreate(
            ['date' => $request->date, 'platform' => $request->platform],
            ['values' => $values, 'recorded_by' => $userId]
        );

        return $this->responseWithSuccess('Saved.', ['id' => $metric->id]);
    }

    // ---- Page fixes ----------------------------------------------------

    public function pageFixes()
    {
        return PortalPageFix::orderByRaw("status = 'fixed'")->orderByDesc('id')->get();
    }

    public function storePageFix($request, int $userId): array
    {
        PortalPageFix::create([
            'title'         => $request->title,
            'description'   => $request->description,
            'page_category' => $request->page_category ?: null,
            'created_by'    => $userId,
        ]);
        return $this->responseWithSuccess('Added.', []);
    }

    public function updatePageFixStatus(int $id, string $status): array
    {
        if (!in_array($status, ['open', 'in_progress', 'fixed'], true)) {
            return $this->responseWithError('Not a valid status.', []);
        }

        $fix = PortalPageFix::find($id);
        if (!$fix) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $fix->status = $status;
        $fix->save();

        return $this->responseWithSuccess('Updated.', []);
    }

    // ---- Social Board Settings — freely add/rename/delete-able blocks ---

    /** All blocks, in display order. Auto-seeds the original 5 defaults
     *  on a tenant that's never had any (brand new site, nothing to carry
     *  over) so the page never starts out completely blank. */
    public function settingLists()
    {
        if (!PortalSettingList::query()->exists()) {
            $this->seedDefaultSettingLists();
        }
        return PortalSettingList::orderBy('sort_order')->get();
    }

    private function seedDefaultSettingLists(): void
    {
        $defaults = [
            ['key' => 'social_platforms', 'label' => 'Platforms', 'values' => PortalSetting::DEFAULT_PLATFORMS],
            ['key' => 'social_metrics', 'label' => 'Tracked numbers', 'values' => PortalSetting::DEFAULT_SOCIAL_METRICS],
            ['key' => 'reel_formats', 'label' => 'Formats', 'values' => PortalSetting::DEFAULT_REEL_FORMATS],
            ['key' => 'reel_categories', 'label' => 'Reel/topic pillars', 'values' => PortalSetting::DEFAULT_REEL_CATEGORIES],
            ['key' => 'series_names', 'label' => 'Series names', 'values' => PortalSetting::DEFAULT_SERIES_NAMES],
        ];
        foreach ($defaults as $i => $d) {
            PortalSettingList::create([
                'key'        => $d['key'],
                'label'      => $d['label'],
                'values'     => $d['values'],
                'sort_order' => $i + 1,
            ]);
        }
    }

    /** $request->lists: [id => ['label' => ..., 'values' => [...], 'delete' => '1'?]]
     *  for every existing block (edit in place, or delete if flagged).
     *  $request->new_lists: the same shape, keyed 0..n, for blocks added
     *  on this submit - each becomes a new row with a fresh unique key. */
    public function updateSocialSettings($request): array
    {
        $lists = (array) $request->input('lists', []);
        foreach ($lists as $id => $block) {
            $list = PortalSettingList::find($id);
            if (!$list) {
                continue;
            }
            if (!empty($block['delete'])) {
                $list->delete();
                continue;
            }
            $label = trim($block['label'] ?? '');
            if ($label === '') {
                continue; // never save a block with no name
            }
            $list->label  = $label;
            $list->values = array_values(array_filter(array_map('trim', (array) ($block['values'] ?? []))));
            $list->save();
        }

        $newBlocks = (array) $request->input('new_lists', []);
        $nextOrder = ((int) PortalSettingList::max('sort_order')) + 1;
        foreach ($newBlocks as $block) {
            $label = trim($block['label'] ?? '');
            if ($label === '') {
                continue;
            }
            PortalSettingList::create([
                'key'        => $this->uniqueListKey($label),
                'label'      => $label,
                'values'     => array_values(array_filter(array_map('trim', (array) ($block['values'] ?? [])))),
                'sort_order' => $nextOrder++,
            ]);
        }

        return $this->responseWithSuccess('Saved.', []);
    }

    private function uniqueListKey(string $label): string
    {
        $base = Str::slug($label, '_') ?: 'list';
        $key  = $base;
        $i    = 1;
        while (PortalSettingList::where('key', $key)->exists()) {
            $key = $base . '_' . (++$i);
        }
        return $key;
    }

    // ---- Reel form custom fields -----------------------------------

    public function reelCustomFields()
    {
        return PortalReelCustomField::orderBy('sort_order')->get();
    }

    /** Global Instructions + the AI Scene Generation prompt template used
     *  by Reel Studio's "AI: Fill In Scenes" - kept here (not its own page)
     *  so every reel-related AI setting lives in one place. */
    public function updateReelAiPrompt($request): array
    {
        try {
            $this->setSetting('reel_global_instructions', $request->input('reel_global_instructions'));
            $this->setSetting('reel_ai_prompt_template', $request->input('reel_ai_prompt_template'));

            return $this->responseWithSuccess('Settings updated.', []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    private function setSetting(string $name, ?string $value): void
    {
        $setting = \App\Models\Setting::where('name', $name)->first();
        if (!$setting) {
            $setting = new \App\Models\Setting();
            $setting->name = $name;
        }
        $setting->value = $value;
        $setting->save();
    }

    /** Same shape as updateSocialSettings(): $request->fields keyed by
     *  existing field id (edit in place, or delete if flagged), plus
     *  $request->new_fields for ones added on this submit. A 'select'
     *  field also carries list_key - which Social Board Settings block
     *  (by its stable key) supplies its options. */
    public function updateReelCustomFields($request): array
    {
        $fields = (array) $request->input('fields', []);
        foreach ($fields as $id => $data) {
            $field = PortalReelCustomField::find($id);
            if (!$field) {
                continue;
            }
            if (!empty($data['delete'])) {
                $field->delete();
                continue;
            }
            $label = trim($data['label'] ?? '');
            $type  = $data['type'] ?? '';
            if ($label === '' || !array_key_exists($type, PortalReelCustomField::TYPES)) {
                continue;
            }
            $field->label    = $label;
            $field->type     = $type;
            $field->list_key = $type === PortalReelCustomField::TYPE_SELECT ? ($data['list_key'] ?? null) : null;
            $field->save();
        }

        $newFields = (array) $request->input('new_fields', []);
        $nextOrder = ((int) PortalReelCustomField::max('sort_order')) + 1;
        foreach ($newFields as $data) {
            $label = trim($data['label'] ?? '');
            $type  = $data['type'] ?? '';
            if ($label === '' || !array_key_exists($type, PortalReelCustomField::TYPES)) {
                continue;
            }
            PortalReelCustomField::create([
                'key'        => $this->uniqueFieldKey($label),
                'label'      => $label,
                'type'       => $type,
                'list_key'   => $type === PortalReelCustomField::TYPE_SELECT ? ($data['list_key'] ?? null) : null,
                'sort_order' => $nextOrder++,
            ]);
        }

        return $this->responseWithSuccess('Saved.', []);
    }

    private function uniqueFieldKey(string $label): string
    {
        $base = Str::slug($label, '_') ?: 'field';
        $key  = $base;
        $i    = 1;
        while (PortalReelCustomField::where('key', $key)->exists()) {
            $key = $base . '_' . (++$i);
        }
        return $key;
    }

    // ---- Daily plan / weekly goals --------------------------------------

    /** Whoever currently holds the Content responsibility — any number
     *  of people, per the admin's own clarification ("1 person, two or
     *  more"). This, not a hardcoded name list, is "the content team". */
    public function contentTeam()
    {
        $ids = PortalResponsibility::ownerStaffIdsForKey(PortalResponsibility::KEY_CONTENT);
        return Staff::whereIn('id', $ids)->orderBy('first_name')->get();
    }

    public function dailyPlansForWeek(string $weekStart)
    {
        $dates = collect(range(0, 6))->map(fn ($i) => Carbon::parse($weekStart)->addDays($i)->format('Y-m-d'));

        return PortalDailyPlan::with('staff')
            ->whereIn('date', $dates)
            ->get()
            ->groupBy('staff_id');
    }

    public function saveDailyPlan($request, int $staffId): array
    {
        PortalDailyPlan::updateOrCreate(
            ['staff_id' => $staffId, 'date' => $request->date],
            ['plan_text' => $request->plan_text]
        );
        return $this->responseWithSuccess('Saved.', []);
    }

    public function weeklyGoalsForWeek(string $weekStart)
    {
        return PortalWeeklyGoal::with('staff')->where('week_start', $weekStart)->get();
    }

    public function saveWeeklyGoal($request, int $staffId): array
    {
        PortalWeeklyGoal::updateOrCreate(
            ['staff_id' => $staffId, 'week_start' => $request->week_start],
            ['goal_text' => $request->goal_text, 'target_metric' => $request->target_metric]
        );
        return $this->responseWithSuccess('Saved.', []);
    }

    public function toggleGoalAchieved(int $id, int $staffId): array
    {
        $goal = PortalWeeklyGoal::where('id', $id)->where('staff_id', $staffId)->first();
        if (!$goal) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        $goal->achieved = !$goal->achieved;
        $goal->save();
        return $this->responseWithSuccess('Updated.', []);
    }
}
