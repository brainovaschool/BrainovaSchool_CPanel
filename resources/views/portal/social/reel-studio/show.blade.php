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
                    <div class="row">
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

                        <div class="col-12 mb-3">
                            <label class="form-label">Characters involved in this reel</label>
                            <div class="d-flex flex-wrap gap-3">
                                @forelse ($data['characters'] as $character)
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="character_ids[]" value="{{ $character->id }}" id="projChar{{ $character->id }}"
                                            {{ in_array($character->id, $project->character_ids ?? []) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="projChar{{ $character->id }}">{{ $character->name }}</label>
                                    </div>
                                @empty
                                    <p class="text-secondary mb-0">No characters set up yet.</p>
                                @endforelse
                            </div>
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
                            This reel needs {{ $data['sceneCount'] }} scene(s) at {{ $project->scene_length_seconds }}s each. Fill in Description + Dialogue for each, or let AI draft them — either way, everything stays editable before you save.
                        @else
                            Set a target total length and a scene length in Reel Setup above to see how many scenes this reel needs.
                        @endif
                    </p>
                </div>
                <form id="aiGenerateForm">
                    <button type="submit" id="aiGenerateBtn" class="btn ot-btn-primary" {{ $data['sceneCount'] ? '' : 'disabled' }}>✨ AI: Fill In Scenes</button>
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
                        @if ($project->coverUpload)
                            <p class="text-secondary mb-0"><a href="{{ globalAsset($project->coverUpload->path) }}" target="_blank">Download current cover</a></p>
                        @endif
                    </div>
                    <div id="scenesContainer" class="d-flex flex-column gap-3"></div>
                    <button type="button" id="addSceneBtn" class="btn btn-sm btn-outline-secondary mt-3">+ Add Scene</button>
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
            var initialScenes = {!! json_encode($data['initialScenesJson']) !!};
            var sceneCount    = {{ (int) ($data['sceneCount'] ?? 0) }};

            var container = document.getElementById('scenesContainer');
            var addSceneBtn = document.getElementById('addSceneBtn');
            var scenesJsonInput = document.getElementById('scenesJson');
            var form = document.getElementById('scenesForm');

            function addScene(sceneData) {
                sceneData = sceneData || { id: null, description: '', dialogue: '' };

                var card = document.createElement('div');
                card.className = 'card p-3';
                card.dataset.sceneId = sceneData.id || '';
                card.innerHTML =
                    '<div class="d-flex justify-content-between align-items-center mb-2">' +
                        '<h5 class="mb-0 scene-number"></h5>' +
                        '<button type="button" class="btn btn-sm btn-outline-danger scene-remove">Remove Scene</button>' +
                    '</div>' +
                    '<div class="mb-2">' +
                        '<label class="form-label">Description (setting, background, camera, visual events)</label>' +
                        '<textarea class="form-control form-control-sm scene-description" rows="2"></textarea>' +
                    '</div>' +
                    '<div>' +
                        '<label class="form-label">Dialogues (one line per character, e.g. "Kea: ...")</label>' +
                        '<textarea class="form-control form-control-sm scene-dialogue" rows="3"></textarea>' +
                    '</div>';

                card.querySelector('.scene-description').value = sceneData.description || '';
                card.querySelector('.scene-dialogue').value = sceneData.dialogue || '';
                card.querySelector('.scene-remove').addEventListener('click', function () { card.remove(); renumberScenes(); });

                container.appendChild(card);
                renumberScenes();
            }

            function renumberScenes() {
                container.querySelectorAll(':scope > .card').forEach(function (card, i) {
                    card.querySelector('.scene-number').textContent = 'Scene ' + (i + 1);
                });
            }

            addSceneBtn.addEventListener('click', function () { addScene(); });

            if (initialScenes.length) {
                initialScenes.forEach(addScene);
            } else if (sceneCount) {
                for (var i = 0; i < sceneCount; i++) { addScene(); }
            } else {
                addScene();
            }

            form.addEventListener('submit', function () {
                var scenes = [];
                container.querySelectorAll(':scope > .card').forEach(function (card) {
                    scenes.push({
                        id: card.dataset.sceneId || null,
                        description: card.querySelector('.scene-description').value,
                        dialogue: card.querySelector('.scene-dialogue').value
                    });
                });
                scenesJsonInput.value = JSON.stringify(scenes);
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
