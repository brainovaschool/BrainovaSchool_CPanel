<?php

namespace App\Repositories\Portal;

use App\Models\Portal\PortalGoal;
use App\Models\Portal\PortalTask;
use App\Services\Portal\PortalNotifier;
use App\Traits\ReturnFormatTrait;

class PortalGoalRepository
{
    use ReturnFormatTrait;

    /** Everything in one query, then assembled into a tree in memory -
     *  simpler and just as fast as a recursive query for the handful of
     *  goals a school actually plans in a year, and lets every row carry
     *  its own tasks (for progress roll-up) without N+1. */
    public function tree()
    {
        $all = PortalGoal::with(['owner', 'tasks.assignee'])
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        $childrenByParent = [];
        foreach ($all as $goal) {
            $childrenByParent[$goal->parent_id][] = $goal;
        }

        $build = function ($parentId) use (&$build, $childrenByParent) {
            $nodes = $childrenByParent[$parentId] ?? [];
            return collect($nodes)->map(function (PortalGoal $goal) use (&$build) {
                $goal->setRelation('childGoals', $build($goal->id));
                return $goal;
            });
        };

        return $build(null);
    }

    /** Top-level goals (only - a roadmap bar per sub-goal would be noise)
     *  that overlap the given year, with that year's Jan-Dec grid columns
     *  precomputed. $tree is the already-built tree() so this doesn't run
     *  a second query. */
    public function roadmapGoals(int $year, $tree)
    {
        return $tree->filter(function (PortalGoal $g) use ($year) {
            return $g->start_date && $g->end_date
                && $g->start_date->year <= $year && $g->end_date->year >= $year;
        })->map(function (PortalGoal $g) use ($year) {
            $startMonth = $g->start_date->year < $year ? 1 : $g->start_date->month;
            $endMonth   = $g->end_date->year > $year ? 12 : $g->end_date->month;
            $g->roadmapStartCol = $startMonth + 1; // +1: column 1 is the label column
            $g->roadmapEndCol   = $endMonth + 2;    // grid-column end is exclusive
            return $g;
        })->values();
    }

    public function store($request, ?int $userId): array
    {
        try {
            PortalGoal::create([
                'parent_id'      => $request->filled('parent_id') ? $request->parent_id : null,
                'title'          => $request->title,
                'description'    => $request->description,
                'owner_staff_id' => $request->owner_staff_id ?: null,
                'status'         => $request->status ?: PortalGoal::NOT_STARTED,
                'start_date'     => $request->start_date ?: null,
                'end_date'       => $request->end_date ?: null,
                'created_by'     => $userId,
            ]);
            return $this->responseWithSuccess('Saved.', []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function update($request, int $id): array
    {
        $goal = PortalGoal::find($id);
        if (!$goal) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        try {
            $goal->title          = $request->title;
            $goal->description    = $request->description;
            $goal->owner_staff_id = $request->owner_staff_id ?: null;
            $goal->status         = $request->status ?: PortalGoal::NOT_STARTED;
            $goal->start_date     = $request->start_date ?: null;
            $goal->end_date       = $request->end_date ?: null;
            $goal->save();
            return $this->responseWithSuccess('Saved.', []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    /** Deleting cascades to the whole sub-tree (DB FK) - any task already
     *  assigned anywhere in it is kept, just unlinked (goal_id -> null). */
    public function destroy($id): array
    {
        $goal = PortalGoal::find($id);
        if (!$goal) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        $goal->delete();
        return $this->responseWithSuccess('Deleted.', []);
    }

    /** A normal Task Board task (title/assignee/due date only - category,
     *  priority etc. all take their plain defaults, same as if a manager
     *  had left them blank on the full Assign a Task form), just tagged
     *  with goal_id so it also counts toward this goal's progress and
     *  shows up here. From here on it IS a normal task - claim, submit,
     *  review, everything - edited from the Task Board, not from here. */
    public function assignTask(int $goalId, $request, int $assignedByUserId): array
    {
        $goal = PortalGoal::find($goalId);
        if (!$goal) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $task = PortalTask::create([
            'goal_id'       => $goal->id,
            'title'         => $request->title,
            'assigned_to'   => $request->assigned_to,
            'assigned_by'   => $assignedByUserId,
            'assigned_date' => now()->format('Y-m-d'),
            'due_date'      => $request->due_date,
            'status'        => PortalTask::ASSIGNED,
        ]);

        $task->load('assignee');
        if ($task->assignee && $task->assignee->user_id) {
            PortalNotifier::send($task->assignee->user_id, 'New task assigned', $task->title, route('portal-tasks.show', $task->id));
        }

        return $this->responseWithSuccess('Task assigned — it now also appears in My Tasks.', ['id' => $task->id]);
    }
}
