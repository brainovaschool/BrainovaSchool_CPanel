@php $p = $data['program'] ?? null; @endphp
@php $accents = ['teal', 'ocean', 'amber', 'indigo', 'violet', 'rose', 'coral', 'slate']; @endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('settings.category') }} <span class="fillable">*</span></label>
        <select class="nice-select niceSelect bordered_style wide @error('program_category_id') is-invalid @enderror"
            name="program_category_id" id="programCategorySelect">
            <option value="">{{ ___('common.select') }}</option>
            @foreach ($data['categories'] as $cat)
                <option value="{{ $cat->id }}" {{ old('program_category_id', $p->program_category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        @error('program_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('settings.focus_area') }}</label>
        <select class="form-control ot-input @error('program_focus_id') is-invalid @enderror" name="program_focus_id" id="programFocusSelect">
            <option value="">— {{ ___('common.none') }} —</option>
            @foreach ($data['focuses'] as $fo)
                <option value="{{ $fo->id }}" data-category="{{ $fo->program_category_id }}"
                    {{ old('program_focus_id', $p->program_focus_id ?? '') == $fo->id ? 'selected' : '' }}>
                    {{ @$fo->category->name }} — {{ $fo->name }}
                </option>
            @endforeach
        </select>
        @error('program_focus_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-8 mb-3">
        <label class="form-label">{{ ___('common.title') }} <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('title') is-invalid @enderror" name="title"
            value="{{ old('title', $p->title ?? '') }}" placeholder="{{ ___('common.enter_title') }}">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('settings.badge') }}</label>
        <input class="form-control ot-input @error('badge') is-invalid @enderror" name="badge"
            value="{{ old('badge', $p->badge ?? '') }}" placeholder="{{ ___('settings.eg_maths') }}">
        @error('badge')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">{{ ___('common.Description') }}</label>
        <textarea class="form-control ot-textarea @error('description') is-invalid @enderror" name="description" rows="2"
            placeholder="{{ ___('settings.card_summary_one_or_two_lines') }}">{{ old('description', $p->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('settings.age_range') }}</label>
        <input class="form-control ot-input" name="age_range" value="{{ old('age_range', $p->age_range ?? '') }}" placeholder="Ages 8–12">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('settings.grade') }}</label>
        <input class="form-control ot-input" name="grade" value="{{ old('grade', $p->grade ?? '') }}" placeholder="Grade 4–6">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('settings.lessons') }}</label>
        <input class="form-control ot-input" name="lessons" value="{{ old('lessons', $p->lessons ?? '') }}" placeholder="12 lessons">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('settings.duration') }}</label>
        <input class="form-control ot-input" name="duration" value="{{ old('duration', $p->duration ?? '') }}" placeholder="6 weeks">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('settings.enrolment_note') }}</label>
        <input class="form-control ot-input" name="enrolled" value="{{ old('enrolled', $p->enrolled ?? '') }}" placeholder="Open enrollment">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('common.price') }}</label>
        <input class="form-control ot-input" name="price" value="{{ old('price', $p->price ?? '') }}" placeholder="PKR 18,000 / term">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('settings.accent_colour') }}</label>
        <select class="nice-select niceSelect bordered_style wide" name="accent">
            @foreach ($accents as $a)
                <option value="{{ $a }}" {{ old('accent', $p->accent ?? 'teal') === $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('common.image') }} {{ ___('common.(1200 x 800 px)') }}</label>
        <div class="ot_fileUploader left-side mb-2 @error('image') is-invalid @enderror">
            <input class="form-control" type="text" placeholder="{{ ___('common.image') }}" readonly id="placeholder">
            <button class="primary-btn-small-input" type="button">
                <label class="btn btn-lg ot-btn-primary" for="fileBrouse">{{ ___('common.browse') }}</label>
                <input type="file" class="d-none form-control" name="image" accept="image/*" id="fileBrouse">
            </button>
        </div>
        @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @if ($p && $p->upload)
            <img src="{{ globalAsset($p->upload->path, '1200X800.webp') }}" alt="" style="max-height:80px;border-radius:8px;margin-top:6px">
        @endif
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('settings.image_url_optional') }}</label>
        <input class="form-control ot-input @error('image_url') is-invalid @enderror" name="image_url"
            value="{{ old('image_url', $p->image_url ?? '') }}" placeholder="https://…">
        @error('image_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">{{ ___('settings.uploaded_image_wins_note') }}</small>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('settings.overview_paragraphs') }}</label>
        <textarea class="form-control ot-textarea" name="overview" rows="4"
            placeholder="{{ ___('settings.one_paragraph_per_line') }}">{{ old('overview', $p ? implode("\n", (array) $p->overview) : '') }}</textarea>
        <small class="text-secondary">{{ ___('settings.one_item_per_line') }}</small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('settings.highlights') }}</label>
        <textarea class="form-control ot-textarea" name="highlights" rows="4"
            placeholder="{{ ___('settings.one_bullet_per_line') }}">{{ old('highlights', $p ? implode("\n", (array) $p->highlights) : '') }}</textarea>
        <small class="text-secondary">{{ ___('settings.one_item_per_line') }}</small>
    </div>

    <div class="col-md-8 mb-3">
        <label class="form-label">{{ ___('settings.format_line') }}</label>
        <input class="form-control ot-input" name="format" value="{{ old('format', $p->format ?? '') }}"
            placeholder="3 × 55-minute sessions weekly + homework">
    </div>
    <div class="col-md-2 mb-3">
        <label class="form-label">{{ ___('common.Serial') }}</label>
        <input class="form-control ot-input" name="sort_order" type="number" min="0" value="{{ old('sort_order', $p->sort_order ?? 0) }}">
    </div>
    <div class="col-md-2 mb-3">
        <label class="form-label">{{ ___('common.status') }} <span class="fillable">*</span></label>
        <select class="nice-select niceSelect bordered_style wide" name="status">
            <option value="{{ App\Enums\Status::ACTIVE }}" {{ old('status', $p->status ?? 1) == 1 ? 'selected' : '' }}>{{ ___('common.active') }}</option>
            <option value="{{ App\Enums\Status::INACTIVE }}" {{ old('status', $p->status ?? 1) == 0 ? 'selected' : '' }}>{{ ___('common.inactive') }}</option>
        </select>
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">{{ ___('settings.meta_description') }}</label>
        <textarea class="form-control ot-textarea" name="meta_description" rows="2"
            placeholder="{{ ___('settings.for_search_engines') }}">{{ old('meta_description', $p->meta_description ?? '') }}</textarea>
    </div>
</div>

<div class="text-end mt-3">
    <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i> </span>{{ ___('common.submit') }}</button>
</div>

@push('script')
<script>
(function () {
    var cat = document.getElementById('programCategorySelect');
    var foc = document.getElementById('programFocusSelect');
    if (!cat || !foc) return;
    function sync() {
        var v = cat.value;
        Array.prototype.forEach.call(foc.options, function (o) {
            if (!o.value) { o.hidden = false; return; }
            o.hidden = v !== '' && o.getAttribute('data-category') !== v;
        });
        if (foc.selectedOptions.length && foc.selectedOptions[0].hidden) {
            foc.value = '';
        }
    }
    cat.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
