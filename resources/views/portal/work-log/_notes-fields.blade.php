{{--
    Notes + a reference link + an optional document upload - the task
    detail someone wants to leave with an entry. A new upload replaces
    whatever was attached before; leaving the file picker alone keeps it.

    Expects: $prefix (unique per row), $notesValue, $linkValue,
    $currentUpload (nullable Upload model - the file already attached).
--}}
@php($fileInputId = $prefix . 'Doc')
<input type="text" name="notes" value="{{ $notesValue }}" class="form-control ot-input" placeholder="Notes / task details (optional)" style="min-width:180px">
<input type="url" name="link" value="{{ $linkValue }}" class="form-control ot-input" placeholder="Link (optional)" style="min-width:160px">
<div class="ot_fileUploader left-side mb-1">
    <input class="form-control ot-input bn-file-placeholder" type="text" placeholder="No file" readonly style="min-width:110px" value="{{ $currentUpload ? basename($currentUpload->path) : '' }}">
    <button class="primary-btn-small-input" type="button">
        <label class="btn btn-sm ot-btn-primary" for="{{ $fileInputId }}">Browse</label>
        <input type="file" class="d-none bn-file-input" id="{{ $fileInputId }}" name="document">
    </button>
</div>
@if ($currentUpload)
    <a href="{{ globalAsset($currentUpload->path) }}" target="_blank" class="text-secondary" style="font-size:12px;">Current file</a>
@endif
