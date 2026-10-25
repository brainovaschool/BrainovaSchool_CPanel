@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Social Board Settings</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-body">
                <p class="text-secondary">Each block below is a named list — add as many as you want, rename any of
                    them, or delete ones you don't need. Blocks used elsewhere (e.g. "Formats" feeds the Reel form's
                    Format picker) keep working by this block's name, so renaming is safe — deleting one that's in
                    active use just leaves that picker with no options until you add a replacement block.</p>

                <form action="{{ route('portal-social-settings.update') }}" method="post">
                    @csrf

                    <div id="blocksContainer">
                        @foreach ($data['lists'] as $list)
                            <div class="settings-block mb-4 pb-4 border-bottom">
                                <input type="text" name="lists[{{ $list->id }}][label]" value="{{ $list->label }}"
                                       class="form-control ot-input mb-2 fw-bold" style="max-width:320px" required>
                                <p class="text-secondary mb-1">One per line.</p>
                                <textarea data-list-for="lists_{{ $list->id }}"
                                          data-field-name="lists[{{ $list->id }}][values][]"
                                          class="form-control ot-input mb-2" rows="4">{{ implode("\n", $list->values ?: []) }}</textarea>
                                <div id="lists_{{ $list->id }}Inputs">
                                    @foreach ($list->values ?: [] as $v)
                                        <input type="hidden" name="lists[{{ $list->id }}][values][]" value="{{ $v }}">
                                    @endforeach
                                </div>
                                <label class="d-inline-flex align-items-center gap-2 mt-1">
                                    <input type="checkbox" name="lists[{{ $list->id }}][delete]" value="1" class="delete-block-checkbox">
                                    <span class="text-danger">Delete this block</span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div id="newBlocksContainer"></div>

                    <button type="button" id="addBlockBtn" class="btn btn-outline-secondary mb-3">+ Add a block</button>
                    <br>
                    <button type="submit" class="btn btn-lg ot-btn-primary">Save Settings</button>
                </form>
            </div>
        </div>

        <div class="card ot-card mt-24">
            <div class="card-body">
                <h4>Reel form fields</h4>
                <p class="text-secondary">Extra fields on the Add/Edit Reel form, beyond the built-in ones. A
                    Dropdown field's options come from one of the blocks above — add a block there first, then
                    point a Dropdown field at it here.</p>

                <form action="{{ route('portal-social-settings.reel-fields') }}" method="post">
                    @csrf

                    <div id="fieldsContainer">
                        @foreach ($data['customFields'] as $field)
                            <div class="field-row mb-4 pb-4 border-bottom">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label">Field name</label>
                                        <input type="text" name="fields[{{ $field->id }}][label]" value="{{ $field->label }}" class="form-control ot-input" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Field type</label>
                                        <select name="fields[{{ $field->id }}][type]" class="nice-select niceSelect bordered_style wide field-type-select">
                                            @foreach ($data['fieldTypes'] as $typeKey => $typeLabel)
                                                <option value="{{ $typeKey }}" {{ $field->type === $typeKey ? 'selected' : '' }}>{{ $typeLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5 field-list-source" style="{{ $field->type === \App\Models\Portal\PortalReelCustomField::TYPE_SELECT ? '' : 'display:none' }}">
                                        <label class="form-label">Options come from</label>
                                        <select name="fields[{{ $field->id }}][list_key]" class="nice-select niceSelect bordered_style wide">
                                            <option value="">—</option>
                                            @foreach ($data['lists'] as $list)
                                                <option value="{{ $list->key }}" {{ $field->list_key === $list->key ? 'selected' : '' }}>{{ $list->label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <label class="d-inline-flex align-items-center gap-2 mt-2">
                                    <input type="checkbox" name="fields[{{ $field->id }}][delete]" value="1" class="delete-field-checkbox">
                                    <span class="text-danger">Delete this field</span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div id="newFieldsContainer"></div>

                    <button type="button" id="addFieldBtn" class="btn btn-outline-secondary mb-3">+ Add a field</button>
                    <br>
                    <button type="submit" class="btn btn-lg ot-btn-primary">Save Fields</button>
                </form>
            </div>
        </div>
    </div>

    <template id="newFieldTemplate">
        <div class="field-row mb-4 pb-4 border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Field name</label>
                    <input type="text" name="new_fields[__INDEX__][label]" value="" class="form-control ot-input" placeholder="Field name" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Field type</label>
                    <select name="new_fields[__INDEX__][type]" class="nice-select niceSelect bordered_style wide field-type-select">
                        @foreach ($data['fieldTypes'] as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5 field-list-source" style="display:none">
                    <label class="form-label">Options come from</label>
                    <select name="new_fields[__INDEX__][list_key]" class="nice-select niceSelect bordered_style wide">
                        <option value="">—</option>
                        @foreach ($data['lists'] as $list)
                            <option value="{{ $list->key }}">{{ $list->label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm mt-2 remove-new-field">Remove this field</button>
        </div>
    </template>

    <template id="newBlockTemplate">
        <div class="settings-block mb-4 pb-4 border-bottom">
            <input type="text" name="new_lists[__INDEX__][label]" value=""
                   class="form-control ot-input mb-2 fw-bold" style="max-width:320px" placeholder="Block name" required>
            <p class="text-secondary mb-1">One per line.</p>
            <textarea data-list-for="new_lists___INDEX__"
                      data-field-name="new_lists[__INDEX__][values][]"
                      class="form-control ot-input mb-2" rows="4"></textarea>
            <div id="new_lists___INDEX__Inputs"></div>
            <button type="button" class="btn btn-outline-danger btn-sm remove-new-block">Remove this block</button>
        </div>
    </template>

    <script>
        (function () {
            // Event delegation (not a per-element listener) so this also
            // covers textareas added later via "+ Add a block" with no
            // extra wiring needed.
            document.addEventListener('input', function (e) {
                if (!e.target.matches('textarea[data-list-for]')) return;
                var textarea = e.target;
                var container = document.getElementById(textarea.getAttribute('data-list-for') + 'Inputs');
                var fieldName = textarea.getAttribute('data-field-name');
                if (!container || !fieldName) return;
                container.innerHTML = '';
                textarea.value.split("\n").map(function (v) { return v.trim(); }).filter(Boolean).forEach(function (v) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = fieldName;
                    input.value = v;
                    container.appendChild(input);
                });
            });

            document.addEventListener('change', function (e) {
                if (e.target.classList.contains('delete-block-checkbox')) {
                    e.target.closest('.settings-block').style.opacity = e.target.checked ? '0.4' : '1';
                }
            });

            var newBlockIndex = 0;
            document.getElementById('addBlockBtn').addEventListener('click', function () {
                var html = document.getElementById('newBlockTemplate').innerHTML.split('__INDEX__').join(newBlockIndex);
                var wrapper = document.createElement('div');
                wrapper.innerHTML = html;
                document.getElementById('newBlocksContainer').appendChild(wrapper.firstElementChild);
                newBlockIndex++;
            });

            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-new-block')) {
                    e.target.closest('.settings-block').remove();
                }
            });

            document.addEventListener('change', function (e) {
                if (e.target.classList.contains('delete-field-checkbox')) {
                    e.target.closest('.field-row').style.opacity = e.target.checked ? '0.4' : '1';
                }
            });

            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-new-field')) {
                    e.target.closest('.field-row').remove();
                }
            });
        })();

        // nice-select only reacts to a real click through jQuery's own
        // trigger - a plain addEventListener('change', ...) never sees it
        // (same issue as the Leave Request role toggle elsewhere in this
        // app), so the Field type -> "Options come from" show/hide needs
        // jQuery's delegated .on(), which also automatically covers field
        // rows added later via "+ Add a field" with no extra wiring.
        (function () {
            var SELECT_TYPE = '{{ \App\Models\Portal\PortalReelCustomField::TYPE_SELECT }}';

            function syncFieldRow($row) {
                var isSelect = $row.find('.field-type-select').val() === SELECT_TYPE;
                $row.find('.field-list-source').toggle(isSelect);
            }

            jQuery(document).on('change', '.field-type-select', function () {
                syncFieldRow(jQuery(this).closest('.field-row'));
            });

            jQuery(function () {
                jQuery('.field-row').each(function () { syncFieldRow(jQuery(this)); });
            });

            var newFieldIndex = 0;
            document.getElementById('addFieldBtn').addEventListener('click', function () {
                var html = document.getElementById('newFieldTemplate').innerHTML.split('__INDEX__').join(newFieldIndex);
                var wrapper = document.createElement('div');
                wrapper.innerHTML = html;
                var row = wrapper.firstElementChild;
                document.getElementById('newFieldsContainer').appendChild(row);
                // Newly inserted <select> elements aren't styled by
                // nice-select until explicitly (re)initialised - the
                // global init on page load only ever saw the original set.
                jQuery(row).find('.niceSelect').niceSelect();
                syncFieldRow(jQuery(row));
                newFieldIndex++;
            });
        })();
    </script>
@endsection
