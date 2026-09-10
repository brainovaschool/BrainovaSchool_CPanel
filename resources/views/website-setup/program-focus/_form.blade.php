@php $f = $data['focus'] ?? null; @endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('settings.category') }} <span class="fillable">*</span></label>
        <select class="nice-select niceSelect bordered_style wide @error('program_category_id') is-invalid @enderror" name="program_category_id">
            <option value="">{{ ___('common.select') }}</option>
            @foreach ($data['categories'] as $cat)
                <option value="{{ $cat->id }}" {{ old('program_category_id', $f->program_category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        @error('program_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('common.name') }} <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('name') is-invalid @enderror" name="name"
            value="{{ old('name', $f->name ?? '') }}" placeholder="{{ ___('common.enter_name') }}">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">{{ ___('common.Description') }}</label>
        <textarea class="form-control ot-textarea @error('description') is-invalid @enderror" name="description" rows="2"
            placeholder="{{ ___('settings.optional_short_line') }}">{{ old('description', $f->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.Serial') }}</label>
        <input class="form-control ot-input @error('sort_order') is-invalid @enderror" name="sort_order" type="number" min="0"
            value="{{ old('sort_order', $f->sort_order ?? 0) }}">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.status') }} <span class="fillable">*</span></label>
        <select class="nice-select niceSelect bordered_style wide @error('status') is-invalid @enderror" name="status">
            <option value="{{ App\Enums\Status::ACTIVE }}" {{ old('status', $f->status ?? 1) == 1 ? 'selected' : '' }}>{{ ___('common.active') }}</option>
            <option value="{{ App\Enums\Status::INACTIVE }}" {{ old('status', $f->status ?? 1) == 0 ? 'selected' : '' }}>{{ ___('common.inactive') }}</option>
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="text-end mt-3">
    <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i> </span>{{ ___('common.submit') }}</button>
</div>
