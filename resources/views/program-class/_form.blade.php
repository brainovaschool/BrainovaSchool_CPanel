@php
    $l = $data['item'] ?? null;

    $materialRows = old('materials.label') !== null
        ? collect(old('materials.label'))->map(fn ($label, $i) => [
            'label' => $label,
            'url' => old("materials.url.$i"),
            'storage_key' => old("materials.existing_storage_key.$i"),
            'original_filename' => old("materials.existing_original_filename.$i"),
        ])
        : ($l ? $l->materials->map(fn ($mat) => ['label' => $mat->label, 'url' => $mat->url, 'storage_key' => $mat->storage_key, 'original_filename' => $mat->original_filename]) : collect());

    $activityRows = old('activities.title') !== null
        ? collect(old('activities.title'))->map(fn ($title, $i) => [
            'title' => $title,
            'description' => old("activities.description.$i"),
            'video_url' => old("activities.video_url.$i"),
            'link_url' => old("activities.link_url.$i"),
        ])
        : ($l ? $l->activities->map(fn ($a) => ['title' => $a->title, 'description' => $a->description, 'video_url' => $a->video_url, 'link_url' => $a->link_url]) : collect());

    $outcomeRows = old('outcomes') !== null
        ? collect(old('outcomes'))
        : ($l ? $l->outcomes->pluck('outcome_text') : collect());
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Please fix the following before saving:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($l && $l->review_status === \App\Models\WebsiteSetup\ProgramClass::CHANGES_REQUESTED && $l->coordinator_feedback)
    <div class="alert alert-warning">
        <strong>Changes requested by the coordinator:</strong>
        <p class="mb-0">{{ $l->coordinator_feedback }}</p>
    </div>
@endif

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Class Title <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('title') is-invalid @enderror" name="title" maxlength="150"
            value="{{ old('title', $l->title ?? '') }}" placeholder="e.g. Class 1 — Meet the Coder">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Class Date (optional)</label>
        <input type="date" class="form-control ot-input" name="class_date"
            value="{{ old('class_date', $l && $l->class_date ? \Carbon\Carbon::parse($l->class_date)->format('Y-m-d') : '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('common.Serial') }}</label>
        <input class="form-control ot-input" name="sort_order" type="number" min="0" value="{{ old('sort_order', $l->sort_order ?? 0) }}">
    </div>
    <div class="col-md-12 mb-3">
        <label class="form-label">Description / Outline (optional)</label>
        <textarea class="form-control ot-textarea" name="description" rows="3" maxlength="3000"
            placeholder="What's covered in this class">{{ old('description', $l->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8 mb-3">
        <label class="form-label">Class Video (optional)</label>
        <input class="form-control ot-input" name="video_url" maxlength="500"
            value="{{ old('video_url', $l->video_url ?? '') }}" placeholder="YouTube / Facebook / Instagram / Canva link">
        <small class="text-secondary">Shown on Course Details as an embedded recap video.</small>
        @error('video_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>

<div class="card mt-2 mb-3 cc-form-section cc-form-section--materials">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0"><i class="fa-solid fa-paperclip me-1"></i> Materials</h5>
            <button type="button" class="btn btn-sm ot-btn-primary" id="addMaterialRow"><i class="fa-solid fa-plus"></i> Add material</button>
        </div>
        <p class="text-secondary mb-2" style="font-size:.85rem;">Worksheets, slides, presentations (PPTs) — upload a file, or paste a link instead. If both are given, the uploaded file is used.</p>
        <div id="materialsWrap">
            @foreach ($materialRows as $mat)
                <div class="row repeater-row align-items-center">
                    <div class="col-md-3 mb-2">
                        <input class="form-control ot-input" name="materials[label][]" value="{{ $mat['label'] }}" placeholder="Label, e.g. Class 1 Slides">
                    </div>
                    <div class="col-md-3 mb-2">
                        <input class="form-control ot-input" name="materials[url][]" value="{{ $mat['url'] }}" placeholder="Link (optional)">
                    </div>
                    <div class="col-md-5 mb-2">
                        <input type="file" class="form-control ot-input" name="materials[file][]">
                        @if (!empty($mat['original_filename']))
                            <small class="text-secondary d-block">Current file: {{ $mat['original_filename'] }} — choose a new one to replace it.</small>
                        @endif
                        <input type="hidden" name="materials[existing_storage_key][]" value="{{ $mat['storage_key'] }}">
                        <input type="hidden" name="materials[existing_original_filename][]" value="{{ $mat['original_filename'] }}">
                    </div>
                    <div class="col-md-1 mb-2">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                </div>
            @endforeach
        </div>
        <template id="materialRowTemplate">
            <div class="row repeater-row align-items-center">
                <div class="col-md-3 mb-2">
                    <input class="form-control ot-input" name="materials[label][]" value="" placeholder="Label, e.g. Class 1 Slides">
                </div>
                <div class="col-md-3 mb-2">
                    <input class="form-control ot-input" name="materials[url][]" value="" placeholder="Link (optional)">
                </div>
                <div class="col-md-5 mb-2">
                    <input type="file" class="form-control ot-input" name="materials[file][]">
                    <input type="hidden" name="materials[existing_storage_key][]" value="">
                    <input type="hidden" name="materials[existing_original_filename][]" value="">
                </div>
                <div class="col-md-1 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fa-solid fa-trash-can"></i></button>
                </div>
            </div>
        </template>
    </div>
</div>

<div class="card mt-2 mb-3 cc-form-section cc-form-section--activities">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0"><i class="fa-solid fa-pen-ruler me-1"></i> Activities</h5>
            <button type="button" class="btn btn-sm ot-btn-primary" id="addActivityRow"><i class="fa-solid fa-plus"></i> Add activity</button>
        </div>
        <p class="text-secondary mb-2" style="font-size:.85rem;">What the student actually does — a task, a project step, a game link.</p>
        <div id="activitiesWrap">
            @foreach ($activityRows as $act)
                <div class="row repeater-row border-top pt-2">
                    <div class="col-md-6 mb-2">
                        <input class="form-control ot-input" name="activities[title][]" value="{{ $act['title'] }}" placeholder="Activity title">
                    </div>
                    <div class="col-md-5 mb-2">
                        <input class="form-control ot-input" name="activities[link_url][]" value="{{ $act['link_url'] }}" placeholder="Link (optional)">
                    </div>
                    <div class="col-md-1 mb-2">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                    <div class="col-md-6 mb-2">
                        <input class="form-control ot-input" name="activities[video_url][]" value="{{ $act['video_url'] }}" placeholder="Video link (optional)">
                    </div>
                    <div class="col-md-6 mb-2">
                        <textarea class="form-control ot-textarea" name="activities[description][]" rows="2" placeholder="What the student should do">{{ $act['description'] }}</textarea>
                    </div>
                </div>
            @endforeach
        </div>
        <template id="activityRowTemplate">
            <div class="row repeater-row border-top pt-2">
                <div class="col-md-6 mb-2">
                    <input class="form-control ot-input" name="activities[title][]" value="" placeholder="Activity title">
                </div>
                <div class="col-md-5 mb-2">
                    <input class="form-control ot-input" name="activities[link_url][]" value="" placeholder="Link (optional)">
                </div>
                <div class="col-md-1 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fa-solid fa-trash-can"></i></button>
                </div>
                <div class="col-md-6 mb-2">
                    <input class="form-control ot-input" name="activities[video_url][]" value="" placeholder="Video link (optional)">
                </div>
                <div class="col-md-6 mb-2">
                    <textarea class="form-control ot-textarea" name="activities[description][]" rows="2" placeholder="What the student should do"></textarea>
                </div>
            </div>
        </template>
    </div>
</div>

<div class="card mt-2 mb-3 cc-form-section cc-form-section--outcomes">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0"><i class="fa-solid fa-bullseye me-1"></i> Learning Outcomes</h5>
            <button type="button" class="btn btn-sm ot-btn-primary" id="addOutcomeRow"><i class="fa-solid fa-plus"></i> Add outcome</button>
        </div>
        <p class="text-secondary mb-2" style="font-size:.85rem;">"By the end of this class, the student can..." — one line each.</p>
        <div id="outcomesWrap">
            @foreach ($outcomeRows as $text)
                <div class="row repeater-row align-items-center">
                    <div class="col-md-11 mb-2">
                        <input class="form-control ot-input" name="outcomes[]" value="{{ $text }}" placeholder="e.g. Build and run a simple animation in Scratch" maxlength="500">
                    </div>
                    <div class="col-md-1 mb-2">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                </div>
            @endforeach
        </div>
        <template id="outcomeRowTemplate">
            <div class="row repeater-row align-items-center">
                <div class="col-md-11 mb-2">
                    <input class="form-control ot-input" name="outcomes[]" value="" placeholder="e.g. Build and run a simple animation in Scratch" maxlength="500">
                </div>
                <div class="col-md-1 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fa-solid fa-trash-can"></i></button>
                </div>
            </div>
        </template>
    </div>
</div>

<div class="card mt-2 mb-3 cc-form-section cc-form-section--project">
    <div class="card-body">
        <h5 class="mb-1"><i class="fa-solid fa-trophy me-1"></i> Project (optional)</h5>
        <p class="text-secondary mb-2" style="font-size:.85rem;">One deliverable the student submits for this class — leave the title blank for no project.</p>
        <div class="row">
            <div class="col-md-6 mb-2">
                <input class="form-control ot-input" name="project_title" maxlength="150" value="{{ old('project_title', $l->project_title ?? '') }}" placeholder="Project title">
            </div>
            <div class="col-md-6 mb-2">
                <input class="form-control ot-input" name="project_link_url" maxlength="500" value="{{ old('project_link_url', $l->project_link_url ?? '') }}" placeholder="Link (optional)">
            </div>
            <div class="col-md-12 mb-2">
                <textarea class="form-control ot-textarea" name="project_description" rows="2" placeholder="What the student should build">{{ old('project_description', $l->project_description ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="card mt-2 mb-3 cc-form-section cc-form-section--quiz">
    <div class="card-body">
        <h5 class="mb-1"><i class="fa-solid fa-circle-question me-1"></i> Quiz (optional)</h5>
        <p class="text-secondary mb-2" style="font-size:.85rem;">One quiz the student completes for this class — leave the title blank for no quiz.</p>
        <div class="row">
            <div class="col-md-6 mb-2">
                <input class="form-control ot-input" name="quiz_title" maxlength="150" value="{{ old('quiz_title', $l->quiz_title ?? '') }}" placeholder="Quiz title">
            </div>
            <div class="col-md-6 mb-2">
                <input class="form-control ot-input" name="quiz_link_url" maxlength="500" value="{{ old('quiz_link_url', $l->quiz_link_url ?? '') }}" placeholder="Link (optional)">
            </div>
            <div class="col-md-12 mb-2">
                <textarea class="form-control ot-textarea" name="quiz_description" rows="2" placeholder="What the quiz covers">{{ old('quiz_description', $l->quiz_description ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <p class="text-secondary mb-0" style="font-size:.85rem;">
        @if ($l && $l->review_status !== \App\Models\WebsiteSetup\ProgramClass::DRAFT && $l->review_status !== \App\Models\WebsiteSetup\ProgramClass::CHANGES_REQUESTED)
            <span class="badge bg-secondary">{{ \App\Models\WebsiteSetup\ProgramClass::REVIEW_STATUSES[$l->review_status] ?? $l->review_status }}</span>
        @else
            Saving keeps this as a draft — use "Submit for review" afterwards when it's ready.
        @endif
    </p>
    <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i> </span>{{ ___('common.submit') }}</button>
</div>

@push('script')
<script>
(function () {
    function wireRepeater(addBtnId, wrapId, templateId) {
        var addBtn = document.getElementById(addBtnId);
        var wrap = document.getElementById(wrapId);
        var template = document.getElementById(templateId);
        if (!addBtn || !wrap || !template) return;

        addBtn.addEventListener('click', function () {
            wrap.appendChild(template.content.cloneNode(true));
        });

        wrap.addEventListener('click', function (e) {
            var btn = e.target.closest('.remove-row');
            if (btn) {
                btn.closest('.repeater-row').remove();
            }
        });
    }

    wireRepeater('addMaterialRow', 'materialsWrap', 'materialRowTemplate');
    wireRepeater('addActivityRow', 'activitiesWrap', 'activityRowTemplate');
    wireRepeater('addOutcomeRow', 'outcomesWrap', 'outcomeRowTemplate');
})();
</script>
@endpush

@push('css')
<style>
    .cc-form-section { border-left: 3px solid transparent; }
    .cc-form-section--materials { border-left-color: #2563eb33; }
    .cc-form-section--activities { border-left-color: #7c3aed33; }
    .cc-form-section--outcomes { border-left-color: #10b98133; }
    .cc-form-section--project { border-left-color: #b4530933; }
    .cc-form-section--quiz { border-left-color: #c026d333; }
</style>
@endpush
