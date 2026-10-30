@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php
        $project      = $data['project'];
        $linkedReel   = $project->portalReel;
        $isStandalone = $project->isStandalone();
        $customFields = $data['customFields'];
        $pillarField  = $customFields->first(fn ($f) => strtolower(trim($f->label)) === 'pillar');
        $seriesField  = $customFields->first(fn ($f) => strtolower(trim($f->label)) === 'series');
        // "Time (sec)" duplicates Target total length below - already covered, so hide it here.
        $otherFields  = $customFields->reject(fn ($f) => $f->is($pillarField) || $f->is($seriesField) || strtolower(trim($f->label)) === 'time (sec)');
        $projectCustomFields = $project->custom_fields ?: [];
    @endphp

    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">
                        {{ $project->title }}
                        @if (optional($linkedReel)->code)
                            <span class="badge-basic-info-text">{{ $linkedReel->code }}</span>
                        @endif
                    </h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('portal-social-reel-studio.index') }}">Reel Studio</a></li>
                        <li class="breadcrumb-item">{{ $project->title }}</li>
                    </ol>
                </div>
            </div>
        </div>

        @if ($linkedReel)
            <div class="card ot-card mb-3">
                <div class="card-header"><h4 class="mb-0">From suggestion</h4></div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Deadline:</strong> {{ $linkedReel->deadline?->format('d M Y') ?: '—' }}</div>
                        <div class="col-md-4"><strong>Suggested by:</strong> {{ optional($linkedReel->suggestedBy)->name ?: '—' }}</div>
                        <div class="col-md-4"><strong>Assigned to:</strong> {{ optional($linkedReel->assignee)->first_name ? trim($linkedReel->assignee->first_name . ' ' . $linkedReel->assignee->last_name) : '—' }}</div>
                    </div>

                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="badge-basic-info-text">{{ $linkedReel->statusLabel() }}</span>
                        <form action="{{ route('portal-social-reel-studio.send-prompt-for-approval', $project->id) }}" method="post">
                            @csrf
                            @if ($project->scenes->isEmpty())
                                <button type="submit" class="btn btn-sm ot-btn-primary" disabled title="Save scenes first">Send Prompt for Approval</button>
                            @else
                                <button type="submit" class="btn btn-sm ot-btn-primary">Send Prompt for Approval</button>
                            @endif
                        </form>
                    </div>
                    <p class="text-secondary mb-0 mt-1" style="font-size:13px;">Sends the current script (Download Script, in the Scenes section below) for approval. Canva link below unlocks once it's approved.</p>
                </div>
            </div>
        @endif

        <div class="card ot-card mb-3">
            <div class="card-header">
                <h4 class="mb-0">Reel Setup</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('portal-social-reel-studio.update', $project->id) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Pillar</label>
                            @if (!$isStandalone)
                                <input type="text" class="form-control ot-input" value="{{ $linkedReel->category ?: '—' }}" disabled>
                                <p class="text-secondary mb-0 mt-1">Set on the reel suggestion.</p>
                            @elseif ($pillarField)
                                @include('portal.social.reel-studio._custom-field-input', ['field' => $pillarField, 'current' => $projectCustomFields[$pillarField->key] ?? null])
                            @else
                                <input type="text" class="form-control ot-input" value="—" disabled>
                            @endif
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Series</label>
                            @if (!$isStandalone)
                                <input type="text" class="form-control ot-input" value="{{ $linkedReel->series_name ?: '—' }}" disabled>
                                <p class="text-secondary mb-0 mt-1">Set on the reel suggestion.</p>
                            @elseif ($seriesField)
                                @include('portal.social.reel-studio._custom-field-input', ['field' => $seriesField, 'current' => $projectCustomFields[$seriesField->key] ?? null])
                            @else
                                <input type="text" class="form-control ot-input" value="—" disabled>
                            @endif
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-8 mb-3">
                            <label class="form-label">Reel title</label>
                            <input type="text" name="title" class="form-control ot-input" value="{{ $project->title }}" required>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <label class="form-label">Hook</label>
                            <input type="text" name="hook" class="form-control ot-input" value="{{ $project->hook }}">
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control ot-textarea">{{ $project->description }}</textarea>
                        </div>

                        <div class="col-12 col-md-3 mb-3">
                            <label class="form-label">Orientation</label>
                            <select name="format" class="form-control ot-input">
                                @foreach (\App\Models\Portal\ReelProject::FORMATS as $value => $label)
                                    <option value="{{ $value }}" {{ $project->format == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3 mb-3">
                            <label class="form-label">Target total length (seconds)</label>
                            <input type="number" name="target_duration_seconds" min="5" max="600" class="form-control ot-input" value="{{ $project->target_duration_seconds }}">
                        </div>
                        <div class="col-12 col-md-3 mb-3">
                            <label class="form-label">Scene length (seconds, max 10)</label>
                            <input type="number" name="scene_length_seconds" min="1" max="10" class="form-control ot-input" value="{{ $project->scene_length_seconds }}">
                            <p class="text-secondary mb-0 mt-1">Total length ÷ scene length = how many scene boxes you get below.</p>
                        </div>

                        @php
                            $canvaLocked = !$isStandalone && !optional($linkedReel)->prompt_approved_at;
                            $driveLocked = !$isStandalone && !optional($linkedReel)->canva_approved_at;
                            $canvaValue  = $isStandalone ? $project->canva_link : optional($linkedReel)->canva_link;
                            $driveValue  = $isStandalone ? $project->drive_link : optional($linkedReel)->drive_link;
                        @endphp
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Canva link</label>
                            <input type="url" name="canva_link" class="form-control ot-input" placeholder="https://..." value="{{ $canvaValue }}" {{ $canvaLocked ? 'disabled' : '' }}>
                            <p class="text-secondary mb-0 mt-1">
                                @if ($canvaLocked)
                                    Locked until the Prompt is approved.
                                @else
                                    Add this once the reel is finished, then Save Reel Setup.
                                @endif
                            </p>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Drive link</label>
                            <input type="url" name="drive_link" class="form-control ot-input" placeholder="https://..." value="{{ $driveValue }}" {{ $driveLocked ? 'disabled' : '' }}>
                            <p class="text-secondary mb-0 mt-1">
                                @if ($driveLocked)
                                    Locked until the Canva link is approved.
                                @else
                                    Add this once uploaded, then Save Reel Setup — also saved on the reel itself, so it shows up wherever this reel appears.
                                @endif
                            </p>
                        </div>

                        @foreach ($otherFields as $field)
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label">{{ $field->label }}</label>
                                @if (!$isStandalone)
                                    @php($v = $linkedReel->custom_fields[$field->key] ?? null)
                                    @if ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_FILE && $v)
                                        @php($upload = \App\Models\Upload::find($v))
                                        @if ($upload)
                                            <p class="mb-0"><a href="{{ globalAsset($upload->path) }}" target="_blank">Download file</a></p>
                                        @else
                                            <input type="text" class="form-control ot-input" value="—" disabled>
                                        @endif
                                    @else
                                        <input type="text" class="form-control ot-input" value="{{ is_array($v) ? implode(', ', $v) : ($v !== null && $v !== '' ? (is_bool($v) ? ($v ? 'Yes' : 'No') : $v) : '—') }}" disabled>
                                    @endif
                                @else
                                    @include('portal.social.reel-studio._custom-field-input', ['field' => $field, 'current' => $projectCustomFields[$field->key] ?? null])
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <button class="btn ot-btn-primary">Save Reel Setup</button>
                </form>
            </div>
        </div>

        <div class="card ot-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="mb-0">Scenes</h4>
                    <p class="text-secondary mb-0">
                        @if ($data['sceneCount'])
                            This reel needs {{ $data['sceneCount'] }} content scene(s) at {{ $project->scene_length_seconds }}s each, plus the Start and End scenes below. For each one, fill in Description + Dialogue and check which 1-3 characters appear in it — or let AI draft everything. Either way, everything stays editable before you save.
                        @else
                            Set a target total length and a scene length in Reel Setup above to see how many scenes this reel needs.
                        @endif
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <form action="{{ route('portal-social-reel-studio.scenes.create-from-zero', $project->id) }}" method="post"
                          onsubmit="return confirm('This will delete all current scenes and create a fresh blank set based on your current Target length / Scene length. Continue?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary" {{ $data['sceneCount'] ? '' : 'disabled' }}>Create Scenes</button>
                    </form>
                    <form id="aiGenerateForm">
                        <button type="submit" id="aiGenerateBtn" class="btn ot-btn-primary" {{ $data['sceneCount'] ? '' : 'disabled' }}>✨ AI: Fill In Scenes</button>
                    </form>
                </div>
            </div>
            <div id="aiGenerateStatus" class="card-body border-top" style="display:none;">
                <div class="d-flex align-items-center gap-2">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    <span id="aiGenerateStatusText" style="white-space:pre-line;">AI is generating scenes… this can take up to 2 minutes.</span>
                    <button type="button" id="aiGenerateCancelBtn" class="btn btn-sm btn-outline-danger ms-auto">Cancel — I'll add scenes manually</button>
                </div>
            </div>
            <div class="card-body">
                <form id="scenesForm" action="{{ route('portal-social-reel-studio.scenes.store', $project->id) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Cover image</label>
                        <div class="ot_fileUploader left-side mb-1">
                            <input class="form-control ot-input bn-file-placeholder" type="text" placeholder="No file chosen" readonly>
                            <button class="primary-btn-small-input" type="button">
                                <label class="btn btn-lg ot-btn-primary" for="coverFile">Browse</label>
                                <input type="file" class="d-none bn-file-input" id="coverFile" name="cover_file" accept="image/*">
                            </button>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                            <button type="button" id="createCoverBtn" class="btn btn-sm ot-btn-primary">✨ Create Cover</button>
                            @if ($project->coverUpload)
                                <a href="{{ globalAsset($project->coverUpload->path) }}" target="_blank" class="text-secondary">Download current cover</a>
                            @endif
                            <span id="createCoverStatus" class="d-flex align-items-center gap-2" style="display:none;">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                <span id="createCoverStatusText" style="white-space:pre-line;"></span>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <p class="text-secondary mb-1" style="font-size:13px;">Start Scene — the intro, telling the viewer what this video is about, closing with the Hook above.</p>
                        <div id="startSceneContainer"></div>
                    </div>

                    <div id="scenesContainer" class="d-flex flex-column gap-3"></div>
                    <button type="button" id="addSceneBtn" class="btn btn-sm btn-outline-secondary mt-3">+ Add Scene</button>

                    <div class="mt-3">
                        <p class="text-secondary mb-1" style="font-size:13px;">End Scene — the send-off, closing with the reel's End Hook (if set, under "other options" in Reel Setup above).</p>
                        <div id="endSceneContainer"></div>
                    </div>

                    <input type="hidden" name="scenes_json" id="scenesJson">
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-lg ot-btn-primary">Save Scenes &amp; Download Script</button>
                        @if ($project->scenes->isNotEmpty())
                            <a href="{{ route('portal-social-reel-studio.scenes.download', $project->id) }}" class="btn btn-lg btn-outline-secondary">Download Script Again</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var initial       = {!! json_encode($data['initialScenesJson']) !!}; // {start, scenes, end}
            var sceneCount    = {{ (int) ($data['sceneCount'] ?? 0) }};
            var availableChars = {!! json_encode($data['availableCharsJson']) !!};
            var MAX_CHARS_PER_SCENE = 3;
            var sceneDownloadUrlTemplate = '{{ route('portal-social-reel-studio.scenes.download-one', [$project->id, '__SCENE_ID__']) }}';

            var container      = document.getElementById('scenesContainer');
            var startContainer = document.getElementById('startSceneContainer');
            var endContainer   = document.getElementById('endSceneContainer');
            var addSceneBtn    = document.getElementById('addSceneBtn');
            var scenesJsonInput = document.getElementById('scenesJson');
            var form           = document.getElementById('scenesForm');

            function characterCheckboxesHtml(uid) {
                if (!availableChars.length) {
                    return '<p class="text-secondary mb-0" style="font-size:13px;">No characters set up yet — add some in Reel Characters first.</p>';
                }
                return availableChars.map(function (c) {
                    var id = 'sceneChar' + uid + '_' + c.id;
                    return '<div class="form-check form-check-inline">' +
                        '<input type="checkbox" class="form-check-input scene-character" id="' + id + '" value="' + c.id + '">' +
                        '<label class="form-check-label" for="' + id + '">' + c.name + '</label>' +
                        '</div>';
                }).join('');
            }

            function enforceCharacterLimit(card) {
                var boxes = card.querySelectorAll('.scene-character');
                var checkedCount = card.querySelectorAll('.scene-character:checked').length;
                boxes.forEach(function (box) {
                    box.disabled = !box.checked && checkedCount >= MAX_CHARS_PER_SCENE;
                });
            }

            var sceneUid = 0;

            /** Builds one scene box. headerText is only the INITIAL label -
             *  content scenes get renumbered afterward; Start/End keep
             *  whatever's passed since there's only ever one of each. */
            function buildSceneCard(sceneData, headerText, removable) {
                sceneData = sceneData || { id: null, title: '', description: '', dialogue: '', characters: [] };
                sceneUid++;
                var uid = sceneUid;

                var card = document.createElement('div');
                card.className = 'card p-3';
                card.dataset.sceneId = sceneData.id || '';
                card.innerHTML =
                    '<div class="d-flex justify-content-between align-items-center mb-2">' +
                        '<h5 class="mb-0 scene-number">' + headerText + '</h5>' +
                        '<div class="d-flex gap-2">' +
                            (sceneData.id
                                ? '<a href="' + sceneDownloadUrlTemplate.replace('__SCENE_ID__', sceneData.id) + '" class="btn btn-sm btn-outline-secondary">Download Script</a>'
                                : '') +
                            (removable ? '<button type="button" class="btn btn-sm btn-outline-danger scene-remove">Remove Scene</button>' : '') +
                        '</div>' +
                    '</div>' +
                    '<div class="mb-2">' +
                        '<label class="form-label">Scene title (what this scene is about)</label>' +
                        '<input type="text" class="form-control form-control-sm scene-title" placeholder="e.g. The Honest Mistake">' +
                    '</div>' +
                    '<div class="mb-2">' +
                        '<label class="form-label">Description (setting, background, camera, lighting, what each character is doing — be specific, this drives the actual video)</label>' +
                        '<textarea class="form-control form-control-sm scene-description" rows="4"></textarea>' +
                    '</div>' +
                    '<div class="mb-2">' +
                        '<label class="form-label">Dialogues — one line per character, e.g. "Kea: ..." (every character checked below should get at least one line)</label>' +
                        '<textarea class="form-control form-control-sm scene-dialogue" rows="3"></textarea>' +
                    '</div>' +
                    '<div>' +
                        '<label class="form-label">Characters in this scene (up to 3)</label>' +
                        '<div class="scene-characters-box">' + characterCheckboxesHtml(uid) + '</div>' +
                    '</div>';

                card.querySelector('.scene-title').value = sceneData.title || '';
                card.querySelector('.scene-description').value = sceneData.description || '';
                card.querySelector('.scene-dialogue').value = sceneData.dialogue || '';
                if (removable) {
                    card.querySelector('.scene-remove').addEventListener('click', function () { card.remove(); renumberScenes(); });
                }

                var checkedIds = (sceneData.characters || []).map(String);
                card.querySelectorAll('.scene-character').forEach(function (box) {
                    box.checked = checkedIds.indexOf(box.value) !== -1;
                    box.addEventListener('change', function () { enforceCharacterLimit(card); });
                });
                enforceCharacterLimit(card);

                return card;
            }

            function addScene(sceneData) {
                container.appendChild(buildSceneCard(sceneData, '', true));
                renumberScenes();
            }

            function renumberScenes() {
                container.querySelectorAll(':scope > .card').forEach(function (card, i) {
                    card.querySelector('.scene-number').textContent = 'Scene ' + (i + 1);
                });
            }

            function collectCard(card) {
                var characters = [];
                card.querySelectorAll('.scene-character:checked').forEach(function (box) {
                    characters.push(parseInt(box.value, 10));
                });
                return {
                    id: card.dataset.sceneId || null,
                    title: card.querySelector('.scene-title').value,
                    description: card.querySelector('.scene-description').value,
                    dialogue: card.querySelector('.scene-dialogue').value,
                    characters: characters
                };
            }

            addSceneBtn.addEventListener('click', function () { addScene(); });

            startContainer.appendChild(buildSceneCard(initial.start, 'Start Scene', false));
            endContainer.appendChild(buildSceneCard(initial.end, 'End Scene', false));

            if (initial.scenes.length) {
                initial.scenes.forEach(addScene);
            } else if (sceneCount) {
                for (var i = 0; i < sceneCount; i++) { addScene(); }
            } else {
                addScene();
            }

            form.addEventListener('submit', function () {
                var scenes = [];
                container.querySelectorAll(':scope > .card').forEach(function (card) {
                    scenes.push(collectCard(card));
                });
                scenesJsonInput.value = JSON.stringify({
                    start: collectCard(startContainer.querySelector('.card')),
                    scenes: scenes,
                    end: collectCard(endContainer.querySelector('.card'))
                });
            });
        })();

        (function () {
            var form         = document.getElementById('aiGenerateForm');
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

                if (!confirm("AI will fill in every scene box below, replacing anything already written in them. Continue?")) {
                    return;
                }

                controller = new AbortController();
                generateBtn.disabled = true;
                statusBox.style.display = 'block';
                spinner.style.display = '';
                cancelBtn.style.display = '';
                setStatus('AI is generating scenes… this can take up to 2 minutes. Feel free to cancel and add scenes manually if it\'s taking too long.', false);

                fetch('{{ route('portal-social-reel-studio.ai.generate-scenes', $project->id) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: new FormData(),
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

        (function () {
            var createBtn = document.getElementById('createCoverBtn');
            if (!createBtn) return;
            var statusBox  = document.getElementById('createCoverStatus');
            var statusText = document.getElementById('createCoverStatusText');

            createBtn.addEventListener('click', function () {
                if (!confirm("AI will generate a cover image and replace the current one (if any). Continue?")) {
                    return;
                }

                createBtn.disabled = true;
                statusBox.style.display = 'flex';
                statusText.style.color = '';
                statusText.textContent = 'Generating cover image… this can take up to a minute.';

                fetch('{{ route('portal-social-reel-studio.ai.generate-cover', $project->id) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: new FormData()
                })
                .then(function (response) {
                    return response.json().then(function (data) { return { ok: response.ok, data: data }; });
                })
                .then(function (result) {
                    if (result.ok && result.data.status) {
                        statusText.textContent = (result.data.message || 'Cover image generated.') + ' Reloading…';
                        window.location.reload();
                    } else {
                        statusText.textContent = result.data.message || 'Something went wrong generating the cover image.';
                        statusText.style.color = '#b91c1c';
                        createBtn.disabled = false;
                    }
                })
                .catch(function () {
                    statusText.textContent = 'Could not reach the server.';
                    statusText.style.color = '#b91c1c';
                    createBtn.disabled = false;
                });
            });
        })();

        // main.js hides every input[type=file] on every page - clicking the
        // visible "Browse" <label> still opens the picker natively via its
        // for="", so this only needs to mirror the chosen filename into the
        // readonly text box next to it.
        document.addEventListener('change', function (e) {
            if (!e.target.classList.contains('bn-file-input')) return;
            var wrapper = e.target.closest('.ot_fileUploader');
            var placeholder = wrapper ? wrapper.querySelector('.bn-file-placeholder') : null;
            if (placeholder) {
                placeholder.value = e.target.files.length ? e.target.files[0].name : '';
            }
        });
    </script>
@endsection
