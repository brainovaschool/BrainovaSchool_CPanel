@php $t = $data['testimonial'] ?? null; @endphp

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('settings.type') }} <span class="fillable">*</span></label>
        <select class="nice-select niceSelect bordered_style wide" name="type">
            <option value="testimonial" {{ old('type', $t->type ?? 'testimonial') === 'testimonial' ? 'selected' : '' }}>{{ ___('settings.testimonial') }}</option>
            <option value="review" {{ old('type', $t->type ?? '') === 'review' ? 'selected' : '' }}>{{ ___('settings.review') }}</option>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.name') }} <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('name') is-invalid @enderror" name="name" value="{{ old('name', $t->name ?? '') }}" placeholder="{{ ___('common.enter_name') }}">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('settings.role_label') }}</label>
        <input class="form-control ot-input" name="role" value="{{ old('role', $t->role ?? '') }}" placeholder="e.g. Parent, Grade 5">
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">{{ ___('settings.quote') }} <span class="fillable">*</span></label>
        <textarea class="form-control ot-textarea @error('quote') is-invalid @enderror" name="quote" rows="3" placeholder="{{ ___('settings.what_they_said') }}">{{ old('quote', $t->quote ?? '') }}</textarea>
        @error('quote')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('settings.rating') }}</label>
        <select class="nice-select niceSelect bordered_style wide" name="rating">
            <option value="">{{ ___('settings.no_rating') }}</option>
            @for ($i = 5; $i >= 1; $i--)
                <option value="{{ $i }}" {{ (string) old('rating', $t->rating ?? '') === (string) $i ? 'selected' : '' }}>{{ str_repeat('★', $i) }} ({{ $i }})</option>
            @endfor
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('common.Serial') }}</label>
        <input class="form-control ot-input" name="sort_order" type="number" min="0" value="{{ old('sort_order', $t->sort_order ?? 0) }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('common.status') }} <span class="fillable">*</span></label>
        <select class="nice-select niceSelect bordered_style wide" name="status">
            <option value="{{ App\Enums\Status::ACTIVE }}" {{ old('status', $t->status ?? 1) == 1 ? 'selected' : '' }}>{{ ___('common.active') }}</option>
            <option value="{{ App\Enums\Status::INACTIVE }}" {{ old('status', $t->status ?? 1) == 0 ? 'selected' : '' }}>{{ ___('common.inactive') }}</option>
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('settings.image_url_optional') }}</label>
        <input class="form-control ot-input @error('image_url') is-invalid @enderror" name="image_url" value="{{ old('image_url', $t->image_url ?? '') }}" placeholder="https://…">
        @error('image_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('common.image') }} (120 x 120 px)</label>
        <div class="ot_fileUploader left-side mb-2 @error('image') is-invalid @enderror">
            <input class="form-control" type="text" placeholder="{{ ___('common.image') }}" readonly id="placeholder">
            <button class="primary-btn-small-input" type="button">
                <label class="btn btn-lg ot-btn-primary" for="fileBrouse">{{ ___('common.browse') }}</label>
                <input type="file" class="d-none form-control" name="image" accept="image/*" id="fileBrouse">
            </button>
        </div>
        @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @if ($t && $t->upload)
            <img src="{{ globalAsset($t->upload->path, '120X120.webp') }}" alt="" style="height:56px;border-radius:50%;margin-top:6px">
        @endif
    </div>
</div>

<div class="text-end mt-3">
    <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i> </span>{{ ___('common.submit') }}</button>
</div>
