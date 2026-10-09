<?php

namespace App\Repositories\Portal;

use App\Models\Portal\PortalAudienceMetric;
use App\Models\Portal\PortalCategoryTarget;
use App\Models\Portal\PortalDailyPlan;
use App\Models\Portal\PortalPageFix;
use App\Models\Portal\PortalReel;
use App\Models\Portal\PortalResponsibility;
use App\Models\Portal\PortalSetting;
use App\Models\Portal\PortalWeeklyGoal;
use App\Models\Staff\Staff;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Carbon\Carbon;

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
        return PortalReel::with(['suggestedBy', 'creator'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when($filters['format'] ?? null, fn ($q, $v) => $q->where('format', $v))
            ->orderByDesc('id')
            ->get();
    }

    public function showReel(int $id): ?PortalReel
    {
        return PortalReel::with(['suggestedBy', 'creator', 'reviewedBy', 'promptUpload', 'thumbUpload'])->find($id);
    }

    public function storeReel($request, int $userId): array
    {
        try {
            $promptUploadId = $request->hasFile('prompt_file') ? $this->UploadImageCreate($request->file('prompt_file'), 'uploads/portal-reels') : null;
            $thumbUploadId  = $request->hasFile('thumb_file') ? $this->UploadImageCreate($request->file('thumb_file'), 'uploads/portal-reels') : null;

            $reel = PortalReel::create([
                'title'            => $request->title,
                'format'           => $request->format ?: 'Reel',
                'category'         => $request->category,
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

        $reel->title        = $request->title;
        $reel->format       = $request->format ?: $reel->format;
        $reel->category     = $request->category;
        $reel->hook          = $request->hook;
        $reel->planned_date = $request->planned_date ?: null;
        $reel->drive_link   = $request->drive_link;
        $reel->prompt_text  = $request->prompt_text;
        $reel->prompt_link  = $request->prompt_link;
        $reel->note         = $request->note;
        $reel->save();

        return $this->responseWithSuccess('Saved.', []);
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

    public function destroyReel(int $id): array
    {
        $reel = PortalReel::find($id);
        if (!$reel) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        $reel->delete();
        return $this->responseWithSuccess('Removed.', []);
    }

    // ---- Month plan / category targets ------------------------------

    public function categoryTargets()
    {
        return PortalCategoryTarget::orderBy('category')->get();
    }

    /** This month's actual reel/carousel counts per category against the
     *  configured targets — business rule 10: counts from accepted
     *  onward, by planned_date falling in the current month. */
    public function monthPlan(): array
    {
        $targets = $this->categoryTargets();
        $start = now()->startOfMonth();
        $end   = now()->endOfMonth();

        $thisMonth = PortalReel::whereIn('status', PortalReel::COUNTS_TOWARD_MONTH)
            ->whereBetween('planned_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

        return $targets->map(function (PortalCategoryTarget $t) use ($thisMonth) {
            $forCategory = $thisMonth->where('category', $t->category);
            return [
                'category'        => $t->category,
                'reel_target'     => $t->reel_target,
                'reel_actual'     => $forCategory->where('format', 'Reel')->count(),
                'carousel_target' => $t->carousel_target,
                'carousel_actual' => $forCategory->where('format', 'Carousel')->count(),
            ];
        })->values()->all();
    }

    public function updateCategoryTargets($request): array
    {
        $categories = (array) $request->category;
        $reelTargets = (array) $request->reel_target;
        $carouselTargets = (array) $request->carousel_target;

        foreach ($categories as $i => $category) {
            $category = trim($category);
            if ($category === '') {
                continue;
            }
            PortalCategoryTarget::updateOrCreate(
                ['category' => $category],
                ['reel_target' => (int) ($reelTargets[$i] ?? 0), 'carousel_target' => (int) ($carouselTargets[$i] ?? 0)]
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
        return PortalPageFix::with('owner')->orderByRaw("status = 'fixed'")->orderByDesc('id')->get();
    }

    public function storePageFix($request, int $userId): array
    {
        PortalPageFix::create([
            'title'          => $request->title,
            'description'    => $request->description,
            'owner_staff_id' => $request->owner_staff_id ?: null,
            'created_by'     => $userId,
        ]);
        return $this->responseWithSuccess('Added.', []);
    }

    public function updatePageFixStatus(int $id, string $status, ?int $restrictToStaffId = null): array
    {
        if (!in_array($status, ['open', 'in_progress', 'fixed'], true)) {
            return $this->responseWithError('Not a valid status.', []);
        }

        $fix = PortalPageFix::find($id);
        if (!$fix) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if ($restrictToStaffId !== null && (int) $fix->owner_staff_id !== $restrictToStaffId) {
            return $this->responseWithError("This isn't assigned to you.", []);
        }

        $fix->status = $status;
        $fix->save();

        return $this->responseWithSuccess('Updated.', []);
    }

    public function updateSocialSettings($request): array
    {
        $platforms = array_values(array_filter(array_map('trim', (array) $request->social_platforms)));
        $metrics   = array_values(array_filter(array_map('trim', (array) $request->social_metrics)));
        $reelCategories = array_values(array_filter(array_map('trim', (array) $request->reel_categories)));

        $settings = PortalSetting::current();
        $settings->social_platforms = $platforms ?: PortalSetting::DEFAULT_PLATFORMS;
        $settings->social_metrics   = $metrics ?: PortalSetting::DEFAULT_SOCIAL_METRICS;
        $settings->reel_categories  = $reelCategories ?: PortalSetting::DEFAULT_REEL_CATEGORIES;
        $settings->save();

        return $this->responseWithSuccess('Saved.', []);
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
