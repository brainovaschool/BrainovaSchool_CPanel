{{-- One editable custom field input, shared by Reel Setup's Pillar/Series
     row and its "other options" loop. $field: PortalReelCustomField,
     $current: this project's stored value for it (or null). --}}
@if ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_CHECKBOX)
    <div class="form-check mt-2">
        <input type="checkbox" class="form-check-input" id="cf_{{ $field->key }}"
               name="custom_fields[{{ $field->key }}]" value="1" {{ $current ? 'checked' : '' }}>
        <label class="form-check-label" for="cf_{{ $field->key }}">{{ $field->label }}</label>
    </div>
@elseif ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_SELECT && $field->list_key === \App\Models\Portal\PortalReelCustomField::SOURCE_REEL_CHARACTERS)
    @php($currentArr = (array) ($current ?? []))
    <select name="custom_fields[{{ $field->key }}][]" class="form-control select2_multy wide" multiple>
        @foreach ($field->options() as $opt)
            <option value="{{ $opt }}" {{ in_array((string) $opt, array_map('strval', $currentArr), true) ? 'selected' : '' }}>{{ $opt }}</option>
        @endforeach
    </select>
@elseif ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_SELECT)
    <select name="custom_fields[{{ $field->key }}]" class="nice-select niceSelect bordered_style wide">
        <option value="">—</option>
        @foreach ($field->options() as $opt)
            <option value="{{ $opt }}" {{ (string) $current === (string) $opt ? 'selected' : '' }}>{{ $opt }}</option>
        @endforeach
    </select>
@elseif ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_TEXTAREA)
    <textarea name="custom_fields[{{ $field->key }}]" class="form-control ot-input" rows="2">{{ $current }}</textarea>
@elseif ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_FILE)
    <div class="ot_fileUploader left-side mb-1">
        <input class="form-control ot-input bn-file-placeholder" type="text" placeholder="No file chosen" readonly>
        <button class="primary-btn-small-input" type="button">
            <label class="btn btn-lg ot-btn-primary" for="cf_{{ $field->key }}_file">Browse</label>
            <input type="file" class="d-none bn-file-input" id="cf_{{ $field->key }}_file" name="custom_fields[{{ $field->key }}]">
        </button>
    </div>
    @php($currentUpload = $current ? \App\Models\Upload::find($current) : null)
    @if ($currentUpload)
        <p class="text-secondary mt-1 mb-0"><a href="{{ globalAsset($currentUpload->path) }}" target="_blank">Current file</a></p>
    @endif
@else
    <input type="{{ $field->type }}" name="custom_fields[{{ $field->key }}]" class="form-control ot-input" value="{{ $current }}">
@endif
