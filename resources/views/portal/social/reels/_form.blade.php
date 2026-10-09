@php($reel = $reel ?? null)
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="ot-input" value="{{ old('title', $reel->title ?? '') }}" required maxlength="150">
    </div>
    <div class="col-md-3">
        <label class="form-label">Format</label>
        <select name="format" class="nice-select niceSelect bordered_style wide">
            @foreach (['Reel', 'Carousel', 'Post'] as $f)
                <option value="{{ $f }}" {{ old('format', $reel->format ?? 'Reel') === $f ? 'selected' : '' }}>{{ $f }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Category</label>
        <select name="category" class="nice-select niceSelect bordered_style wide">
            <option value="">—</option>
            @foreach ($settings->reel_categories ?: \App\Models\Portal\PortalSetting::DEFAULT_REEL_CATEGORIES as $cat)
                <option value="{{ $cat }}" {{ old('category', $reel->category ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Hook</label>
        <input type="text" name="hook" class="ot-input" value="{{ old('hook', $reel->hook ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Planned date</label>
        <input type="date" name="planned_date" class="ot-input" value="{{ old('planned_date', optional($reel->planned_date ?? null)->format('Y-m-d')) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Drive link</label>
        <input type="text" name="drive_link" class="ot-input" value="{{ old('drive_link', $reel->drive_link ?? '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">Prompt text (AI/edit prompt)</label>
        <textarea name="prompt_text" class="ot-input" rows="3">{{ old('prompt_text', $reel->prompt_text ?? '') }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">Prompt link (optional)</label>
        <input type="text" name="prompt_link" class="ot-input" value="{{ old('prompt_link', $reel->prompt_link ?? '') }}">
        <label class="form-label mt-2">Prompt file (e.g. a Word doc)</label>
        <input type="file" name="prompt_file" class="ot-input">
        @if (($reel->promptUpload ?? null))
            <p class="text-secondary mt-1 mb-0"><a href="{{ globalAsset($reel->promptUpload->path) }}" target="_blank">Current file</a></p>
        @endif
    </div>

    <div class="col-md-6">
        <label class="form-label">Thumbnail (optional)</label>
        <input type="file" name="thumb_file" class="ot-input">
        @if (($reel->thumbUpload ?? null))
            <p class="text-secondary mt-1 mb-0"><a href="{{ globalAsset($reel->thumbUpload->path) }}" target="_blank">Current thumbnail</a></p>
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label">Note</label>
        <textarea name="note" class="ot-input" rows="2">{{ old('note', $reel->note ?? '') }}</textarea>
    </div>
</div>
