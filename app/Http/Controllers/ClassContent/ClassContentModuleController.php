<?php

namespace App\Http\Controllers\ClassContent;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Academic\SubjectAssignChildren;
use App\Models\ClassContent\ClassContentModule;
use App\Repositories\ClassContent\ClassContentModuleRepository;

/** Teacher authoring: create/edit a module (and, via
 *  ClassContentLessonController, its lessons) and submit it for review.
 *  Shared by any role with class_content permissions — Admin sees/edits
 *  everything via its usual role-1 bypass, a Teacher only ever sees
 *  modules they created, scoped to classes+subjects they're actually
 *  assigned to teach. */
class ClassContentModuleController extends Controller
{
    private $repo;

    public function __construct(ClassContentModuleRepository $repo)
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

    /** A Teacher only ever touches their own modules, and only while it's
     *  still theirs to edit (draft, or kicked back for changes) — once it's
     *  off for review/approval it's locked until it comes back to them.
     *  Admin (teacherStaffId === null here) always passes, matching the
     *  rest of this controller's "admin sees/edits everything" design. */
    private function authorizeModule(ClassContentModule $module, bool $forEdit = false): void
    {
        $staffId = $this->teacherStaffId();
        if ($staffId === null) {
            return;
        }
        if ((int) $module->created_by !== $staffId) {
            abort(403, "This isn't your class content.");
        }
        if ($forEdit && !in_array($module->review_status, [ClassContentModule::DRAFT, ClassContentModule::CHANGES_REQUESTED], true)) {
            abort(403, "This has already been submitted for review — you can't edit it until it's sent back to you.");
        }
    }

    /** Flattens assignableOptions() into one consistent shape for the form:
     *  a Teacher's combos already carry a single subject_id each; Admin's
     *  combos carry a full subjects list per class/section, so those get
     *  cross-joined into the same per-subject shape here. */
    private function comboOptions(?int $staffId)
    {
        return $this->assignableOptions($staffId)->flatMap(function ($a) {
            if (isset($a['subjects'])) {
                return collect($a['subjects'])->map(fn ($subject) => [
                    'classes_id'   => $a['classes_id'],
                    'section_id'   => $a['section_id'],
                    'class_name'   => $a['class_name'],
                    'section_name' => $a['section_name'],
                    'subject_id'   => $subject->id,
                    'subject_name' => $subject->name,
                ]);
            }
            return [$a];
        })->values();
    }

    /** Every (classes_id, section_id, subject_id) combo this teacher is
     *  actually assigned to — what they're allowed to create a module
     *  for. Admin isn't scoped at all (sees every class/subject). */
    private function assignableOptions(?int $staffId)
    {
        if ($staffId === null) {
            return \App\Models\Academic\SubjectAssign::active()->with(['class', 'section'])->get()
                ->map(fn ($a) => ['classes_id' => $a->classes_id, 'section_id' => $a->section_id, 'class_name' => optional($a->class)->name, 'section_name' => optional($a->section)->name, 'subjects' => \App\Models\Academic\Subject::active()->orderBy('name')->get()])
                ->unique(fn ($a) => $a['classes_id'] . '-' . $a['section_id']);
        }

        return SubjectAssignChildren::where('staff_id', $staffId)
            ->with(['subject', 'subjectAssign.class', 'subjectAssign.section'])
            ->get()
            ->filter(fn ($a) => $a->subjectAssign)
            ->map(fn ($a) => [
                'classes_id'   => $a->subjectAssign->classes_id,
                'section_id'   => $a->subjectAssign->section_id,
                'class_name'   => optional($a->subjectAssign->class)->name,
                'section_name' => optional($a->subjectAssign->section)->name,
                'subject_id'   => $a->subject_id,
                'subject_name' => optional($a->subject)->name,
            ]);
    }

    public function index()
    {
        $staffId = $this->teacherStaffId();
        $data['modules'] = $staffId !== null
            ? $this->repo->forTeacher($staffId)
            : ClassContentModule::with(['class', 'section', 'subject', 'creator'])->withCount('lessons')->orderByDesc('id')->paginate(\App\Enums\Settings::PAGINATE);
        $data['counts']  = $this->repo->statusCounts($staffId);
        $data['title']   = 'Class Content';
        return view('class-content.module.index', compact('data'));
    }

    public function create()
    {
        $data['title']   = 'Add a Module';
        $data['options'] = $this->comboOptions($this->teacherStaffId());
        return view('class-content.module.create', compact('data'));
    }

    public function store(Request $request)
    {
        $this->validateRequest($request);

        $staffId = $this->teacherStaffId();
        if ($staffId !== null && !$this->comboOptions($staffId)->contains(
            fn ($o) => $o['classes_id'] == $request->classes_id
                && (string) ($o['section_id'] ?? '') === (string) ($request->section_id ?? '')
                && $o['subject_id'] == $request->subject_id
        )) {
            return back()->withInput()->with('danger', "You can only add content for a class, section and subject you're assigned to teach.");
        }

        $staffId = $staffId ?? Auth::user()->staff?->id;
        $result  = $this->repo->store($request, $staffId !== null ? (int) $staffId : null);

        if ($result['status']) {
            return redirect()->route('class-content-module.lessons', $result['data']['id'])->with('success', 'Module created — now add its lessons below.');
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['item'] = $this->repo->show($id);
        if (!$data['item']) {
            return redirect()->route('class-content-module.index')->with('danger', ___('alert.not_found'));
        }
        $this->authorizeModule($data['item'], true);
        $data['options'] = $this->comboOptions($this->teacherStaffId());
        $data['title']   = 'Edit Module';
        return view('class-content.module.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $module = ClassContentModule::find($id);
        if (!$module) {
            return redirect()->route('class-content-module.index')->with('danger', ___('alert.not_found'));
        }
        $this->authorizeModule($module, true);

        $this->validateRequest($request);

        $staffId = $this->teacherStaffId();
        if ($staffId !== null && !$this->comboOptions($staffId)->contains(
            fn ($o) => $o['classes_id'] == $request->classes_id
                && (string) ($o['section_id'] ?? '') === (string) ($request->section_id ?? '')
                && $o['subject_id'] == $request->subject_id
        )) {
            return back()->withInput()->with('danger', "You can only add content for a class, section and subject you're assigned to teach.");
        }

        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('class-content-module.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function delete($id)
    {
        $module = ClassContentModule::find($id);
        if (!$module) {
            return response()->json([___('alert.not_found'), 'error', ___('alert.oops'), ___('alert.OK')]);
        }
        $this->authorizeModule($module, true);

        $result = $this->repo->destroy($id);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.deleted'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    public function submit($id)
    {
        $module = ClassContentModule::find($id);
        if (!$module) {
            return redirect()->route('class-content-module.index')->with('danger', ___('alert.not_found'));
        }
        $this->authorizeModule($module, true);

        $result = $this->repo->submit((int) $id);

        return redirect()->route('class-content-module.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
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
