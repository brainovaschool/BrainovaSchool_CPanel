<?php

namespace App\Http\Controllers\ClassContent;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\ClassContent\ClassContentModule;
use App\Repositories\ClassContent\ClassContentLessonRepository;
use App\Repositories\ClassContent\ClassContentModuleRepository;

class ClassContentLessonController extends Controller
{
    private $repo;
    private $moduleRepo;

    public function __construct(ClassContentLessonRepository $repo, ClassContentModuleRepository $moduleRepo)
    {
        $this->repo       = $repo;
        $this->moduleRepo = $moduleRepo;
    }

    private function teacherStaffId(): ?int
    {
        $user = Auth::user();
        if ($user && (int) $user->role_id === 5 && $user->staff) {
            return (int) $user->staff->id;
        }
        return null;
    }

    /** Same rule as ClassContentModuleController::authorizeModule() —
     *  lessons live inside their module's review state, so a Teacher can
     *  only manage them while the module itself is still theirs to edit. */
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

    public function index($moduleId)
    {
        $data['module'] = $this->moduleRepo->show($moduleId);
        if (!$data['module']) {
            return redirect()->route('class-content-module.index')->with('danger', ___('alert.not_found'));
        }
        $this->authorizeModule($data['module']);
        $data['lessons'] = $this->repo->forModule((int) $moduleId);
        $data['title']   = 'Lessons — ' . $data['module']->title;
        return view('class-content.lesson.index', compact('data'));
    }

    public function create($moduleId)
    {
        $data['module'] = $this->moduleRepo->show($moduleId);
        if (!$data['module']) {
            return redirect()->route('class-content-module.index')->with('danger', ___('alert.not_found'));
        }
        $this->authorizeModule($data['module'], true);
        $data['title'] = 'Add a Lesson';
        return view('class-content.lesson.create', compact('data'));
    }

    public function store(Request $request, $moduleId)
    {
        $module = $this->moduleRepo->show($moduleId);
        if (!$module) {
            return redirect()->route('class-content-module.index')->with('danger', ___('alert.not_found'));
        }
        $this->authorizeModule($module, true);

        $this->validateRequest($request);

        $result = $this->repo->store($request, (int) $moduleId);
        if ($result['status']) {
            return redirect()->route('class-content-module.lessons', $moduleId)->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($moduleId, $id)
    {
        $data['module'] = $this->moduleRepo->show($moduleId);
        $data['item']   = $this->repo->show($id);
        if (!$data['module'] || !$data['item']) {
            return redirect()->route('class-content-module.index')->with('danger', ___('alert.not_found'));
        }
        $this->authorizeModule($data['module'], true);
        $data['title'] = 'Edit Lesson';
        return view('class-content.lesson.edit', compact('data'));
    }

    public function update(Request $request, $moduleId, $id)
    {
        $module = $this->moduleRepo->show($moduleId);
        if (!$module) {
            return redirect()->route('class-content-module.index')->with('danger', ___('alert.not_found'));
        }
        $this->authorizeModule($module, true);

        $this->validateRequest($request);

        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('class-content-module.lessons', $moduleId)->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function delete($moduleId, $id)
    {
        $module = $this->moduleRepo->show($moduleId);
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

    private function validateRequest(Request $request): void
    {
        $request->validate([
            'title'       => 'required|string|max:150',
            'description' => 'nullable|string|max:3000',
            'video_url'   => 'nullable|string|max:500',
            'class_date'  => 'nullable|date',
            'sort_order'  => 'nullable|integer',
        ]);
    }
}
