@php($reel = $reel ?? null)
@php($isManager = $isManager ?? false)
@php($seriesList = $series ?? [])

{{-- Assignment (who/deadline), scheduling (upload date), and the prompt/
     video links themselves all happen later, as their own actions on the
     Reel's detail page once it exists - this form is only ever "what is
     this reel about", never "where is it in the pipeline". --}}

<div class="row g-3">
    <div class="col-md-{{ $isManager ? 4 : 6 }}">
        <label class="form-label">Series</label>
        <select name="series_name" id="reelSeriesSelect" class="nice-select niceSelect bordered_style wide">
            <option value="">—</option>
            @foreach ($seriesList as $s)
                <option value="{{ $s->name }}"
                        data-defaults="{{ json_encode($s->defaults ?: []) }}"
                        {{ old('series_name', $reel->series_name ?? '') === $s->name ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
        @unless ($isManager)
            <p class="text-secondary mb-0 mt-1">Picking a series fills in its Pillar/Format/Characters for you.</p>
        @endunless
    </div>
    <div class="col-md-{{ $isManager ? 4 : 6 }}">
        <label class="form-label">Topic</label>
        <input type="text" name="title" class="form-control ot-input" value="{{ old('title', $reel->title ?? '') }}" required maxlength="150">
    </div>

    @if ($isManager)
        <div class="col-md-4">
            <label class="form-label">Format</label>
            <select name="format" id="reelFormatSelect" class="nice-select niceSelect bordered_style wide">
                @foreach ($settings->reel_formats ?: \App\Models\Portal\PortalSetting::DEFAULT_REEL_FORMATS as $f)
                    <option value="{{ $f }}" {{ old('format', $reel->format ?? 'Reel') === $f ? 'selected' : '' }}>{{ $f }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Pillar</label>
            <select name="category" id="reelPillarSelect" class="nice-select niceSelect bordered_style wide">
                <option value="">—</option>
                @foreach ($settings->reel_categories ?: \App\Models\Portal\PortalSetting::DEFAULT_REEL_CATEGORIES as $cat)
                    <option value="{{ $cat }}" {{ old('category', $reel->category ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="col-md-{{ $isManager ? 4 : 12 }}">
        <label class="form-label">Hook</label>
        <input type="text" name="hook" class="form-control ot-input" value="{{ old('hook', $reel->hook ?? '') }}">
    </div>

    @if ($isManager)
        <div class="col-md-6">
            <label class="form-label">Prompt text (AI/edit prompt)</label>
            <textarea name="prompt_text" class="form-control ot-input" rows="3">{{ old('prompt_text', $reel->prompt_text ?? '') }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Prompt file (e.g. a Word doc)</label>
            <input type="file" name="prompt_file" class="form-control ot-input">
            @if (($reel->promptUpload ?? null))
                <p class="text-secondary mt-1 mb-0"><a href="{{ globalAsset($reel->promptUpload->path) }}" target="_blank">Current file</a></p>
            @endif
        </div>

        <div class="col-md-6">
            <label class="form-label">Thumbnail (optional)</label>
            <input type="file" name="thumb_file" class="form-control ot-input">
            @if (($reel->thumbUpload ?? null))
                <p class="text-secondary mt-1 mb-0"><a href="{{ globalAsset($reel->thumbUpload->path) }}" target="_blank">Current thumbnail</a></p>
            @endif
        </div>
        <div class="col-md-6">
            <label class="form-label">Note</label>
            <textarea name="note" class="form-control ot-input" rows="2">{{ old('note', $reel->note ?? '') }}</textarea>
        </div>

        @php($customFields = $customFields ?? [])
        @foreach ($customFields as $field)
            @php($current = old('custom_fields.' . $field->key, ($reel->custom_fields ?? [])[$field->key] ?? null))
            @php($isCharacterField = $field->list_key === \App\Models\Portal\PortalReelCustomField::SOURCE_REEL_CHARACTERS)
            <div class="col-md-6">
                @if ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_CHECKBOX)
                    <div class="form-check mt-4">
                        <input type="checkbox" class="form-check-input" id="cf_{{ $field->key }}"
                               name="custom_fields[{{ $field->key }}]" value="1" {{ $current ? 'checked' : '' }}>
                        <label class="form-check-label" for="cf_{{ $field->key }}">{{ $field->label }}</label>
                    </div>
                @else
                    <label class="form-label">{{ $field->label }}</label>
                    @if ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_SELECT && $isCharacterField)
                        @php($currentArr = (array) ($current ?? []))
                        <select name="custom_fields[{{ $field->key }}][]" class="form-control ot-input" multiple size="4" data-source="reel_characters">
                            @foreach ($field->options() as $opt)
                                <option value="{{ $opt }}" {{ in_array((string) $opt, array_map('strval', $currentArr), true) ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        <p class="text-secondary mb-0 mt-1">Hold Ctrl (Cmd on Mac) to pick more than one.</p>
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
                        <input type="file" name="custom_fields[{{ $field->key }}]" class="form-control ot-input">
                        @php($currentUpload = $current ? \App\Models\Upload::find($current) : null)
                        @if ($currentUpload)
                            <p class="text-secondary mt-1 mb-0"><a href="{{ globalAsset($currentUpload->path) }}" target="_blank">Current file</a></p>
                        @endif
                    @else
                        <input type="{{ $field->type }}" name="custom_fields[{{ $field->key }}]" class="form-control ot-input" value="{{ $current }}">
                    @endif
                @endif
            </div>
        @endforeach
    @endif
</div>

@push('script')
<script>
    (function () {
        var seriesSelect = document.getElementById('reelSeriesSelect');
        if (!seriesSelect) return;
        var formatSelect = document.getElementById('reelFormatSelect');
        var pillarSelect = document.getElementById('reelPillarSelect');
        var charactersSelect = document.querySelector('select[data-source="reel_characters"]');

        function setSelectIfBlank(select, value) {
            if (!select || !value || select.value) return;
            select.value = value;
            if (window.jQuery) { jQuery(select).niceSelect('update'); }
        }

        function setMultiIfBlank(select, values) {
            if (!select || !values || !values.length) return;
            var hasSelection = Array.prototype.some.call(select.options, function (o) { return o.selected; });
            if (hasSelection) return;
            values = Array.isArray(values) ? values : [values];
            Array.prototype.forEach.call(select.options, function (o) {
                o.selected = values.indexOf(o.value) !== -1;
            });
        }

        function setFieldIfBlank(key, value) {
            var el = document.querySelector('[name="custom_fields[' + key + ']"]');
            if (!el) return;
            if (el.type === 'checkbox') {
                if (el.checked) return;
                el.checked = !!value;
                return;
            }
            if (el.value) return;
            el.value = Array.isArray(value) ? value.join(', ') : value;
            if (el.classList.contains('niceSelect') && window.jQuery) { jQuery(el).niceSelect('update'); }
        }

        // Each <option> carries the whole series' defaults as JSON -
        // {category, format, custom_fields: {field_key: value}} - so
        // picking a series fills in everything it has a default for,
        // without overwriting anything already typed in.
        function applySeriesDefaults() {
            var opt = seriesSelect.options[seriesSelect.selectedIndex];
            if (!opt) return;
            var defaults = {};
            try { defaults = JSON.parse(opt.getAttribute('data-defaults') || '{}'); } catch (e) { /* ignore */ }

            setSelectIfBlank(pillarSelect, defaults.category);
            setSelectIfBlank(formatSelect, defaults.format);

            var customFields = defaults.custom_fields || {};
            Object.keys(customFields).forEach(function (key) {
                if (charactersSelect && charactersSelect.name === 'custom_fields[' + key + '][]') {
                    setMultiIfBlank(charactersSelect, customFields[key]);
                } else {
                    setFieldIfBlank(key, customFields[key]);
                }
            });
        }

        // nice-select only fires its change event through jQuery's own
        // trigger, not a plain addEventListener (same issue fixed
        // elsewhere in this app for the Leave Request role toggle).
        if (window.jQuery) {
            jQuery(seriesSelect).on('change', applySeriesDefaults);
        } else {
            seriesSelect.addEventListener('change', applySeriesDefaults);
        }
    })();
</script>
@endpush
