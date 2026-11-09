<?php

namespace App\Http\Controllers\ClassContent;

use App\Http\Controllers\Controller;
use App\Models\ClassContent\ClassContentUnit;
use App\Repositories\ClassContent\ClassContentUnitRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Units have no review workflow of their own - just a teacher-owned
 *  label a Module can sit under - so unlike Module there's no separate
 *  create/edit page: the forms live inline on the Class Content dashboard
 *  (see class-content.module.index), and this controller is only ever
 *  posted to from there. */
class ClassContentUnitController extends Controller
{
    private $repo;

    public function __construct(ClassContentUnitRepository $repo)
    {
        $this->repo = $repo;
    }

    private function teacherStaffId(): ?int
    {
        $user = Auth::user();
        if ($user && (int) $user->role_id === 5 && $user->staff) {
            return (int) $user->staff->id;
        }
        return null;
    }

    private function authorizeUnit(ClassContentUnit $unit): void
    {
        $staffId = $this->teacherStaffId();
        if ($staffId !== null && (int) $unit->created_by !== $staffId) {
            abort(403, "This isn't your class content.");
        }
    }

    public function store(Request $request)
    {
        $this->validateRequest($request);

        $staffId = $this->teacherStaffId();
        $result  = $this->repo->store($request, $staffId ?? Auth::user()->staff?->id);

        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function update(Request $request, $id)
    {
        $unit = $this->repo->show((int) $id);
        if (!$unit) {
            return back()->with('danger', ___('alert.not_found'));
        }
        $this->authorizeUnit($unit);
        $this->validateRequest($request);

        $result = $this->repo->update($request, (int) $id);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function destroy($id)
    {
        $unit = $this->repo->show((int) $id);
        if (!$unit) {
            return response()->json([___('alert.not_found'), 'error', ___('alert.oops'), ___('alert.OK')]);
        }
        $this->authorizeUnit($unit);

        $result = $this->repo->destroy((int) $id);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.deleted'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    private function validateRequest(Request $request): void
    {
        $request->validate([
            'classes_id'  => 'required|exists:classes,id',
            'section_id'  => 'nullable|exists:sections,id',
            'subject_id'  => 'required|exists:subjects,id',
            'title'       => 'required|string|max:150',
            'description' => 'nullable|string|max:3000',
            'sort_order'  => 'nullable|integer',
        ]);
    }
}
