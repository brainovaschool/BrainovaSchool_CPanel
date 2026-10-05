@php
    $m = $data['item'] ?? null;
    $selected = old('classes_id')
        ? old('classes_id') . '|' . old('section_id') . '|' . old('subject_id')
        : ($m ? $m->classes_id . '|' . $m->section_id . '|' . $m->subject_id : '');
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Class, Section &amp; Subject <span class="fillable">*</span></label>
        <select class="form-control ot-input @error('classes_id') is-invalid @enderror @error('subject_id') is-invalid @enderror" id="comboSelect">
            <option value="">{{ ___('common.select') }}</option>
            @foreach ($data['options'] as $opt)
                @php $value = $opt['classes_id'] . '|' . $opt['section_id'] . '|' . $opt['subject_id']; @endphp
                <option value="{{ $value }}" {{ $selected === $value ? 'selected' : '' }}>
                    {{ $opt['class_name'] }}{{ $opt['section_name'] ? ' - ' . $opt['section_name'] : '' }} — {{ $opt['subject_name'] }}
                </option>
            @endforeach
        </select>
        <input type="hidden" name="classes_id" id="classesIdInput" value="{{ old('classes_id', $m->classes_id ?? '') }}">
        <input type="hidden" name="section_id" id="sectionIdInput" value="{{ old('section_id', $m->section_id ?? '') }}">
        <input type="hidden" name="subject_id" id="subjectIdInput" value="{{ old('subject_id', $m->subject_id ?? '') }}">
        @error('classes_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @error('subject_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @if ($data['options']->isEmpty())
            <small class="text-danger">No class/subject is assigned to you yet — ask admin to assign you to a class and subject first.</small>
        @endif
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Title <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('title') is-invalid @enderror" name="title" maxlength="150"
            value="{{ old('title', $m->title ?? '') }}" placeholder="e.g. Week 3 — Intro to Scratch Animation">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">Description (optional)</label>
        <textarea class="form-control ot-textarea" name="description" rows="3" maxlength="3000"
            placeholder="A short outline of what this module covers">{{ old('description', $m->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.Serial') }}</label>
        <input class="form-control ot-input" name="sort_order" type="number" min="0" value="{{ old('sort_order', $m->sort_order ?? 0) }}">
    </div>
</div>

<div class="text-end mt-3">
    <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i> </span>{{ ___('common.submit') }}</button>
</div>

@push('script')
<script>
(function () {
    var combo = document.getElementById('comboSelect');
    var classesInput = document.getElementById('classesIdInput');
    var sectionInput  = document.getElementById('sectionIdInput');
    var subjectInput  = document.getElementById('subjectIdInput');
    if (!combo) return;
    combo.addEventListener('change', function () {
        var parts = (combo.value || '').split('|');
        classesInput.value = parts[0] || '';
        sectionInput.value = parts[1] || '';
        subjectInput.value = parts[2] || '';
    });
})();
</script>
@endpush
