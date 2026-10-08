@php
    $l = $data['item'] ?? null;

    $materialRows = old('materials.label') !== null
        ? collect(old('materials.label'))->map(fn ($label, $i) => ['label' => $label, 'url' => old("materials.url.$i")])
        : ($l ? $l->materials->map(fn ($mat) => ['label' => $mat->label, 'url' => $mat->url]) : collect());

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

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Lesson Title <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('title') is-invalid @enderror" name="title" maxlength="150"
            value="{{ old('title', $l->title ?? '') }}" placeholder="e.g. Lesson 1 — Getting Started">
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
            placeholder="What's covered in this lesson">{{ old('description', $l->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8 mb-3">
        <label class="form-label">Lesson Video (optional)</label>
        <input class="form-control ot-input" name="video_url" maxlength="500"
            value="{{ old('video_url', $l->video_url ?? '') }}" placeholder="YouTube / Facebook / Instagram / Canva link">
        <small class="text-secondary">Shown to students as an embedded recap video.</small>
        @error('video_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>

<div class="card mt-2 mb-3 cc-form-section cc-form-section--materials">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0"><i class="fa-solid fa-paperclip me-1"></i> Materials</h5>
            <button type="button" class="btn btn-sm ot-btn-primary" id="addMaterialRow"><i class="fa-solid fa-plus"></i> Add material</button>
        </div>
        <p class="text-secondary mb-2" style="font-size:.85rem;">Worksheets, slides, reference links — anything the student can open.</p>
        <div id="materialsWrap">
            @foreach ($materialRows as $mat)
                <div class="row repeater-row align-items-center">
                    <div class="col-md-5 mb-2">
                        <input class="form-control ot-input" name="materials[label][]" value="{{ $mat['label'] }}" placeholder="Label, e.g. Worksheet PDF">
                    </div>
                    <div class="col-md-6 mb-2">
                        <input class="form-control ot-input" name="materials[url][]" value="{{ $mat['url'] }}" placeholder="Link">
                    </div>
                    <div class="col-md-1 mb-2">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                </div>
            @endforeach
        </div>
        <template id="materialRowTemplate">
            <div class="row repeater-row align-items-center">
                <div class="col-md-5 mb-2">
                    <input class="form-control ot-input" name="materials[label][]" value="" placeholder="Label, e.g. Worksheet PDF">
                </div>
                <div class="col-md-6 mb-2">
                    <input class="form-control ot-input" name="materials[url][]" value="" placeholder="Link">
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
        <p class="text-secondary mb-2" style="font-size:.85rem;">"By the end of this lesson, the student can..." — one line each.</p>
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

<div class="text-end mt-3">
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
</style>
@endpush
