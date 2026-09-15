@php $s = $data['skill'] ?? null; @endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('common.title') }} <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('title') is-invalid @enderror" name="title"
            value="{{ old('title', $s->title ?? '') }}" placeholder="{{ ___('settings.eg_two_digit_addition') }}">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">{{ ___('settings.skill_title_note') }}</small>
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('common.class') }} <span class="fillable">*</span></label>
        <select class="form-control ot-input @error('classes_id') is-invalid @enderror" name="classes_id">
            <option value="">{{ ___('common.select') }}</option>
            @foreach (\App\Models\Academic\Classes::active()->orderBy('name')->get() as $class)
                <option value="{{ $class->id }}" {{ old('classes_id', $s->classes_id ?? '') == $class->id ? 'selected' : '' }}>
                    {{ $class->class_tran }}
                </option>
            @endforeach
        </select>
        @error('classes_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('common.subject') }} <span class="fillable">*</span></label>
        <select class="form-control ot-input @error('subject_id') is-invalid @enderror" name="subject_id">
            <option value="">{{ ___('common.select') }}</option>
            @foreach (\App\Models\Academic\Subject::active()->orderBy('name')->get() as $subject)
                <option value="{{ $subject->id }}" {{ old('subject_id', $s->subject_id ?? '') == $subject->id ? 'selected' : '' }}>
                    {{ $subject->name }}
                </option>
            @endforeach
        </select>
        @error('subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">{{ ___('common.description') }}</label>
        <textarea class="form-control ot-textarea @error('description') is-invalid @enderror" name="description" rows="2"
            placeholder="{{ ___('settings.what_mastering_this_skill_looks_like') }}">{{ old('description', $s->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.Serial') }}</label>
        <input class="form-control ot-input @error('sort_order') is-invalid @enderror" name="sort_order" type="number" min="0"
            value="{{ old('sort_order', $s->sort_order ?? 0) }}">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.status') }} <span class="fillable">*</span></label>
        <select class="form-control ot-input @error('status') is-invalid @enderror" name="status">
            <option value="{{ App\Enums\Status::ACTIVE }}" {{ old('status', $s->status ?? 1) == 1 ? 'selected' : '' }}>{{ ___('common.active') }}</option>
            <option value="{{ App\Enums\Status::INACTIVE }}" {{ old('status', $s->status ?? 1) == 0 ? 'selected' : '' }}>{{ ___('common.inactive') }}</option>
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="text-end mt-3">
    <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i> </span>{{ ___('common.submit') }}</button>
</div>
