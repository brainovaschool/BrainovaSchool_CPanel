@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Social Board Settings</li>
                    </ol>
                </div>
                <div class="col-sm-4 text-sm-end">
                    <a href="{{ route('portal-social-reel-characters.index') }}" class="btn btn-outline-secondary">Reel Characters</a>
                </div>
            </div>
        </div>

        <div class="card ot-card mb-24">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Reel Characters</h4>
                <a href="{{ route('portal-social-reel-characters.create') }}" class="btn ot-btn-primary">+ Add Character</a>
            </div>
            <div class="card-body">
                <p class="text-secondary">Add every character your reels use here — name, portrait image, voice/appearance/rules for AI consistency, an optional reference document, and as many costume images as you need. Click a character to manage all of that.</p>

                @if ($data['reelCharacters']->isEmpty())
                    <p class="text-secondary mb-0">No characters yet — click "+ Add Character" above to create your first one.</p>
                @else
                    <div class="d-flex flex-wrap gap-3">
                        @foreach ($data['reelCharacters'] as $char)
                            <a href="{{ route('portal-social-reel-characters.edit', $char->id) }}" class="text-decoration-none">
                                <div class="border rounded p-2 text-center" style="width:120px;">
                                    @if ($char->portrait)
                                        <img src="{{ globalAsset($char->portrait->path) }}" style="width:100%;height:110px;object-fit:cover;" class="mb-1 rounded">
                                    @else
                                        <div class="d-flex align-items-center justify-content-center bg-light mb-1 rounded" style="width:100%;height:110px;color:#999;font-size:12px;">No image</div>
                                    @endif
                                    <p class="mb-0" style="font-size:13px;color:#333;">{{ $char->name }}</p>
                                    @if ((int) $char->status !== 1)
                                        <span class="badge-basic-danger-text" style="font-size:10px;">Inactive</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-body">
                <p class="text-secondary">Each block below is a named list — add as many as you want, rename any of
                    them, or delete ones you don't need. Blocks used elsewhere (e.g. "Formats" feeds the Reel form's
                    Format picker) keep working by this block's name, so renaming is safe — deleting one that's in
                    active use just leaves that picker with no options until you add a replacement block.</p>

                <form action="{{ route('portal-social-settings.update') }}" method="post">
                    @csrf

                    <div id="blocksContainer">
                        @foreach ($data['lists'] as $list)
                            <div class="settings-block mb-4 pb-4 border-bottom">
                                <input type="text" name="lists[{{ $list->id }}][label]" value="{{ $list->label }}"
                                       class="form-control ot-input mb-2 fw-bold" style="max-width:320px" required>
                                <p class="text-secondary mb-1">One per line.</p>
                                <textarea data-list-for="lists_{{ $list->id }}"
                                          data-field-name="lists[{{ $list->id }}][values][]"
                                          class="form-control ot-input mb-2" rows="4">{{ implode("\n", $list->values ?: []) }}</textarea>
                                <div id="lists_{{ $list->id }}Inputs">
                                    @foreach ($list->values ?: [] as $v)
                                        <input type="hidden" name="lists[{{ $list->id }}][values][]" value="{{ $v }}">
                                    @endforeach
                                </div>
                                <label class="d-inline-flex align-items-center gap-2 mt-1">
                                    <input type="checkbox" name="lists[{{ $list->id }}][delete]" value="1" class="delete-block-checkbox">
                                    <span class="text-danger">Delete this block</span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div id="newBlocksContainer"></div>

                    <button type="button" id="addBlockBtn" class="btn btn-outline-secondary mb-3">+ Add a block</button>
                    <br>
                    <button type="submit" class="btn btn-lg ot-btn-primary">Save Settings</button>
                </form>
            </div>
        </div>

        @php
            // The Pillar/Format/Series pickers are built in; a custom
            // field sourced from the exact same list would just be a
            // second, redundant dropdown for the same thing, so it's
            // left out of this list (the field itself is untouched -
            // still usable anywhere else it's wired up). The "Reel form
            // fields" management block (add/edit/remove field *types*)
            // was removed from this page once the field set was finalized -
            // the fields themselves, and editing their per-series values
            // below, still work exactly as before.
            $seriesEditableFields = $data['customFields']->filter(fn ($f) =>
                !in_array($f->list_key, ['reel_categories', 'reel_formats', 'series_names'], true)
            );
            $pillarOptions = optional($data['lists']->firstWhere('key', 'reel_categories'))->values ?? [];
            $formatOptions = optional($data['lists']->firstWhere('key', 'reel_formats'))->values ?? [];

            // Grouped by Pillar (in the Pillar list's own order) so it
            // reads as "what's covered in each pillar" at a glance - a
            // series with no pillar set, or one whose pillar value isn't
            // (or no longer is) in the Pillar list, still shows up under
            // its own group at the end rather than silently disappearing.
            $seriesByPillar = $data['series']->groupBy(fn ($s) => ($s->defaults['category'] ?? '') ?: '');
            $knownPillars = collect($pillarOptions)->push('')->unique();
            $orderedPillars = $knownPillars->merge($seriesByPillar->keys()->diff($knownPillars));
            $pillarGroups = $orderedPillars
                ->mapWithKeys(fn ($cat) => [$cat => $seriesByPillar->get($cat, collect())])
                ->filter(fn ($rows) => $rows->isNotEmpty());
            $seriesColspan = 6 + $seriesEditableFields->count();
        @endphp
        <div class="card ot-card mt-24">
            <div class="card-body">
                <h4>Series</h4>
                <p class="text-secondary">Grouped by Pillar so it's easy to see what's already covered. Each series
                    can carry its own defaults — Pillar, Format, and every field below. Pick the series on the Reel
                    form and all of it fills itself in automatically. Characters can take more than one.</p>

                <form id="deleteSeriesForm" method="post" action="" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>

                <datalist id="existingSeriesNames">
                    @foreach ($data['series']->pluck('name')->unique()->sort() as $existingName)
                        <option value="{{ $existingName }}"></option>
                    @endforeach
                </datalist>

                <form action="{{ route('portal-social-settings.series') }}" method="post" enctype="multipart/form-data">
                    @csrf

                    <div class="table-responsive">
                    <table class="ot-basic-table ot-table-bg" id="seriesTable">
                        <thead class="thead">
                            <tr>
                                <th>Name</th>
                                <th>Color</th>
                                <th>Code</th>
                                <th>Pillar</th>
                                <th>Format</th>
                                @foreach ($seriesEditableFields as $field)
                                    <th>{{ $field->label }}</th>
                                @endforeach
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody id="seriesTableBody">
                            @foreach ($pillarGroups as $pillar => $rows)
                                <tr class="bn-pillar-group-row">
                                    <td colspan="{{ $seriesColspan }}"><strong>{{ $pillar ?: 'No pillar set' }}</strong></td>
                                </tr>
                                @foreach ($rows as $s)
                                    @php($d = $s->defaults ?: [])
                                    @php($dc = $d['custom_fields'] ?? [])
                                    <tr class="series-row">
                                        <td><input type="text" name="series[{{ $s->id }}][name]" value="{{ $s->name }}" class="form-control ot-input" style="min-width:140px" list="existingSeriesNames" required></td>
                                        <td><input type="color" name="series[{{ $s->id }}][color]" value="{{ $s->color ?: '#007585' }}" class="form-control ot-input p-1" style="width:50px"></td>
                                        <td><input type="text" name="series[{{ $s->id }}][code]" value="{{ $s->code }}" class="form-control ot-input" placeholder="e.g. SS" maxlength="20" style="width:60px"></td>
                                        <td>
                                            <select name="series[{{ $s->id }}][category]" class="form-control ot-input" style="min-width:140px">
                                                <option value="">—</option>
                                                @foreach ($pillarOptions as $cat)
                                                    <option value="{{ $cat }}" {{ ($d['category'] ?? null) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="series[{{ $s->id }}][format]" class="form-control ot-input" style="min-width:110px">
                                                <option value="">—</option>
                                                @foreach ($formatOptions as $f)
                                                    <option value="{{ $f }}" {{ ($d['format'] ?? null) === $f ? 'selected' : '' }}>{{ $f }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        @foreach ($seriesEditableFields as $field)
                                            @php($current = $dc[$field->key] ?? null)
                                            @php($isCharacterField = $field->list_key === \App\Models\Portal\PortalReelCustomField::SOURCE_REEL_CHARACTERS)
                                            <td>
                                                @if ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_CHECKBOX)
                                                    <input type="checkbox" class="form-check-input" name="series[{{ $s->id }}][custom_fields][{{ $field->key }}]" value="1" {{ $current ? 'checked' : '' }}>
                                                @elseif ($isCharacterField)
                                                    @php($currentArr = (array) ($current ?? []))
                                                    <select name="series[{{ $s->id }}][custom_fields][{{ $field->key }}][]" class="form-control select2_multy" multiple style="min-width:160px">
                                                        @foreach ($field->options() as $opt)
                                                            <option value="{{ $opt }}" {{ in_array((string) $opt, array_map('strval', $currentArr), true) ? 'selected' : '' }}>{{ $opt }}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_SELECT)
                                                    <select name="series[{{ $s->id }}][custom_fields][{{ $field->key }}]" class="form-control ot-input" style="min-width:140px">
                                                        <option value="">—</option>
                                                        @foreach ($field->options() as $opt)
                                                            <option value="{{ $opt }}" {{ (string) $current === (string) $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_TEXTAREA)
                                                    <textarea name="series[{{ $s->id }}][custom_fields][{{ $field->key }}]" class="form-control ot-input" rows="2" style="min-width:160px">{{ $current }}</textarea>
                                                @elseif ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_FILE)
                                                    @php($fileInputId = 'series_' . $s->id . '_' . $field->key . '_file')
                                                    <div class="ot_fileUploader left-side mb-1">
                                                        <input class="form-control ot-input bn-file-placeholder" type="text" placeholder="No file" readonly style="min-width:100px">
                                                        <button class="primary-btn-small-input" type="button">
                                                            <label class="btn btn-sm ot-btn-primary" for="{{ $fileInputId }}">Browse</label>
                                                            <input type="file" class="d-none bn-file-input" id="{{ $fileInputId }}" name="series[{{ $s->id }}][custom_fields][{{ $field->key }}]">
                                                        </button>
                                                    </div>
                                                    @php($currentUpload = $current ? \App\Models\Upload::find($current) : null)
                                                    @if ($currentUpload)
                                                        <a href="{{ globalAsset($currentUpload->path) }}" target="_blank" class="d-block mt-1">Current file</a>
                                                    @endif
                                                @else
                                                    <input type="{{ $field->type }}" name="series[{{ $s->id }}][custom_fields][{{ $field->key }}]" class="form-control ot-input" value="{{ $current }}" style="{{ trim($field->label) === 'Time (sec)' ? 'width:60px' : 'min-width:120px' }}">
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-series-btn" data-url="{{ route('portal-social-settings.series.destroy', $s->id) }}" title="Delete this series">Delete</button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                    </div>

                    <button type="button" id="addSeriesBtn" class="btn btn-outline-secondary mb-3 mt-2">+ Add a series</button>
                    <br>
                    <button type="submit" class="btn btn-lg ot-btn-primary">Save Series</button>
                </form>

                <form action="{{ route('portal-social-settings.series.regenerate-codes') }}" method="post" class="mt-4 pt-3 border-top" onsubmit="return confirm('Recalculate every reel\'s code from each series\' current Code field? This rewrites the Code column on existing reels/topics in the Reels list.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Regenerate Reel Codes from Series</button>
                    <span class="text-secondary d-block mt-1" style="font-size:12px;">Use this after changing a series' Code above, or moving a reel to a different series, so the Reels list's Code column catches up.</span>
                </form>
            </div>
        </div>

        <div class="card ot-card mt-24">
            <div class="card-body">
                <h4>Who can be assigned content?</h4>
                <p class="text-secondary">Pick one or more staff Roles — everyone holding any of them shows up in the
                    "Assign to" dropdown on Content Pool and a reel's page. Pick more than one if some staff do dual
                    duty (e.g. "Content Creator" and "Teacher").</p>

                <form action="{{ route('portal-social-settings.content-assignees') }}" method="post">
                    @csrf
                    <select name="content_assignee_role_ids[]" class="form-control select2_multy wide" multiple>
                        @foreach ($data['roles'] as $role)
                            <option value="{{ $role->id }}" {{ in_array($role->id, $data['assigneeRoleIds'] ?? [], true) ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-lg ot-btn-primary mt-3">Save</button>
                </form>
            </div>
        </div>

        <div class="card ot-card mt-24">
            <div class="card-body">
                <h4>AI Scene Generation</h4>
                <p class="text-secondary">Used by Reel Studio's "AI: Fill In Scenes" button, for every reel.</p>

                <form action="{{ route('portal-social-settings.reel-ai-prompt') }}" method="post">
                    @csrf

                    <label class="form-label fw-bold">Global Instructions</label>
                    <p class="text-secondary">Included in <strong>every</strong> generated script — style, educational objectives, dialogue standards, scene continuity, and anything else that should always apply.</p>
                    <textarea name="reel_global_instructions" rows="8" class="form-control ot-textarea mb-4"
                        placeholder="e.g. Keep the tone warm and encouraging. Every reel should teach one clear idea. Scenes should feel like one continuous story, not disconnected clips...">{{ old('reel_global_instructions', $data['globalInstructions']) }}</textarea>

                    <label class="form-label fw-bold">AI Scene Generation Prompt <span class="text-secondary" style="font-weight:normal;">(advanced, optional)</span></label>
                    <p class="text-secondary">
                        This is the instruction set the AI follows when a teacher clicks "AI: Fill In Scenes" — write it however you like (tone, camera work, lighting, humor, pacing, story structure, anything), as detailed as you want.
                        Leave it blank to use a sensible built-in default instead.
                    </p>
                    <p class="text-secondary">
                        Use these placeholders anywhere in your text — they're filled in automatically for each reel:
                        <br><code>@{{title}}</code>, <code>@{{hook}}</code>, <code>@{{description}}</code>, <code>@{{format}}</code>, <code>@{{target_duration}}</code>, <code>@{{scene_count_instruction}}</code> (a ready-made sentence telling the AI how many scenes to make), <code>@{{characters}}</code> (the full roster the AI can choose from — their appearance/voice/rules — not what ends up in any one scene).
                    </p>
                    <p class="text-secondary">
                        Each scene features at most 3 characters, picked by the AI (or by a teacher, via the checkboxes on each scene) from that roster — never the whole roster at once. Dialogue in a scene only ever comes from whoever's checked for that scene, and the downloaded script includes each checked character's full appearance/voice/rules (plus a note about their reference portrait/costume images) right under that scene, alongside its duration, scene number, description and dialogue — all added automatically, you don't need to type any of it.
                    </p>
                    <p class="text-secondary">
                        You don't need to ask for JSON or worry about output format — the system adds its own technical instructions after your prompt automatically so the generated scenes always land back into the editable Scene boxes correctly.
                        You also don't need to write the opening hook line or the closing "Think Smart / Learn Smarter" line yourself — those are added automatically to the first/last scene.
                    </p>
                    <textarea name="reel_ai_prompt_template" rows="20" class="form-control ot-textarea" style="font-family:monospace;font-size:13px;"
                        placeholder="e.g. VISUAL STYLE: Premium futuristic Brainova educational animation. FORMAT: @{{format}}...">{{ old('reel_ai_prompt_template', $data['aiPromptTemplate']) }}</textarea>

                    <label class="form-label fw-bold mt-4">Start Scene AI Prompt <span class="text-secondary" style="font-weight:normal;">(optional)</span></label>
                    <p class="text-secondary">Extra instructions used only for the Start scene (the intro, closing with the Hook) when "AI: Fill In Scenes" runs — on top of the Scene Generation Prompt above.</p>
                    <textarea name="reel_start_scene_ai_prompt" rows="4" class="form-control ot-textarea mb-4"
                        placeholder="e.g. Open on a wide shot of the Brainova studio before cutting to the host...">{{ old('reel_start_scene_ai_prompt', $data['startSceneAiPrompt']) }}</textarea>

                    <label class="form-label fw-bold">End Scene AI Prompt <span class="text-secondary" style="font-weight:normal;">(optional)</span></label>
                    <p class="text-secondary">Extra instructions used only for the End scene (the send-off, closing with the End Hook) when "AI: Fill In Scenes" runs.</p>
                    <textarea name="reel_end_scene_ai_prompt" rows="4" class="form-control ot-textarea"
                        placeholder="e.g. End on a warm, encouraging close-up, inviting viewers to follow for more...">{{ old('reel_end_scene_ai_prompt', $data['endSceneAiPrompt']) }}</textarea>

                    <button type="submit" class="btn btn-lg ot-btn-primary mt-3">Save</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 p-0">
        <div class="card ot-card bn-reels-filters mb-24" style="padding:1.25rem">
            <div class="card-body">
                <h4>AI Cover Image</h4>
                <p class="text-secondary">Used by Reel Studio's "Create Cover" button. Needs an OpenAI account configured in Website Setup → AI Helper — that's the only provider this can use for images.</p>

                <form action="{{ route('portal-social-settings.reel-ai-prompt') }}" method="post">
                    @csrf

                    <label class="form-label fw-bold">Cover Image AI Prompt</label>
                    <p class="text-secondary">
                        Describes the image to generate — art style, mood, composition, anything you want every cover to share. Leave it blank and "Create Cover" will tell you to fill this in first.
                        <br>Placeholders: <code>@{{title}}</code>, <code>@{{hook}}</code>, <code>@{{description}}</code>, <code>@{{format}}</code>.
                    </p>
                    <textarea name="reel_cover_ai_prompt" rows="6" class="form-control ot-textarea"
                        placeholder="e.g. A premium, clean educational thumbnail in Brainova's turquoise/purple style for a reel titled &quot;@{{title}}&quot;, about: @{{description}}. No text on the image.">{{ old('reel_cover_ai_prompt', $data['coverAiPrompt']) }}</textarea>

                    <button type="submit" class="btn btn-lg ot-btn-primary mt-3">Save</button>
                </form>
            </div>
        </div>
    </div>

    <template id="newSeriesTemplate">
        <tr class="series-row">
            <td><input type="text" name="new_series[__INDEX__][name]" value="" class="form-control ot-input" placeholder="Series name" style="min-width:140px" list="existingSeriesNames" required></td>
            <td><input type="color" name="new_series[__INDEX__][color]" value="#007585" class="form-control ot-input p-1" style="width:50px"></td>
            <td><input type="text" name="new_series[__INDEX__][code]" value="" class="form-control ot-input" placeholder="e.g. SS" maxlength="20" style="width:60px"></td>
            <td>
                <select name="new_series[__INDEX__][category]" class="form-control ot-input" style="min-width:140px">
                    <option value="">—</option>
                    @foreach ($pillarOptions as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="new_series[__INDEX__][format]" class="form-control ot-input" style="min-width:110px">
                    <option value="">—</option>
                    @foreach ($formatOptions as $f)
                        <option value="{{ $f }}">{{ $f }}</option>
                    @endforeach
                </select>
            </td>
            @foreach ($seriesEditableFields as $field)
                @php($isCharacterField = $field->list_key === \App\Models\Portal\PortalReelCustomField::SOURCE_REEL_CHARACTERS)
                <td>
                    @if ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_CHECKBOX)
                        <input type="checkbox" class="form-check-input" name="new_series[__INDEX__][custom_fields][{{ $field->key }}]" value="1">
                    @elseif ($isCharacterField)
                        <select name="new_series[__INDEX__][custom_fields][{{ $field->key }}][]" class="form-control select2_multy" multiple style="min-width:160px">
                            @foreach ($field->options() as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    @elseif ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_SELECT)
                        <select name="new_series[__INDEX__][custom_fields][{{ $field->key }}]" class="form-control ot-input" style="min-width:140px">
                            <option value="">—</option>
                            @foreach ($field->options() as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    @elseif ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_TEXTAREA)
                        <textarea name="new_series[__INDEX__][custom_fields][{{ $field->key }}]" class="form-control ot-input" rows="2" style="min-width:160px"></textarea>
                    @elseif ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_FILE)
                        <div class="ot_fileUploader left-side mb-1">
                            <input class="form-control ot-input bn-file-placeholder" type="text" placeholder="No file" readonly style="min-width:100px">
                            <button class="primary-btn-small-input" type="button">
                                <label class="btn btn-sm ot-btn-primary" for="new_series___INDEX___{{ $field->key }}_file">Browse</label>
                                <input type="file" class="d-none bn-file-input" id="new_series___INDEX___{{ $field->key }}_file" name="new_series[__INDEX__][custom_fields][{{ $field->key }}]">
                            </button>
                        </div>
                    @else
                        <input type="{{ $field->type }}" name="new_series[__INDEX__][custom_fields][{{ $field->key }}]" class="form-control ot-input" value="" style="{{ trim($field->label) === 'Time (sec)' ? 'width:60px' : 'min-width:120px' }}">
                    @endif
                </td>
            @endforeach
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm remove-new-series">Remove</button>
            </td>
        </tr>
    </template>

    <template id="newBlockTemplate">
        <div class="settings-block mb-4 pb-4 border-bottom">
            <input type="text" name="new_lists[__INDEX__][label]" value=""
                   class="form-control ot-input mb-2 fw-bold" style="max-width:320px" placeholder="Block name" required>
            <p class="text-secondary mb-1">One per line.</p>
            <textarea data-list-for="new_lists___INDEX__"
                      data-field-name="new_lists[__INDEX__][values][]"
                      class="form-control ot-input mb-2" rows="4"></textarea>
            <div id="new_lists___INDEX__Inputs"></div>
            <button type="button" class="btn btn-outline-danger btn-sm remove-new-block">Remove this block</button>
        </div>
    </template>

    @push('css')
    <style>
        .bn-pillar-group-row td { background: rgba(var(--bn-turquoise-rgb, 0,117,133), .08); padding-top: .6rem; padding-bottom: .6rem; }
        #seriesTable td { vertical-align: middle; }
    </style>
    @endpush

    @push('script')
    <script>
        (function () {
            // Event delegation (not a per-element listener) so this also
            // covers textareas added later via "+ Add a block" with no
            // extra wiring needed.
            document.addEventListener('input', function (e) {
                if (!e.target.matches('textarea[data-list-for]')) return;
                var textarea = e.target;
                var container = document.getElementById(textarea.getAttribute('data-list-for') + 'Inputs');
                var fieldName = textarea.getAttribute('data-field-name');
                if (!container || !fieldName) return;
                container.innerHTML = '';
                textarea.value.split("\n").map(function (v) { return v.trim(); }).filter(Boolean).forEach(function (v) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = fieldName;
                    input.value = v;
                    container.appendChild(input);
                });
            });

            document.addEventListener('change', function (e) {
                if (e.target.classList.contains('delete-block-checkbox')) {
                    e.target.closest('.settings-block').style.opacity = e.target.checked ? '0.4' : '1';
                }
            });

            var newBlockIndex = 0;
            document.getElementById('addBlockBtn').addEventListener('click', function () {
                var html = document.getElementById('newBlockTemplate').innerHTML.split('__INDEX__').join(newBlockIndex);
                var wrapper = document.createElement('div');
                wrapper.innerHTML = html;
                document.getElementById('newBlocksContainer').appendChild(wrapper.firstElementChild);
                newBlockIndex++;
            });

            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-new-block')) {
                    e.target.closest('.settings-block').remove();
                }
            });
        })();

        (function () {
            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('delete-series-btn')) {
                    if (!confirm('Delete this series? This cannot be undone.')) return;
                    var form = document.getElementById('deleteSeriesForm');
                    form.action = e.target.dataset.url;
                    form.submit();
                }
            });

            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-new-series')) {
                    e.target.closest('.series-row').remove();
                }
            });

            // The datalist is rendered from the database at page load, so a
            // name typed into a still-unsaved "+ Add a series" row isn't in
            // it yet - mirror it in as soon as it's typed, so other rows
            // being added in the same sitting can suggest it too.
            document.addEventListener('input', function (e) {
                if (!e.target.matches('input[name^="new_series"][name$="[name]"]')) return;
                var val = e.target.value.trim();
                if (!val) return;
                var datalist = document.getElementById('existingSeriesNames');
                var already = Array.prototype.some.call(datalist.options, function (o) { return o.value === val; });
                if (!already) {
                    var opt = document.createElement('option');
                    opt.value = val;
                    datalist.appendChild(opt);
                }
            });

            // A <tr> string only parses correctly when the element it's
            // injected into is itself a valid table-row container (a bare
            // <div> silently drops stray <tr> tags) - a throwaway <tbody>
            // does the parsing, then its one child row gets moved into the
            // real table body.
            var newSeriesIndex = 0;
            document.getElementById('addSeriesBtn').addEventListener('click', function () {
                var html = document.getElementById('newSeriesTemplate').innerHTML.split('__INDEX__').join(newSeriesIndex);
                var wrapper = document.createElement('tbody');
                wrapper.innerHTML = html;
                var row = wrapper.firstElementChild;
                document.getElementById('seriesTableBody').appendChild(row);
                // select2 needs explicit init for rows added after page load -
                // the global init on load only ever saw the original set.
                jQuery(row).find('.select2_multy').select2();
                newSeriesIndex++;
            });

            // main.js hides every input[type=file] on every page - clicking
            // the visible "Browse" <label> still opens the picker natively
            // via its for="", so this only needs to mirror the chosen
            // filename into the readonly text box next to it. Delegated so
            // it also covers File fields added later via "+ Add a series".
            document.addEventListener('change', function (e) {
                if (!e.target.classList.contains('bn-file-input')) return;
                var wrapper = e.target.closest('.ot_fileUploader');
                var placeholder = wrapper ? wrapper.querySelector('.bn-file-placeholder') : null;
                if (placeholder) {
                    placeholder.value = e.target.files.length ? e.target.files[0].name : '';
                }
            });
        })();
    </script>
    @endpush
@endsection
