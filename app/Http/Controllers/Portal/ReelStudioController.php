<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Portal\ReelCharacter;
use App\Models\Upload;
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

    /** Non-managers may only act on their own projects. */
    private function authorizeProject($project): bool
    {
        return $project && ($this->isManager() || (int) $project->created_by === (int) Auth::id());
    }

    public function index()
    {
        $data['projects'] = $this->repo->projects((int) Auth::id(), $this->isManager());
        $data['title']    = 'Reel Studio';
        return view('portal.social.reel-studio.index', compact('data'));
    }

    private function projectRules(): array
    {
        return [
            'title'                   => 'required|string|max:150',
            'hook'                    => 'nullable|string|max:1000',
            'description'             => 'nullable|string|max:2000',
            'format'                  => 'required|in:vertical,horizontal',
            'target_duration_seconds' => 'nullable|integer|min:5|max:600',
            'character_ids'           => 'nullable|array',
            'character_ids.*'         => 'integer|exists:reel_characters,id',
        ];
    }

    public function create()
    {
        $data['characters'] = ReelCharacter::active()->orderBy('sort_order')->orderBy('name')->get();
        $data['title']      = 'New Reel';
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

        $characters = ReelCharacter::active()->orderBy('sort_order')->orderBy('name')->get();

        $data['project']              = $project;
        $data['characters']           = $characters;
        $data['initialScenesJson']    = $this->scenesForJs($project);
        $data['availableCharsJson']   = $this->charactersForJs($characters);
        $data['title']                = $project->title;
        return view('portal.social.reel-studio.show', compact('data'));
    }

    /** Plain-array shape of this project's scenes, ready to JSON-encode for
     *  the Scene Builder's JS — kept out of the Blade file on purpose. */
    private function scenesForJs($project): array
    {
        $scenes = [];
        foreach ($project->scenes as $scene) {
            $characters = [];
            foreach ($scene->sceneCharacters as $sc) {
                $characters[] = [
                    'character_id'  => $sc->reel_character_id,
                    'action_points' => $sc->action_points,
                    'dialogue'      => $sc->dialogue,
                ];
            }
            $scenes[] = [
                'id'               => $scene->id,
                'duration_seconds' => $scene->duration_seconds,
                'description'      => $scene->description,
                'characters'       => $characters,
            ];
        }
        return $scenes;
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

    public function destroy($id)
    {
        $project = $this->repo->find((int) $id);
        if (!$project || !$this->authorizeProject($project)) {
            abort(404);
        }

        $result = $this->repo->destroyProject((int) $id);
        return redirect()->route('portal-social-reel-studio.index')->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function storeScenes(Request $request, $projectId)
    {
        $project = $this->repo->find((int) $projectId);
        if (!$project || !$this->authorizeProject($project)) {
            abort(404);
        }

        $result = $this->repo->saveScenes($request, (int) $projectId);
        return redirect()->route('portal-social-reel-studio.show', $projectId)->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function aiGenerateScenes($projectId)
    {
        $project = $this->repo->find((int) $projectId);
        if (!$project || !$this->authorizeProject($project)) {
            abort(404);
        }

        set_time_limit(120); // the AI gateway can fall back across accounts, which can take longer than the host's default cap

        $result = $this->repo->aiGenerateScenes((int) $projectId);
        return redirect()->route('portal-social-reel-studio.show', $projectId)->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function generatePrompt($projectId, $sceneId)
    {
        $project = $this->repo->find((int) $projectId);
        if (!$project || !$this->authorizeProject($project)) {
            abort(404);
        }
        if (!$project->scenes->contains('id', (int) $sceneId)) {
            abort(404);
        }

        $result = $this->repo->generatePrompt((int) $sceneId);
        return redirect()->route('portal-social-reel-studio.show', $projectId)->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function downloadScenePrompt($projectId, $sceneId)
    {
        $project = $this->repo->find((int) $projectId);
        if (!$project || !$this->authorizeProject($project)) {
            abort(404);
        }

        $scene = $project->scenes->firstWhere('id', (int) $sceneId);
        if (!$scene || !$scene->generated_prompt) {
            abort(404, 'Generate the prompt for this scene first.');
        }

        $filename = \Illuminate\Support\Str::slug($project->title) . '-scene-' . $scene->sort_order . '-prompt.txt';

        return response()->streamDownload(function () use ($scene) {
            echo $scene->generated_prompt;
        }, $filename, ['Content-Type' => 'text/plain']);
    }

    public function downloadCharacterPortrait($projectId, $sceneId, $characterId)
    {
        $project = $this->repo->find((int) $projectId);
        if (!$project || !$this->authorizeProject($project)) {
            abort(404);
        }
        if (!$project->scenes->contains('id', (int) $sceneId)) {
            abort(404);
        }

        $character = ReelCharacter::find((int) $characterId);
        if (!$character || !$character->portrait_upload_id) {
            abort(404);
        }

        $upload = Upload::find($character->portrait_upload_id);
        if (!$upload) {
            abort(404);
        }

        $filename = \Illuminate\Support\Str::slug($character->name) . '-portrait.' . pathinfo($upload->path, PATHINFO_EXTENSION);

        if (setting('file_system') == 's3') {
            return \Illuminate\Support\Facades\Storage::disk('s3')->download($upload->path, $filename);
        }
        return response()->download(public_path($upload->path), $filename);
    }
}
