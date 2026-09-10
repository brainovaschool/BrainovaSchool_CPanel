@php $c = $data['category'] ?? null; @endphp
@php
    $accents = ['teal', 'ocean', 'amber', 'indigo', 'violet', 'rose', 'coral', 'slate'];
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('common.name') }} <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('name') is-invalid @enderror" name="name"
            value="{{ old('name', $c->name ?? '') }}" placeholder="{{ ___('common.enter_name') }}">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">{{ ___('settings.slug_auto_note') }}</small>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('settings.tagline') }}</label>
        <input class="form-control ot-input @error('tagline') is-invalid @enderror" name="tagline"
            value="{{ old('tagline', $c->tagline ?? '') }}" placeholder="{{ ___('settings.short_one_liner') }}">
        @error('tagline')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('settings.hero_title') }}</label>
        <input class="form-control ot-input @error('hero_title') is-invalid @enderror" name="hero_title"
            value="{{ old('hero_title', $c->hero_title ?? '') }}" placeholder="{{ ___('settings.landing_page_headline') }}">
        @error('hero_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('common.status') }} <span class="fillable">*</span></label>
        <select class="nice-select niceSelect bordered_style wide @error('status') is-invalid @enderror" name="status">
            <option value="{{ App\Enums\Status::ACTIVE }}" {{ old('status', $c->status ?? 1) == 1 ? 'selected' : '' }}>{{ ___('common.active') }}</option>
            <option value="{{ App\Enums\Status::INACTIVE }}" {{ old('status', $c->status ?? 1) == 0 ? 'selected' : '' }}>{{ ___('common.inactive') }}</option>
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">{{ ___('settings.hero_subtitle') }}</label>
        <textarea class="form-control ot-textarea @error('hero_subtitle') is-invalid @enderror" name="hero_subtitle" rows="2"
            placeholder="{{ ___('settings.one_or_two_sentences') }}">{{ old('hero_subtitle', $c->hero_subtitle ?? '') }}</textarea>
        @error('hero_subtitle')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('settings.accent_colour') }}</label>
        <select class="nice-select niceSelect bordered_style wide" name="accent">
            @foreach ($accents as $a)
                <option value="{{ $a }}" {{ old('accent', $c->accent ?? 'teal') === $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.Serial') }}</label>
        <input class="form-control ot-input @error('sort_order') is-invalid @enderror" name="sort_order" type="number" min="0"
            value="{{ old('sort_order', $c->sort_order ?? 0) }}">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('settings.image_url_optional') }}</label>
        <input class="form-control ot-input @error('image_url') is-invalid @enderror" name="image_url"
            value="{{ old('image_url', $c->image_url ?? '') }}" placeholder="https://…">
        @error('image_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('common.image') }} {{ ___('common.(1920 x 700 px)') }}</label>
        <div class="ot_fileUploader left-side mb-2 @error('image') is-invalid @enderror">
            <input class="form-control" type="text" placeholder="{{ ___('common.image') }}" readonly id="placeholder">
            <button class="primary-btn-small-input" type="button">
                <label class="btn btn-lg ot-btn-primary" for="fileBrouse">{{ ___('common.browse') }}</label>
                <input type="file" class="d-none form-control" name="image" accept="image/*" id="fileBrouse">
            </button>
        </div>
        @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @if ($c && $c->upload)
            <img src="{{ globalAsset($c->upload->path, '1920X700.webp') }}" alt="" style="max-height:90px;border-radius:8px;margin-top:6px">
        @endif
    </div>
</div>

<div class="text-end mt-3">
    <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i> </span>{{ ___('common.submit') }}</button>
</div>
