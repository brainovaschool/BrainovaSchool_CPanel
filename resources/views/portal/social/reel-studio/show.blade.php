@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">
                        {{ $data['project']->title }}
                        @if (optional($data['project']->portalReel)->code)
                            <span class="badge-basic-info-text">{{ $data['project']->portalReel->code }}</span>
                        @endif
                    </h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('portal-social-reel-studio.index') }}">Reel Studio</a></li>
                        <li class="breadcrumb-item">{{ $data['project']->title }}</li>
                    </ol>
                </div>
            </div>
        </div>

        @php($linkedReel = $data['project']->portalReel)
        @if ($linkedReel)
            <div class="card ot-card mb-3">
                <div class="card-header"><h4 class="mb-0">From suggestion</h4></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4"><strong>Code:</strong> {{ $linkedReel->code ?: '—' }}</div>
                        <div class="col-md-4"><strong>Series:</strong>
                            @if ($linkedReel->series_name)
                                <span @if (optional($linkedReel->seriesModel)->color) style="color:{{ $linkedReel->seriesModel->color }};font-weight:600" @endif>{{ $linkedReel->series_name }}</span>
                            @else
                                —
                            @endif
                        </div>
                        <div class="col-md-4"><strong>Pillar:</strong> {{ $linkedReel->category ?: '—' }}</div>
                        <div class="col-md-4"><strong>Deadline:</strong> {{ $linkedReel->deadline?->format('d M Y') ?: '—' }}</div>
                        <div class="col-md-4"><strong>Suggested by:</strong> {{ optional($linkedReel->suggestedBy)->name ?: '—' }}</div>
                        <div class="col-md-4"><strong>Assigned to:</strong> {{ optional($linkedReel->assignee)->first_name ? trim($linkedReel->assignee->first_name . ' ' . $linkedReel->assignee->last_name) : '—' }}</div>
                        <div class="col-md-4"><a href="{{ route('portal-social-reels.show', $linkedReel->id) }}">Open the reel's own page</a></div>
                    </div>
                </div>
            </div>
        @endif

        <div class="card ot-card mb-3">
            <div class="card-header">
                <h4 class="mb-0">Reel Setup</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('portal-social-reel-studio.update', $data['project']->id) }}" method="post">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-12 col-md-8 mb-3">
                            <label class="form-label">Reel title</label>
                            <input type="text" name="title" class="form-control ot-input" value="{{ $data['project']->title }}" required>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <label class="form-label">Orientation</label>
                            <select name="format" class="form-control ot-input">
                                @foreach (\App\Models\Portal\ReelProject::FORMATS as $value => $label)
                                    <option value="{{ $value }}" {{ $data['project']->format == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Hook</label>
                            <textarea name="hook" rows="2" class="form-control ot-textarea">{{ $data['project']->hook }}</textarea>
                            <small class="text-secondary">Kea always opens the first scene with this line, word for word.</small>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control ot-textarea">{{ $data['project']->description }}</textarea>
                        </div>

                        <div class="col-12 col-md-4 mb-3">
                            <label class="form-label">Target total length (seconds)</label>
                            <input type="number" name="target_duration_seconds" min="5" max="600" class="form-control ot-input" value="{{ $data['project']->target_duration_seconds }}">
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Characters involved in this reel</label>
                            <div class="d-flex flex-wrap gap-3">
                                @forelse ($data['characters'] as $character)
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="character_ids[]" value="{{ $character->id }}" id="projChar{{ $character->id }}"
                                            {{ in_array($character->id, $data['project']->character_ids ?? []) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="projChar{{ $character->id }}">{{ $character->name }}</label>
                                    </div>
                                @empty
                                    <p class="text-secondary mb-0">No characters set up yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <button class="btn ot-btn-primary">Save Reel Setup</button>
                </form>
            </div>
        </div>

        <div class="card ot-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="mb-0">Scenes</h4>
                    <p class="text-secondary mb-0">Add scenes manually, or let AI draft a full set from your hook/description/characters above — either way, everything stays editable before you generate prompts.</p>
                </div>
                <form id="aiGenerateForm" class="d-flex align-items-end gap-2">
                    <div>
                        <label class="form-label mb-0" style="font-size:12px;">Number of scenes (optional)</label>
                        <input type="number" id="aiSceneCount" min="1" max="20" class="form-control form-control-sm" style="width:110px;" placeholder="auto">
                    </div>
                    <button type="submit" id="aiGenerateBtn" class="btn ot-btn-primary">✨ AI: Generate Scenes</button>
                </form>
            </div>
            <div id="aiGenerateStatus" class="card-body border-top" style="display:none;">
                <div class="d-flex align-items-center gap-2">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    <span id="aiGenerateStatusText" style="white-space:pre-line;">AI is generating scenes… this can take up to 2 minutes.</span>
                    <button type="button" id="aiGenerateCancelBtn" class="btn btn-sm btn-outline-danger ms-auto">Cancel — I'll add scenes manually</button>
                </div>
            </div>
            <div class="card-body">
                <form id="scenesForm" action="{{ route('portal-social-reel-studio.scenes.store', $data['project']->id) }}" method="post">
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

        @if ($data['project']->scenes->isNotEmpty())
            <div class="card ot-card">
                <div class="card-header">
                    <h4 class="mb-0">Scene Prompts</h4>
                    <p class="text-secondary mb-0">Save your scenes above first, then generate each one's prompt here — read it over before downloading.</p>
                </div>
                <div class="card-body">
                    @foreach ($data['project']->scenes as $scene)
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h5 class="mb-0">
                                        Scene {{ $scene->sort_order }} ({{ $scene->duration_seconds }}s)
                                        @if ($scene->is_first_scene)
                                            <span class="badge-basic-success-text">Opening</span>
                                        @endif
                                        @if ($scene->is_last_scene)
                                            <span class="badge-basic-primary-text">Closing</span>
                                        @endif
                                    </h5>
                                    <form action="{{ route('portal-social-reel-studio.scenes.generate-prompt', [$data['project']->id, $scene->id]) }}" method="post">
                                        @csrf
                                        <button class="btn btn-sm ot-btn-primary">{{ $scene->generated_prompt ? 'Regenerate Prompt' : 'Generate Prompt' }}</button>
                                    </form>
                                </div>

                                @if ($scene->generated_prompt)
                                    <textarea readonly rows="14" class="form-control mt-3" id="promptText{{ $scene->id }}" style="font-family:monospace;font-size:13px;">{{ $scene->generated_prompt }}</textarea>
                                    <div class="mt-2 d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary copy-prompt-btn" data-target="promptText{{ $scene->id }}">Copy</button>
                                        <a href="{{ route('portal-social-reel-studio.scenes.download-prompt', [$data['project']->id, $scene->id]) }}" class="btn btn-sm btn-outline-secondary">Download .txt</a>
                                        @foreach ($scene->sceneCharacters as $sc)
                                            @if ($sc->character && $sc->character->portrait_upload_id)
                                                <a href="{{ route('portal-social-reel-studio.scenes.download-portrait', [$data['project']->id, $scene->id, $sc->character->id]) }}" class="btn btn-sm btn-outline-secondary">Download {{ $sc->character->name }} portrait</a>
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

    <script>
        (function () {
            var initialScenes = {!! json_encode($data['initialScenesJson']) !!};
            var availableCharacters = {!! json_encode($data['availableCharsJson']) !!};
            var MAX_CHARACTERS = 3;
            var MAX_SECONDS = 10;

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
                                '<option value="">Choose character</option>' +
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
                            '<button type="button" class="btn btn-sm btn-outline-danger char-remove">Remove</button>' +
                        '</div>' +
                    '</div>';

                row.querySelector('.char-action').value = charData.action_points || '';
                row.querySelector('.char-dialogue').value = charData.dialogue || '';
                row.querySelector('.char-remove').addEventListener('click', function () { row.remove(); });

                charsContainer.appendChild(row);
            }

            function addScene(sceneData) {
                sceneData = sceneData || { id: null, duration_seconds: 8, description: '', is_first_scene: false, is_last_scene: false, characters: [] };

                var card = document.createElement('div');
                card.className = 'card p-3';
                card.dataset.sceneId = sceneData.id || '';
                card.innerHTML =
                    '<div class="row g-2 mb-2 align-items-end">' +
                        '<div class="col-12 col-md-3">' +
                            '<label class="form-label">Duration (seconds, max 10)</label>' +
                            '<input type="number" min="1" max="' + MAX_SECONDS + '" class="form-control form-control-sm scene-duration" value="' + (sceneData.duration_seconds || 8) + '">' +
                        '</div>' +
                        '<div class="col-6 col-md-3">' +
                            '<div class="form-check">' +
                                '<input type="checkbox" class="form-check-input scene-is-first">' +
                                '<label class="form-check-label">First scene (hook)</label>' +
                            '</div>' +
                            '<div class="form-check">' +
                                '<input type="checkbox" class="form-check-input scene-is-last">' +
                                '<label class="form-check-label">Last scene (closing)</label>' +
                            '</div>' +
                        '</div>' +
                        '<div class="col-12 col-md-6 text-end">' +
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
                card.querySelector('.scene-is-first').checked = !!sceneData.is_first_scene;
                card.querySelector('.scene-is-last').checked = !!sceneData.is_last_scene;
                card.querySelector('.scene-duration').addEventListener('change', function () {
                    var v = parseInt(this.value, 10) || 1;
                    if (v > MAX_SECONDS) this.value = MAX_SECONDS;
                    if (v < 1) this.value = 1;
                });
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

                    var duration = parseInt(card.querySelector('.scene-duration').value, 10) || 8;
                    if (duration > MAX_SECONDS) duration = MAX_SECONDS;

                    scenes.push({
                        id: card.dataset.sceneId || null,
                        duration_seconds: duration,
                        description: card.querySelector('.scene-description').value,
                        is_first_scene: card.querySelector('.scene-is-first').checked,
                        is_last_scene: card.querySelector('.scene-is-last').checked,
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

        (function () {
            var form         = document.getElementById('aiGenerateForm');
            var sceneCount   = document.getElementById('aiSceneCount');
            var generateBtn  = document.getElementById('aiGenerateBtn');
            var statusBox    = document.getElementById('aiGenerateStatus');
            var statusText   = document.getElementById('aiGenerateStatusText');
            var cancelBtn    = document.getElementById('aiGenerateCancelBtn');
            var spinner      = statusBox.querySelector('.spinner-border');
            var controller   = null;

            function setStatus(text, isError) {
                statusText.textContent = text;
                statusText.style.color = isError ? '#b91c1c' : '';
            }

            function stopWaiting() {
                generateBtn.disabled = false;
                spinner.style.display = 'none';
                cancelBtn.style.display = 'none';
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                if (!confirm("AI will add new scenes after whatever is already here (it won't touch or remove existing scenes). Continue?")) {
                    return;
                }

                controller = new AbortController();
                generateBtn.disabled = true;
                statusBox.style.display = 'block';
                spinner.style.display = '';
                cancelBtn.style.display = '';
                setStatus('AI is generating scenes… this can take up to 2 minutes. Feel free to cancel and add scenes manually if it\'s taking too long.', false);

                var formData = new FormData();
                if (sceneCount.value) {
                    formData.append('scene_count', sceneCount.value);
                }

                fetch('{{ route('portal-social-reel-studio.ai.generate-scenes', $data['project']->id) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: formData,
                    signal: controller.signal
                })
                .then(function (response) {
                    return response.json().then(function (data) { return { ok: response.ok, data: data }; });
                })
                .then(function (result) {
                    if (result.ok && result.data.status) {
                        setStatus((result.data.message || 'Scenes generated.') + ' Reloading…', false);
                        window.location.reload();
                    } else {
                        setStatus(result.data.message || 'Something went wrong. You can add scenes manually below.', true);
                        stopWaiting();
                    }
                })
                .catch(function (err) {
                    if (err.name === 'AbortError') {
                        setStatus("Cancelled — add scenes manually below whenever you're ready.", false);
                    } else {
                        setStatus('Could not reach the server. You can add scenes manually below.', true);
                    }
                    stopWaiting();
                });
            });

            cancelBtn.addEventListener('click', function () {
                if (controller) {
                    controller.abort();
                }
            });
        })();
    </script>
@endsection
