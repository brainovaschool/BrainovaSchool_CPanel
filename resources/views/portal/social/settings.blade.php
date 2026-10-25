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
    </div>

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
        })();
    </script>
@endsection
