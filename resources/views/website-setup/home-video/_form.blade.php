@php
    $s = $data['item'] ?? null;
@endphp

<div class="row">
    <div class="col-md-8 mb-3">
        <label class="form-label">Video link <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('video_url') is-invalid @enderror" name="video_url"
            value="{{ old('video_url', $s->video_url ?? '') }}"
            placeholder="A YouTube, Facebook or Instagram link, or a direct link to a video you host yourself">
        @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">The video itself is never uploaded here — only the link, so the site stays light.</small>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Orientation <span class="fillable">*</span></label>
        <select class="form-control ot-input @error('orientation') is-invalid @enderror" name="orientation">
            @foreach (App\Models\WebsiteSetup\HomeVideo::ORIENTATIONS as $key => $label)
                <option value="{{ $key }}" {{ old('orientation', $s->orientation ?? 'landscape') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('orientation')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">Can't be detected from the link — pick it so the card on the homepage isn't stretched or squashed.</small>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Title / caption (optional)</label>
        <input class="form-control ot-input" name="title" maxlength="150"
            value="{{ old('title', $s->title ?? '') }}" placeholder="Shown under the video on the homepage">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3 d-flex align-items-end">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="autoplay" id="autoplayCheck" value="1" {{ old('autoplay', $s->autoplay ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="autoplayCheck">
                Autoplay (muted) as soon as the page loads
            </label>
            <br><small class="text-secondary">Browsers only ever allow autoplay without sound — visitors can tap to unmute. Instagram videos can't autoplay at all; this is ignored for them.</small>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.Serial') }}</label>
        <input class="form-control ot-input" name="sort_order" type="number" min="0" value="{{ old('sort_order', $s->sort_order ?? 0) }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.status') }} <span class="fillable">*</span></label>
        <select class="form-control ot-input" name="status">
            <option value="{{ App\Enums\Status::ACTIVE }}" {{ old('status', $s->status ?? 1) == 1 ? 'selected' : '' }}>{{ ___('common.active') }}</option>
            <option value="{{ App\Enums\Status::INACTIVE }}" {{ old('status', $s->status ?? 1) == 0 ? 'selected' : '' }}>{{ ___('common.inactive') }}</option>
        </select>
    </div>
</div>

<div class="text-end mt-3">
    <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i> </span>{{ ___('common.submit') }}</button>
</div>
