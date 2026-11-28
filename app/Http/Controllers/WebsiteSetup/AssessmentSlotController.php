<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\WebsiteSetup\AssessmentSlot\AssessmentSlotRequest;
use App\Repositories\WebsiteSetup\AssessmentSlotRepository;
use Illuminate\Http\Request;

// Admin management of the time slots visitors pick from on the public
// "book an assessment" page. Deliberately gated the same simple way as
// the rest of the Assessment System's own admin pages (AssessmentSystemController's
// deployForm/accessForm) - a direct admin/superadmin role check - rather
// than the permission-seeder + FeatureCheck system Trial Slot uses,
// since this is part of Assessment System administration, not the
// public Course Catalogue.
class AssessmentSlotController extends Controller
{
    private $repo;

    public function __construct(AssessmentSlotRepository $repo)
    {
        $this->repo = $repo;
    }

    private function guard(Request $request)
    {
        if (! in_array((int) $request->user()?->role_id, [RoleEnum::SUPERADMIN, RoleEnum::ADMIN], true)) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->guard($request);
        $data['slots'] = $this->repo->getAll();
        $data['title'] = 'Assessment Slots';
        return view('website-setup.assessment-slot.index', compact('data'));
    }

    public function create(Request $request)
    {
        $this->guard($request);
        $data['title'] = 'Add Assessment Slot';
        return view('website-setup.assessment-slot.create', compact('data'));
    }

    public function store(AssessmentSlotRequest $request)
    {
        $this->guard($request);
        $result = $this->repo->store($request);
        if ($result['status']) {
            return redirect()->route('assessment-slot.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit(Request $request, $id)
    {
        $this->guard($request);
        $data['slot'] = $this->repo->show($id);
        if (! $data['slot']) {
            return redirect()->route('assessment-slot.index')->with('danger', ___('alert.not_found'));
        }
        $data['title'] = 'Edit Assessment Slot';
        return view('website-setup.assessment-slot.edit', compact('data'));
    }

    public function update(AssessmentSlotRequest $request, $id)
    {
        $this->guard($request);
        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('assessment-slot.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function delete(Request $request, $id)
    {
        $this->guard($request);
        $result = $this->repo->destroy($id);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.deleted'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    public function bulkDelete(Request $request)
    {
        $this->guard($request);
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $result = $this->repo->bulkDestroy($ids);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.deleted'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    public function bulkStatus(Request $request)
    {
        $this->guard($request);
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $result = $this->repo->bulkStatus($ids, (int) $request->input('status', 1));
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.updated'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    /** The "generate a week's worth of slots at once" form. */
    public function bulkCreateForm(Request $request)
    {
        $this->guard($request);
        $data['title'] = 'Bulk Add Assessment Slots';
        return view('website-setup.assessment-slot.bulk-create', compact('data'));
    }

    public function bulkGenerate(Request $request)
    {
        $this->guard($request);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'weekdays' => 'required|array|min:1',
            'weekdays.*' => 'integer|between:0,6',
            'times' => 'required|string',
            'capacity' => 'required|integer|min:1|max:500',
            'status' => 'required|in:0,1',
            'note' => 'nullable|string|max:191',
        ]);

        $result = $this->repo->bulkGenerate($request);

        $message = "Created {$result['created']} slot(s).";
        if ($result['skipped']) {
            $message .= " Skipped {$result['skipped']} that already existed.";
        }
        if (!empty($result['badTimes'])) {
            $message .= ' Could not read this as a time, so it was skipped: '.implode(', ', $result['badTimes']).' (use 24-hour HH:MM, e.g. 14:00).';
        }

        return redirect()->route('assessment-slot.index')->with($result['created'] ? 'success' : 'danger', $message);
    }

    /** One click, no selection needed - see repo's cleanupPast() for what this actually does to leads that pointed at a removed slot. */
    public function cleanupPast(Request $request)
    {
        $this->guard($request);
        $result = $this->repo->cleanupPast();

        $message = $result['deleted']
            ? "Removed {$result['deleted']} past slot(s)."
            : 'Nothing to clean up — no past slots found.';

        return redirect()->route('assessment-slot.index')->with('success', $message);
    }

    /** Bulk edit from the index page's selection bar - capacity and/or status only. */
    public function bulkEdit(Request $request)
    {
        $this->guard($request);
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $result = $this->repo->bulkEditFields($ids, $request->input('capacity'), $request->input('status'));
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.updated'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }
}
