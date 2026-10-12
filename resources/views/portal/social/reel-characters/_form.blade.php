@php $character = $character ?? null; @endphp

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <label class="form-label">Name <span class="fillable">*</span></label>
        <input type="text" name="name" class="form-control ot-input" value="{{ old('name', $character->name ?? '') }}" placeholder="e.g. Brainbot" required>
    </div>

    <div class="col-12 col-md-6 mb-3">
        <label class="form-label">Portrait image</label>
        <div class="ot_fileUploader left-side mb-2 @error('portrait') is-invalid @enderror">
            <input class="form-control" type="text" placeholder="Choose an image" readonly id="placeholder">
            <button class="primary-btn-small-input" type="button">
                <label class="btn btn-lg ot-btn-primary" for="fileBrouse">{{ ___('common.browse') }}</label>
                <input type="file" class="d-none form-control" name="portrait" accept="image/*" id="fileBrouse">
            </button>
        </div>
        @error('portrait')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <small class="text-secondary d-block mt-1">Portrait orientation, 9:16 (e.g. 1080×1920) — used as the visual reference for video generation.</small>
        @if ($character && $character->portrait)
            <div class="mt-2"><img src="{{ globalAsset($character->portrait->path) }}" style="height:110px;"></div>
        @endif
    </div>

    <div class="col-12 mb-3">
        <label class="form-label">Visual appearance &amp; consistency</label>
        <textarea name="appearance_text" rows="4" class="form-control ot-textarea"
            placeholder="What they look like, clothing, colors, physical features, personality — everything that must stay consistent across scenes.">{{ old('appearance_text', $character->appearance_text ?? '') }}</textarea>
    </div>

    <div class="col-12 mb-3">
        <label class="form-label">Voice profile</label>
        <textarea name="voice_profile_text" rows="3" class="form-control ot-textarea"
            placeholder="Tone, pitch, speaking style, accent, pace.">{{ old('voice_profile_text', $character->voice_profile_text ?? '') }}</textarea>
    </div>

    <div class="col-12 mb-3">
        <label class="form-label">Character-specific rules</label>
        <textarea name="rules_text" rows="4" class="form-control ot-textarea"
            placeholder="Role, behaviour, subject expertise, speaking style, and any restrictions this character must follow.">{{ old('rules_text', $character->rules_text ?? '') }}</textarea>
    </div>

    <div class="col-12 col-md-4 mb-3">
        <label class="form-label">Sort order</label>
        <input type="number" name="sort_order" class="form-control ot-input" value="{{ old('sort_order', $character->sort_order ?? 0) }}">
    </div>

    <div class="col-12 col-md-4 mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-control ot-input">
            <option value="1" {{ old('status', $character->status ?? 1) == 1 ? 'selected' : '' }}>Active</option>
            <option value="0" {{ old('status', $character->status ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
</div>
