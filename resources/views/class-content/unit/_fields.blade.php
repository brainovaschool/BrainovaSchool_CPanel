{{-- Expects: $idSuffix (unique per form on the page), $options (comboOptions()), $unit (nullable, existing unit) --}}
@php
    $selected = $unit ? $unit->classes_id . '|' . $unit->section_id . '|' . $unit->subject_id : '';
@endphp
<div class="row">
    <div class="col-md-8 mb-2">
        <label class="form-label">Class, Section &amp; Subject <span class="fillable">*</span></label>
        <select class="form-control ot-input" id="unitCombo{{ $idSuffix }}">
            <option value="">{{ ___('common.select') }}</option>
            @foreach ($options as $opt)
                @php $value = $opt['classes_id'] . '|' . $opt['section_id'] . '|' . $opt['subject_id']; @endphp
                <option value="{{ $value }}" {{ $selected === $value ? 'selected' : '' }}>
                    {{ $opt['class_name'] }}{{ $opt['section_name'] ? ' - ' . $opt['section_name'] : '' }} — {{ $opt['subject_name'] }}
                </option>
            @endforeach
        </select>
        <input type="hidden" name="classes_id" id="unitClasses{{ $idSuffix }}" value="{{ $unit->classes_id ?? '' }}">
        <input type="hidden" name="section_id" id="unitSection{{ $idSuffix }}" value="{{ $unit->section_id ?? '' }}">
        <input type="hidden" name="subject_id" id="unitSubject{{ $idSuffix }}" value="{{ $unit->subject_id ?? '' }}">
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label">{{ ___('common.Serial') }}</label>
        <input class="form-control ot-input" name="sort_order" type="number" min="0" value="{{ $unit->sort_order ?? 0 }}">
    </div>
    <div class="col-md-12 mb-2">
        <label class="form-label">Unit title <span class="fillable">*</span></label>
        <input class="form-control ot-input" name="title" maxlength="150" value="{{ $unit->title ?? '' }}" placeholder="e.g. Unit 1 — Animation &amp; Game Basics">
    </div>
    <div class="col-md-12 mb-2">
        <label class="form-label">Description (optional)</label>
        <textarea class="form-control ot-textarea" name="description" rows="2">{{ $unit->description ?? '' }}</textarea>
    </div>
</div>
<button type="submit" class="btn btn-sm ot-btn-primary mt-1">{{ $unit ? 'Save changes' : 'Add unit' }}</button>

@push('script')
<script>
(function () {
    var combo = document.getElementById('unitCombo{{ $idSuffix }}');
    var classesInput = document.getElementById('unitClasses{{ $idSuffix }}');
    var sectionInput  = document.getElementById('unitSection{{ $idSuffix }}');
    var subjectInput  = document.getElementById('unitSubject{{ $idSuffix }}');
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
