@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php($project = $data['project'])
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">{{ $project->title }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('portal-social-reel-studio.index') }}">Reel Studio</a></li>
                        <li class="breadcrumb-item">{{ $project->title }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card mb-3">
            <div class="card-body">
                <form action="{{ route('portal-social-reel-studio.update', $project->id) }}" method="post" class="row g-2 align-items-end">
                    @csrf
                    @method('PUT')
                    <div class="col-12 col-md-5">
                        <label class="form-label">Reel title</label>
                        <input type="text" name="title" class="form-control ot-input" value="{{ $project->title }}" required>
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label">Topic</label>
                        <input type="text" name="topic" class="form-control ot-input" value="{{ $project->topic }}">
                    </div>
                    <div class="col-12 col-md-2">
                        <button class="btn ot-btn-primary w-100">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card ot-card mb-3">
            <div class="card-header">
                <h4 class="mb-0">Scenes</h4>
                <p class="text-secondary mb-0">Add as many scenes as you need, pick up to 3 characters each, and write what happens, what each character does, and what they say.</p>
            </div>
            <div class="card-body">
                <form id="scenesForm" action="{{ route('portal-social-reel-studio.scenes.store', $project->id) }}" method="post">
                    @csrf
                    <div id="scenesContainer" class="d-flex flex-column gap-3"></div>
                    <button type="button" id="addSceneBtn" class="btn ot-btn-primary mt-3">+ Add Scene</button>
                    <input type="hidden" name="scenes_json" id="scenesJson">
                    <div class="mt-3">
                        <button type="submit" class="btn btn-lg ot-btn-primary">Save Scenes</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($project->scenes->isNotEmpty())
            <div class="card ot-card">
                <div class="card-header">
                    <h4 class="mb-0">Scene Prompts</h4>
                    <p class="text-secondary mb-0">Save your scenes above first, then generate each one's prompt here.</p>
                </div>
                <div class="card-body">
                    @foreach ($project->scenes as $scene)
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h5 class="mb-0">Scene {{ $scene->sort_order }} — {{ $scene->duration_seconds }}s</h5>
                                    <form action="{{ route('portal-social-reel-studio.scenes.generate-prompt', [$project->id, $scene->id]) }}" method="post">
                                        @csrf
                                        <button class="btn btn-sm ot-btn-primary">{{ $scene->generated_prompt ? 'Regenerate Prompt' : 'Generate Prompt' }}</button>
                                    </form>
                                </div>

                                @if ($scene->generated_prompt)
                                    <textarea readonly rows="12" class="form-control mt-3" id="promptText{{ $scene->id }}">{{ $scene->generated_prompt }}</textarea>
                                    <div class="mt-2 d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary copy-prompt-btn" data-target="promptText{{ $scene->id }}">Copy</button>
                                        <a href="{{ route('portal-social-reel-studio.scenes.download-prompt', [$project->id, $scene->id]) }}" class="btn btn-sm btn-outline-secondary">Download .txt</a>
                                        @foreach ($scene->sceneCharacters as $sc)
                                            @if ($sc->character && $sc->character->portrait_upload_id)
                                                <a href="{{ route('portal-social-reel-studio.scenes.download-portrait', [$project->id, $scene->id, $sc->character->id]) }}" class="btn btn-sm btn-outline-secondary">Download {{ $sc->character->name }} portrait</a>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-secondary mt-2 mb-0">No prompt generated yet for this scene.</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @php
        $initialScenesForJs = [];
        foreach ($project->scenes as $scene) {
            $characters = [];
            foreach ($scene->sceneCharacters as $sc) {
                $characters[] = [
                    'character_id'  => $sc->reel_character_id,
                    'action_points' => $sc->action_points,
                    'dialogue'      => $sc->dialogue,
                ];
            }
            $initialScenesForJs[] = [
                'id'               => $scene->id,
                'duration_seconds' => $scene->duration_seconds,
                'description'      => $scene->description,
                'characters'       => $characters,
            ];
        }

        $availableCharactersForJs = [];
        foreach ($data['characters'] as $character) {
            $availableCharactersForJs[] = ['id' => $character->id, 'name' => $character->name];
        }
    @endphp

    <script>
        (function () {
            var initialScenes = @json($initialScenesForJs);
            var availableCharacters = @json($availableCharactersForJs);
            var MAX_CHARACTERS = 3;

            var container = document.getElementById('scenesContainer');
            var addSceneBtn = document.getElementById('addSceneBtn');
            var scenesJsonInput = document.getElementById('scenesJson');
            var form = document.getElementById('scenesForm');

            function characterOptions(selectedId) {
                return availableCharacters.map(function (c) {
                    return '<option value="' + c.id + '"' + (String(c.id) === String(selectedId) ? ' selected' : '') + '>' + c.name + '</option>';
                }).join('');
            }

            function addCharacterRow(charsContainer, charData) {
                charData = charData || { character_id: '', action_points: '', dialogue: '' };

                if (charsContainer.querySelectorAll('.character-row').length >= MAX_CHARACTERS) {
                    return;
                }

                var row = document.createElement('div');
                row.className = 'character-row border rounded p-2 mb-2';
                row.innerHTML =
                    '<div class="row g-2">' +
                        '<div class="col-12 col-md-4">' +
                            '<select class="form-control form-control-sm char-select">' +
                                '<option value="">— Choose character —</option>' +
                                characterOptions(charData.character_id) +
                            '</select>' +
                        '</div>' +
                        '<div class="col-12 col-md-4">' +
                            '<textarea class="form-control form-control-sm char-action" rows="2" placeholder="What this character does"></textarea>' +
                        '</div>' +
                        '<div class="col-12 col-md-3">' +
                            '<textarea class="form-control form-control-sm char-dialogue" rows="2" placeholder="What this character says (leave empty if silent)"></textarea>' +
                        '</div>' +
                        '<div class="col-12 col-md-1 text-end">' +
                            '<button type="button" class="btn btn-sm btn-outline-danger char-remove">&times;</button>' +
                        '</div>' +
                    '</div>';

                row.querySelector('.char-action').value = charData.action_points || '';
                row.querySelector('.char-dialogue').value = charData.dialogue || '';
                row.querySelector('.char-remove').addEventListener('click', function () { row.remove(); });

                charsContainer.appendChild(row);
            }

            function addScene(sceneData) {
                sceneData = sceneData || { id: null, duration_seconds: 8, description: '', characters: [] };

                var card = document.createElement('div');
                card.className = 'card p-3';
                card.dataset.sceneId = sceneData.id || '';
                card.innerHTML =
                    '<div class="row g-2 mb-2">' +
                        '<div class="col-12 col-md-3">' +
                            '<label class="form-label">Duration (seconds)</label>' +
                            '<input type="number" min="1" class="form-control form-control-sm scene-duration" value="' + (sceneData.duration_seconds || 8) + '">' +
                        '</div>' +
                        '<div class="col-12 col-md-9 text-end">' +
                            '<button type="button" class="btn btn-sm btn-outline-danger scene-remove">Remove Scene</button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="mb-2">' +
                        '<label class="form-label">Scene description (setting, background, camera, visual events)</label>' +
                        '<textarea class="form-control form-control-sm scene-description" rows="2"></textarea>' +
                    '</div>' +
                    '<label class="form-label">Characters in this scene (up to 3)</label>' +
                    '<div class="scene-characters"></div>' +
                    '<button type="button" class="btn btn-sm ot-btn-primary mt-1 add-character-btn">+ Add Character</button>';

                card.querySelector('.scene-description').value = sceneData.description || '';
                card.querySelector('.scene-remove').addEventListener('click', function () { card.remove(); });

                var charsContainer = card.querySelector('.scene-characters');
                card.querySelector('.add-character-btn').addEventListener('click', function () {
                    addCharacterRow(charsContainer);
                });

                (sceneData.characters || []).forEach(function (c) { addCharacterRow(charsContainer, c); });

                container.appendChild(card);
            }

            addSceneBtn.addEventListener('click', function () { addScene(); });

            if (initialScenes.length) {
                initialScenes.forEach(addScene);
            } else {
                addScene();
            }

            form.addEventListener('submit', function () {
                var scenes = [];
                container.querySelectorAll(':scope > .card').forEach(function (card) {
                    var characters = [];
                    card.querySelectorAll('.character-row').forEach(function (row) {
                        var characterId = row.querySelector('.char-select').value;
                        if (!characterId) return;
                        characters.push({
                            character_id: parseInt(characterId, 10),
                            action_points: row.querySelector('.char-action').value,
                            dialogue: row.querySelector('.char-dialogue').value
                        });
                    });

                    scenes.push({
                        id: card.dataset.sceneId || null,
                        duration_seconds: parseInt(card.querySelector('.scene-duration').value, 10) || 8,
                        description: card.querySelector('.scene-description').value,
                        characters: characters
                    });
                });
                scenesJsonInput.value = JSON.stringify(scenes);
            });

            document.querySelectorAll('.copy-prompt-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var textarea = document.getElementById(btn.dataset.target);

                    function flash() {
                        btn.textContent = 'Copied!';
                        setTimeout(function () { btn.textContent = 'Copy'; }, 1500);
                    }

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(textarea.value).then(flash);
                    } else {
                        textarea.select();
                        document.execCommand('copy');
                        flash();
                    }
                });
            });
        })();
    </script>
@endsection
