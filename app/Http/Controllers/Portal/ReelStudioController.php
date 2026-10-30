<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Portal\ReelStudioRepository;

class ReelStudioController extends Controller
{
    private $repo;

    public function __construct(ReelStudioRepository $repo)
    {
        $this->repo = $repo;
    }

    private function isManager(): bool
    {
        return hasPermission('portal_manage');
    }

    /** Non-managers may act on their own projects, or a project that
     *  came from a Content Pool/Reels suggestion they were assigned. */
    private function authorizeProject($project): bool
    {
        $staff = Auth::user()->staff;
        return $this->repo->authorizeProject($project, (int) Auth::id(), $staff ? (int) $staff->id : null, $this->isManager());
    }

    /** No more standalone list to browse - every project is reached
     *  either from its reel (Open/Reel Setup) or, for a brand new one
     *  not tied to a suggestion, straight from here into the setup form. */
    public function index()
    {
        return redirect()->route('portal-social-reel-studio.create');
    }

    private function projectRules(): array
    {
        return [
            'title'                   => 'required|string|max:150',
            'hook'                    => 'nullable|string|max:1000',
            'description'             => 'nullable|string|max:2000',
            'format'                  => 'required|in:vertical,horizontal',
            'target_duration_seconds' => 'nullable|integer|min:5|max:600',
            'scene_length_seconds'    => 'nullable|integer|min:1|max:10',
            'cover_file'              => 'nullable|file|image|max:10240',
            'custom_fields'           => 'nullable|array',
            'canva_link'              => 'nullable|url|max:500',
            'drive_link'              => 'nullable|url|max:500',
        ];
    }

    public function create()
    {
        $data['title'] = 'New Reel';
        return view('portal.social.reel-studio.create', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate($this->projectRules());

        $result = $this->repo->storeProject($request, (int) Auth::id());
        if ($result['status']) {
            return redirect()->route('portal-social-reel-studio.show', $result['data']['id'])->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function show($id)
    {
        $project = $this->repo->find((int) $id);
        if (!$project || !$this->authorizeProject($project)) {
            abort(404);
        }

        $data['project']             = $project;
        $data['customFields']        = $this->repo->customFields();
        $data['sceneCount']          = $this->repo->sceneCount($project);
        $data['initialScenesJson']   = $this->scenesForJs($project);
        $data['availableCharsJson']  = $this->charactersForJs($project->involvedCharacters());
        $data['title']               = $project->title;
        return view('portal.social.reel-studio.show', compact('data'));
    }

    /** This project's scenes, ready to JSON-encode for the Scene Builder's
     *  JS — kept out of the Blade file on purpose. The Start/End scenes
     *  are singled out from the numbered content scenes so the page can
     *  render them as their own fixed boxes. */
    private function scenesForJs($project): array
    {
        $out = ['start' => null, 'scenes' => [], 'end' => null];

        foreach ($project->scenes as $scene) {
            $data = [
                'id'          => $scene->id,
                'title'       => $scene->title,
                'description' => $scene->description,
                'dialogue'    => $scene->dialogue,
                'characters'  => $scene->character_ids ?: [],
            ];
            if ($scene->scene_type === \App\Models\Portal\ReelScene::TYPE_START) {
                $out['start'] = $data;
            } elseif ($scene->scene_type === \App\Models\Portal\ReelScene::TYPE_END) {
                $out['end'] = $data;
            } else {
                $out['scenes'][] = $data;
            }
        }

        return $out;
    }

    private function charactersForJs($characters): array
    {
        $out = [];
        foreach ($characters as $character) {
            $out[] = ['id' => $character->id, 'name' => $character->name];
        }
        return $out;
    }

    public function update(Request $request, $id)
    {
        $project = $this->repo->find((int) $id);
        if (!$project || !$this->authorizeProject($project)) {
            abort(404);
        }

        $request->validate($this->projectRules());

        $result = $this->repo->updateProject($request, (int) $id);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    /** "Create Scenes" button — the browser's confirm() already warned the
     *  teacher this deletes whatever scenes currently exist. */
    public function createScenesFromZero($id)
    {
        $project = $this->repo->find((int) $id);
        if (!$project || !$this->authorizeProject($project)) {
            abort(404);
        }

        $result = $this->repo->createScenesFromZero((int) $id);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    /** "Send Prompt for Approval" — submits the script download as this
     *  reel's prompt link and notifies whoever reviews it. */
    public function sendPromptForApproval($id)
    {
        $project = $this->repo->find((int) $id);
        if (!$project || !$this->authorizeProject($project)) {
            abort(404);
        }

        $result = $this->repo->sendPromptForApproval((int) $id);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function destroy($id)
    {
        $project = $this->repo->find((int) $id);
        if (!$project || !$this->authorizeProject($project)) {
            abort(404);
        }

        $result = $this->repo->destroyProject((int) $id);
        return redirect()->route('portal-social-reel-studio.index')->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    /**
     * Saves the scene boxes and, on success, streams the compiled script
     * document straight back as the response — no redirect, so the file
     * just downloads while the teacher stays on the page.
     */
    public function storeScenes(Request $request, $projectId)
    {
        $project = $this->repo->find((int) $projectId);
        if (!$project || !$this->authorizeProject($project)) {
            abort(404);
        }

        $request->validate(['cover_file' => 'nullable|file|image|max:10240']);

        $result = $this->repo->saveScenes($request, (int) $projectId);
        if (!$result['status']) {
            return back()->with('danger', $result['message']);
        }

        $filename = \Illuminate\Support\Str::slug($project->title) . '-script.txt';
        return response()->streamDownload(function () use ($result) {
            echo $result['data']['document'];
        }, $filename, ['Content-Type' => 'text/plain']);
    }

    /** Re-downloads the already-saved script without resubmitting the
     *  scene boxes. */
    public function downloadScript($projectId)
    {
        $project = $this->repo->find((int) $projectId);
        if (!$project || !$this->authorizeProject($project)) {
            abort(404);
        }

        $document = $this->repo->scriptDocument((int) $projectId);
        if (!$document) {
            abort(404, 'Save scenes first.');
        }

        $filename = \Illuminate\Support\Str::slug($project->title) . '-script.txt';
        return response()->streamDownload(function () use ($document) {
            echo $document;
        }, $filename, ['Content-Type' => 'text/plain']);
    }

    /** One scene's own standalone script. */
    public function downloadSceneScript($projectId, $sceneId)
    {
        $project = $this->repo->find((int) $projectId);
        if (!$project || !$this->authorizeProject($project)) {
            abort(404);
        }

        $document = $this->repo->sceneScriptDocument((int) $projectId, (int) $sceneId);
        if (!$document) {
            abort(404);
        }

        $scene    = $project->scenes->firstWhere('id', (int) $sceneId);
        $filename = \Illuminate\Support\Str::slug($project->title) . '-scene-' . ($scene->sort_order ?? $sceneId) . '-script.txt';
        return response()->streamDownload(function () use ($document) {
            echo $document;
        }, $filename, ['Content-Type' => 'text/plain']);
    }

    /**
     * AJAX endpoint (not a classic form submit) so the Scene Builder page
     * can show a loading state with a real Cancel button — the client can
     * abort waiting on the fetch() and let the teacher build scenes
     * manually instead, without being stuck behind a full-page redirect.
     */
    public function aiGenerateScenes(Request $request, $projectId)
    {
        $project = $this->repo->find((int) $projectId);
        if (!$project || !$this->authorizeProject($project)) {
            return response()->json(['status' => false, 'message' => ___('alert.not_found')], 404);
        }

        set_time_limit(120); // the AI gateway can fall back across accounts, which can take longer than the host's default cap

        $result = $this->repo->aiGenerateScenes((int) $projectId);
        return response()->json($result, $result['status'] ? 200 : 422);
    }

    /** AJAX endpoint for the "Create Cover" button - same reasoning as
     *  aiGenerateScenes() above, a real loading state instead of a
     *  full-page wait while the image generates. */
    public function generateCoverImage($projectId)
    {
        $project = $this->repo->find((int) $projectId);
        if (!$project || !$this->authorizeProject($project)) {
            return response()->json(['status' => false, 'message' => ___('alert.not_found')], 404);
        }

        set_time_limit(180); // image generation plus the gateway's own account fallback can run well past a text-only request

        $result = $this->repo->generateCoverImage((int) $projectId);
        return response()->json($result, $result['status'] ? 200 : 422);
    }
}
