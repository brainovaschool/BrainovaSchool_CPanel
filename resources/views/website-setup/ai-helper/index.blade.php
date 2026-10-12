@extends('backend.master')

@section('title')
    {{ @$data['title'] }}
@endsection

@section('content')
    <div class="page-content">

        {{-- bradecrumb Area S t a r t --}}
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h1>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}

        <div class="card ot-card">
            <div class="card-header">
                <h4>{{ ___('settings.ai_helper') }}</h4>
                <p class="text-secondary mb-0">The lesson-plan generator is also available to logged-in teachers in
                    their dashboard sidebar, and the study helper to logged-in students in theirs. This unlinked
                    test link still works too:
                    <code>{{ url('/homeschool-ai-preview') }}</code>
                </p>
                <a href="{{ route('ai-helper.logs') }}" class="btn ot-btn-primary mt-2">View AI Usage Log</a>
            </div>
            <div class="card-body">
                <form action="{{ route('ai-helper.update') }}" method="post" id="visitForm" enctype="multipart/form-data">
                    @csrf
                    <div class="row mb-3">

                        <div class="col-12">
                            <h5 class="mb-1">AI Accounts (tried in order, top to bottom)</h5>
                            <p class="text-secondary" style="margin-top:-6px">
                                Add one row per API key. If the first account is busy, over its quota, or fails,
                                the system automatically retries and then moves on to the next account — so
                                generation only ever fails if <em>every</em> row below has failed. For the
                                strongest protection, add keys from more than one provider (Gemini, OpenAI,
                                Anthropic), not just more than one key from the same one.
                            </p>
                        </div>

                        <div class="col-12 mb-4">
                            <div id="aiAccountsRows" class="d-flex flex-column gap-2"></div>
                            <button type="button" id="aiAccountsAddRow" class="btn btn-sm ot-btn-primary mt-2">
                                <i class="fa-solid fa-plus"></i> Add Account
                            </button>
                            <input type="hidden" name="ai_helper_accounts" id="aiAccountsJson">

                            <datalist id="aiModelsGemini">
                                <option value="gemini-3.6-flash">
                                <option value="gemini-3.6-flash-lite">
                                <option value="gemini-2.5-flash">
                                <option value="gemini-2.5-flash-lite">
                                <option value="gemini-3.5-flash">
                                <option value="gemini-3.5-flash-lite">
                            </datalist>
                            <datalist id="aiModelsAnthropic">
                                <option value="claude-haiku-5-5">
                                <option value="claude-sonnet-5-5">
                                <option value="claude-opus-5-5">
                            </datalist>
                            <datalist id="aiModelsOpenai"></datalist>
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Page Title</label>
                            <input type="text" name="ai_helper_page_title"
                                class="form-control ot-input @error('ai_helper_page_title') is-invalid @enderror"
                                value="{{ Setting('ai_helper_page_title') }}" placeholder="AI Helper (Preview)">
                            @error('ai_helper_page_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Button Text</label>
                            <input type="text" name="ai_helper_button_text"
                                class="form-control ot-input @error('ai_helper_button_text') is-invalid @enderror"
                                value="{{ Setting('ai_helper_button_text') }}" placeholder="Generate Lesson Plan">
                            @error('ai_helper_button_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Prompt (the full instructions sent to the AI — the visitor's
                                Grade / Subject / Term / Unit / Module / Lesson Title are automatically added after
                                this text)</label>
                            <textarea name="ai_helper_system_prompt" rows="16"
                                class="form-control ot-textarea @error('ai_helper_system_prompt') is-invalid @enderror"
                                placeholder="Paste your full lesson-plan generator prompt here.">{{ Setting('ai_helper_system_prompt') }}</textarea>
                            @error('ai_helper_system_prompt')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <hr>
                            <h5 class="mb-3">Mascots</h5>
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Teacher AI Help Mascot (e.g. Brainbot)</label>
                            @if (Setting('ai_helper_teacher_mascot'))
                                <div class="mb-2"><img src="{{ globalAsset(Setting('ai_helper_teacher_mascot')) }}" style="height:70px;"></div>
                            @endif
                            <input type="file" name="ai_helper_teacher_mascot" class="form-control ot-input" accept="image/*">
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Student AI Help Mascot (e.g. Kea)</label>
                            @if (Setting('ai_helper_student_mascot'))
                                <div class="mb-2"><img src="{{ globalAsset(Setting('ai_helper_student_mascot')) }}" style="height:70px;"></div>
                            @endif
                            <input type="file" name="ai_helper_student_mascot" class="form-control ot-input" accept="image/*">
                        </div>

                        <div class="col-12">
                            <hr>
                            <h5 class="mb-3">Usage Limits</h5>
                            <p class="text-secondary" style="margin-top:-10px">Leave the count at 0 for unlimited.</p>
                        </div>

                        <div class="col-12 col-md-3 mb-3">
                            <label class="form-label">Teacher limit period</label>
                            <select name="ai_helper_teacher_limit_period" class="form-control">
                                <option value="day" {{ Setting('ai_helper_teacher_limit_period') != 'week' ? 'selected' : '' }}>Per day</option>
                                <option value="week" {{ Setting('ai_helper_teacher_limit_period') == 'week' ? 'selected' : '' }}>Per week</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3 mb-3">
                            <label class="form-label">Teacher max requests</label>
                            <input type="number" min="0" name="ai_helper_teacher_limit_count"
                                class="form-control ot-input" value="{{ Setting('ai_helper_teacher_limit_count') ?: 0 }}">
                        </div>
                        <div class="col-12 col-md-3 mb-3">
                            <label class="form-label">Student limit period</label>
                            <select name="ai_help_student_limit_period" class="form-control">
                                <option value="day" {{ Setting('ai_help_student_limit_period') != 'week' ? 'selected' : '' }}>Per day</option>
                                <option value="week" {{ Setting('ai_help_student_limit_period') == 'week' ? 'selected' : '' }}>Per week</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3 mb-3">
                            <label class="form-label">Student max requests</label>
                            <input type="number" min="0" name="ai_help_student_limit_count"
                                class="form-control ot-input" value="{{ Setting('ai_help_student_limit_count') ?: 0 }}">
                        </div>

                        <div class="col-12">
                            <hr>
                            <h5 class="mb-3">Student AI Help</h5>
                            <p class="text-secondary" style="margin-top:-10px">Controls the separate homework/study
                                helper tool students see after logging into their own student portal.</p>
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Page Title</label>
                            <input type="text" name="ai_help_student_page_title"
                                class="form-control ot-input @error('ai_help_student_page_title') is-invalid @enderror"
                                value="{{ Setting('ai_help_student_page_title') }}" placeholder="AI Study Helper">
                            @error('ai_help_student_page_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Button Text</label>
                            <input type="text" name="ai_help_student_button_text"
                                class="form-control ot-input @error('ai_help_student_button_text') is-invalid @enderror"
                                value="{{ Setting('ai_help_student_button_text') }}" placeholder="Ask">
                            @error('ai_help_student_button_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Question Box Label</label>
                            <input type="text" name="ai_help_student_question_label"
                                class="form-control ot-input @error('ai_help_student_question_label') is-invalid @enderror"
                                value="{{ Setting('ai_help_student_question_label') }}" placeholder="What are you stuck on?">
                            @error('ai_help_student_question_label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Prompt (instructions sent to the AI before the student's own
                                question)</label>
                            <textarea name="ai_help_student_system_prompt" rows="6"
                                class="form-control ot-textarea @error('ai_help_student_system_prompt') is-invalid @enderror"
                                placeholder="You are a friendly, patient study helper for a school student. Explain things simply, step by step, and never just give a final homework answer without helping the student understand it.">{{ Setting('ai_help_student_system_prompt') }}</textarea>
                            @error('ai_help_student_system_prompt')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <hr>
                            <h5 class="mb-3">Delivery — Image &amp; Slides only</h5>
                            <p class="text-secondary" style="margin-top:-10px">The plain "Generate Lesson Plan" PDF
                                always downloads directly. This setting only affects the "Generate Visual Lesson
                                Plan" and "Generate as Slides" buttons.</p>
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Delivery Mode</label>
                            <select name="ai_helper_delivery_mode" class="nice-select niceSelect bordered_style wide @error('ai_helper_delivery_mode') is-invalid @enderror">
                                <option value="download" {{ Setting('ai_helper_delivery_mode') != 'drive' ? 'selected' : '' }}>Direct Download</option>
                                <option value="drive" {{ Setting('ai_helper_delivery_mode') == 'drive' ? 'selected' : '' }}>Save to Google Drive</option>
                            </select>
                            @error('ai_helper_delivery_mode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Google Drive Root Folder Name</label>
                            <input type="text" name="ai_helper_drive_root_folder"
                                class="form-control ot-input @error('ai_helper_drive_root_folder') is-invalid @enderror"
                                value="{{ Setting('ai_helper_drive_root_folder') ?: 'Brainova Lessons' }}"
                                placeholder="Brainova Lessons">
                            @error('ai_helper_drive_root_folder')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Google Drive Client ID</label>
                            <input type="text" name="ai_helper_drive_client_id"
                                class="form-control ot-input @error('ai_helper_drive_client_id') is-invalid @enderror"
                                value="{{ Setting('ai_helper_drive_client_id') }}" placeholder="xxxxxxxx.apps.googleusercontent.com">
                            @error('ai_helper_drive_client_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Google Drive Client Secret</label>
                            <input type="text" name="ai_helper_drive_client_secret"
                                class="form-control ot-input @error('ai_helper_drive_client_secret') is-invalid @enderror"
                                value="{{ Setting('ai_helper_drive_client_secret') }}" placeholder="GOCSPX-...">
                            @error('ai_helper_drive_client_secret')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 mb-3">
                            <small class="text-secondary d-block mb-2">In Google Cloud Console → APIs &amp; Services →
                                Credentials, create an "OAuth client ID" (type: Web application) with this exact
                                Authorized redirect URI:
                                <code>{{ route('ai-helper.drive-callback') }}</code>.
                                Save the Client ID/Secret above first, then connect below.</small>

                            @if (Setting('ai_helper_drive_refresh_token'))
                                <span class="badge-basic-success-text">Connected</span>
                            @else
                                <span class="badge-basic-danger-text">Not connected</span>
                            @endif
                        </div>

                    </div>

                    <div class="col-md-12 mt-3">
                        <div class="text-end">
                            @if (hasPermission('ai_helper_update'))
                                <button class="btn btn-lg ot-btn-primary">
                                    <span><i class="fa-solid fa-save"></i></span>{{ ___('common.update') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </form>

                @if (hasPermission('ai_helper_update'))
                    <div class="mt-3">
                        @if (Setting('ai_helper_drive_refresh_token'))
                            <form action="{{ route('ai-helper.drive-disconnect') }}" method="post" class="d-inline">
                                @csrf
                                <button class="btn btn-outline-danger">Disconnect Google Drive</button>
                            </form>
                        @else
                            <a href="{{ route('ai-helper.drive-connect') }}" class="btn ot-btn-primary">Connect Google Drive</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function () {
            var initialAccounts = @json($aiAccounts);

            var rowsContainer = document.getElementById('aiAccountsRows');
            var addButton     = document.getElementById('aiAccountsAddRow');
            var jsonInput     = document.getElementById('aiAccountsJson');
            var form          = jsonInput.closest('form');

            function modelDatalistId(provider) {
                if (provider === 'gemini') return 'aiModelsGemini';
                if (provider === 'anthropic') return 'aiModelsAnthropic';
                return 'aiModelsOpenai';
            }

            function addRow(account) {
                account = account || { provider: 'gemini', api_key: '', model: '', label: '', enabled: true };

                var row = document.createElement('div');
                row.className = 'card p-2';
                row.innerHTML =
                    '<div class="row g-2 align-items-center">' +
                        '<div class="col-6 col-md-2">' +
                            '<select class="form-control form-control-sm ai-row-provider">' +
                                '<option value="gemini">Gemini</option>' +
                                '<option value="openai">OpenAI</option>' +
                                '<option value="anthropic">Anthropic</option>' +
                            '</select>' +
                        '</div>' +
                        '<div class="col-6 col-md-3">' +
                            '<input type="text" class="form-control form-control-sm ai-row-key" placeholder="API key">' +
                        '</div>' +
                        '<div class="col-6 col-md-2">' +
                            '<input type="text" class="form-control form-control-sm ai-row-model" placeholder="Model name" list="">' +
                        '</div>' +
                        '<div class="col-6 col-md-2">' +
                            '<input type="text" class="form-control form-control-sm ai-row-label" placeholder="Label (optional)">' +
                        '</div>' +
                        '<div class="col-4 col-md-1 text-center">' +
                            '<input type="checkbox" class="form-check-input ai-row-enabled" title="Enabled">' +
                        '</div>' +
                        '<div class="col-4 col-md-1">' +
                            '<button type="button" class="btn btn-sm btn-outline-secondary ai-row-up" title="Move up">&uarr;</button>' +
                            '<button type="button" class="btn btn-sm btn-outline-secondary ai-row-down" title="Move down">&darr;</button>' +
                        '</div>' +
                        '<div class="col-4 col-md-1 text-end">' +
                            '<button type="button" class="btn btn-sm btn-outline-danger ai-row-remove" title="Remove">&times;</button>' +
                        '</div>' +
                    '</div>';

                var providerSelect = row.querySelector('.ai-row-provider');
                var modelInput     = row.querySelector('.ai-row-model');

                providerSelect.value = account.provider || 'gemini';
                row.querySelector('.ai-row-key').value     = account.api_key || '';
                modelInput.value                            = account.model || '';
                row.querySelector('.ai-row-label').value    = account.label || '';
                row.querySelector('.ai-row-enabled').checked = account.enabled !== false;
                modelInput.setAttribute('list', modelDatalistId(providerSelect.value));

                providerSelect.addEventListener('change', function () {
                    modelInput.setAttribute('list', modelDatalistId(providerSelect.value));
                });

                row.querySelector('.ai-row-remove').addEventListener('click', function () {
                    row.remove();
                });
                row.querySelector('.ai-row-up').addEventListener('click', function () {
                    var prev = row.previousElementSibling;
                    if (prev) rowsContainer.insertBefore(row, prev);
                });
                row.querySelector('.ai-row-down').addEventListener('click', function () {
                    var next = row.nextElementSibling;
                    if (next) rowsContainer.insertBefore(next, row);
                });

                rowsContainer.appendChild(row);
            }

            addButton.addEventListener('click', function () { addRow(); });

            if (initialAccounts && initialAccounts.length) {
                initialAccounts.forEach(addRow);
            } else {
                addRow();
            }

            form.addEventListener('submit', function () {
                var accounts = [];
                rowsContainer.querySelectorAll('.card').forEach(function (row) {
                    var apiKey = row.querySelector('.ai-row-key').value.trim();
                    var model  = row.querySelector('.ai-row-model').value.trim();
                    if (!apiKey || !model) return; // skip genuinely empty rows, don't block saving the rest

                    accounts.push({
                        provider: row.querySelector('.ai-row-provider').value,
                        api_key: apiKey,
                        model: model,
                        label: row.querySelector('.ai-row-label').value.trim(),
                        enabled: row.querySelector('.ai-row-enabled').checked
                    });
                });
                jsonInput.value = JSON.stringify(accounts);
            });
        })();
    </script>
@endsection
