@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php($showBulk = $data['canApprove'] || $data['canAssign'])
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <h4 class="bradecrumb-title mb-1">Reels</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Reels</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-12 p-0">
            <form action="{{ route('portal-social-reels.index') }}" method="get">
                <div class="card ot-card bn-reels-filters mb-24 d-flex flex-row align-items-center gap-3 flex-wrap" style="padding:1rem 1.25rem">
                    @if ($data['canManage'])
                        <a href="{{ route('portal-social-reels.import') }}" class="btn btn-sm ot-btn-primary ms-auto order-last">
                            <i class="fa-solid fa-file-arrow-up"></i> Bulk Import
                        </a>
                    @endif
                    <select name="status" class="nice-select niceSelect bordered_style">
                        <option value="">All statuses</option>
                        @foreach (['suggested', 'accepted', 'production', 'ready', 'published', 'rejected'] as $s)
                            <option value="{{ $s }}" {{ ($data['filters']['status'] ?? '') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <select name="category" class="nice-select niceSelect bordered_style">
                        <option value="">All pillars</option>
                        @foreach ($data['settings']->reel_categories ?: \App\Models\Portal\PortalSetting::DEFAULT_REEL_CATEGORIES as $cat)
                            <option value="{{ $cat }}" {{ ($data['filters']['category'] ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    <select name="format" class="nice-select niceSelect bordered_style">
                        <option value="">All formats</option>
                        @foreach ($data['settings']->reel_formats ?: \App\Models\Portal\PortalSetting::DEFAULT_REEL_FORMATS as $f)
                            <option value="{{ $f }}" {{ ($data['filters']['format'] ?? '') === $f ? 'selected' : '' }}>{{ $f }}</option>
                        @endforeach
                    </select>
                    @if ($data['series']->count())
                        <select name="series_name" class="nice-select niceSelect bordered_style">
                            <option value="">All series</option>
                            @foreach ($data['series'] as $s)
                                <option value="{{ $s->name }}" {{ ($data['filters']['series_name'] ?? '') === $s->name ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    {{-- Plain selects, not nice-select - a past batch of new
                         nice-select elements on this same page (the "Set
                         for selected" bar) silently failed to render a
                         replacement for most of them, verified live. Not
                         worth risking the same thing here. --}}
                    <select name="task_status" class="form-control ot-input" style="width:150px">
                        <option value="">Any task status</option>
                        @foreach ([
                            \App\Models\Portal\PortalTask::OPEN         => 'Open',
                            \App\Models\Portal\PortalTask::ASSIGNED     => 'Assigned',
                            \App\Models\Portal\PortalTask::IN_PROGRESS  => 'In progress',
                            \App\Models\Portal\PortalTask::SUBMITTED    => 'Submitted',
                            \App\Models\Portal\PortalTask::UNDER_REVIEW => 'Under review',
                            \App\Models\Portal\PortalTask::REVISION     => 'Revision',
                            \App\Models\Portal\PortalTask::COMPLETED    => 'Completed',
                        ] as $value => $label)
                            <option value="{{ $value }}" {{ ($data['filters']['task_status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($data['contentTeam']->count())
                        <select name="assigned_to" class="form-control ot-input" style="width:160px">
                            <option value="">Anyone assigned</option>
                            @foreach ($data['contentTeam'] as $staff)
                                <option value="{{ $staff->id }}" {{ (string) ($data['filters']['assigned_to'] ?? '') === (string) $staff->id ? 'selected' : '' }}>{{ $staff->first_name }} {{ $staff->last_name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <button class="btn ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                </div>
            </form>
        </div>

        @if ($showBulk)
            <div class="col-12 p-0">
                <div class="card ot-card bn-reels-filters mb-24 d-flex flex-row flex-wrap align-items-center gap-3" style="padding:1rem 1.25rem" id="bulkActionsBar" hidden>
                    <strong><span id="bulkSelectedCount">0</span> selected</strong>
                    <div id="bnCharactersDisplay" class="text-secondary" style="font-size:.85rem" hidden></div>
                    @if ($data['canApprove'])
                        <div class="d-flex align-items-center gap-2 flex-wrap bn-bar-group">
                            <form id="bulkApproveForm" action="{{ route('portal-social-reels.bulk-review') }}" method="post" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="accepted">
                                <button type="submit" class="btn btn-sm ot-btn-primary">Approve selected</button>
                            </form>
                            <form id="bulkDiscardForm" action="{{ route('portal-social-reels.bulk-review') }}" method="post" class="d-inline" onsubmit="return confirm('Discard all selected suggestions?');">
                                @csrf
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Discard selected</button>
                            </form>
                        </div>
                    @endif
                    @if ($data['canAssign'])
                        <form id="bulkAssignForm" action="{{ route('portal-social-reels.bulk-assign') }}" method="post" class="d-flex align-items-center gap-2 flex-wrap bn-bar-group">
                            @csrf
                            <select name="assigned_to" class="form-control ot-input" required style="width:220px">
                                <option value="">Assign to…</option>
                                @foreach ($data['contentTeam'] as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->last_name }}</option>
                                @endforeach
                            </select>
                            <input type="date" name="deadline" class="form-control ot-input" style="width:150px">
                            <button type="submit" class="btn btn-sm ot-btn-primary">Assign selected</button>
                        </form>
                        <form id="bulkMarkPaidForm" action="{{ route('portal-social-reels.bulk-mark-paid') }}" method="post" class="d-flex align-items-center gap-2 flex-wrap bn-bar-group">
                            @csrf
                            <span class="text-secondary" style="font-size:.85rem">Mark as paid task —</span>
                            <input type="number" step="0.01" min="0" name="amount" class="form-control ot-input" placeholder="Amount" required style="width:110px">
                            <input type="date" name="deadline" class="form-control ot-input" style="width:150px">
                            <select name="visible_to_staff_ids[]" class="form-control select2_multy" multiple style="min-width:220px" data-placeholder="Visible to everyone">
                                @foreach ($data['contentTeam'] as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->last_name }}</option>
                                @endforeach
                            </select>
                            <span class="form-check d-flex align-items-center gap-1" style="font-size:.85rem" title="Leave unticked if the prompt/shot list isn't written yet - the task stays visible but can't be claimed until you come back and tick this.">
                                <input type="checkbox" class="form-check-input bn-prompt-ready" id="bulkPromptReady" name="prompt_ready" value="1">
                                <label class="form-check-label mb-0" for="bulkPromptReady">Prompt ready</label>
                            </span>
                            <span class="form-check d-flex align-items-center gap-1" style="font-size:.85rem" title="Tick this instead if this one was never going to have a written prompt at all - it'll still be claimable, just labelled 'No prompt' instead of 'Ready'.">
                                <input type="checkbox" class="form-check-input bn-no-prompt" id="bulkNoPrompt" name="no_prompt" value="1">
                                <label class="form-check-label mb-0" for="bulkNoPrompt">No prompt</label>
                            </span>
                            <button type="submit" class="btn btn-sm ot-btn-primary">Mark as paid</button>
                        </form>
                        <form id="bnPromptStatusForm" method="post" class="d-flex align-items-center gap-2 flex-wrap bn-bar-group" hidden>
                            @csrf
                            <span class="text-secondary" style="font-size:.85rem">Prompt status for the selected reel —</span>
                            <label class="d-flex align-items-center gap-1 mb-0" style="font-size:.85rem">
                                <input type="checkbox" class="form-check-input bn-prompt-ready" name="prompt_ready" value="1">
                                Prompt ready
                            </label>
                            <label class="d-flex align-items-center gap-1 mb-0" style="font-size:.85rem">
                                <input type="checkbox" class="form-check-input bn-no-prompt" name="no_prompt" value="1">
                                No prompt
                            </label>
                            <button type="submit" class="btn btn-sm ot-btn-primary">Update</button>
                        </form>
                    @endif
                    @if ($data['canManage'])
                        <form id="bulkUpdateFieldsForm" action="{{ route('portal-social-reels.bulk-update-fields') }}" method="post" class="d-flex align-items-center gap-2 flex-wrap bn-bar-group">
                            @csrf
                            <span class="text-secondary" style="font-size:.85rem">Set for selected —</span>
                            {{-- Plain selects, not nice-select - nice-select's init silently
                                 fails to render a replacement for some of these (verified live;
                                 it leaves the original <select> as display:none with nothing in
                                 its place), so a native select is what's actually reliable here. --}}
                            <select name="series_name" id="bulkFieldsSeries" class="form-control ot-input" title="Series" style="width:120px">
                                <option value="">Series…</option>
                                @foreach ($data['series'] as $s)
                                    <option value="{{ $s->name }}" data-defaults="{{ json_encode($s->defaults ?: []) }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                            <select name="format" id="bulkFieldsFormat" class="form-control ot-input" title="Format" style="width:120px">
                                <option value="">Format…</option>
                                @foreach ($data['settings']->reel_formats ?: \App\Models\Portal\PortalSetting::DEFAULT_REEL_FORMATS as $f)
                                    <option value="{{ $f }}">{{ $f }}</option>
                                @endforeach
                            </select>
                            <select name="category" id="bulkFieldsCategory" class="form-control ot-input" title="Pillar" style="width:125px">
                                <option value="">Pillar…</option>
                                @foreach ($data['settings']->reel_categories ?: \App\Models\Portal\PortalSetting::DEFAULT_REEL_CATEGORIES as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                            <select name="is_document_track" class="form-control ot-input" title="Document track" style="width:160px">
                                <option value="">Doc. track…</option>
                                <option value="1">Mark as doc. track</option>
                                <option value="0">Unmark doc. track</option>
                            </select>
                            <button type="submit" class="btn btn-sm ot-btn-primary">Apply</button>
                        </form>
                    @endif
                    <div id="bnSingleReelPanel" class="mt-2" hidden></div>
                </div>
            </div>
        @endif

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="ot-basic-table ot-table-bg bn-reels-table">
                        <colgroup>
                            @if ($showBulk)<col style="width:3%">@endif
                            <col style="width:7%">
                            <col style="width:5%">
                            <col style="width:20%">
                            <col style="width:8%">
                            <col style="width:10%">
                            <col style="width:6%">
                            <col style="width:9%">
                            <col style="width:7%">
                            <col style="width:7%">
                            <col style="width:7%">
                            <col style="width:5%">
                            <col style="width:5%">
                        </colgroup>
                        <thead class="thead">
                            <tr>
                                @if ($showBulk)
                                    <th><input type="checkbox" id="bnSelectAll"></th>
                                @endif
                                <th>Series</th>
                                <th>Code</th>
                                <th>Topic</th>
                                <th>Suggested by</th>
                                <th>Approve</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Prompt</th>
                                <th>Canva link</th>
                                <th>Drive link</th>
                                <th>Upload</th>
                                <th class="action">{{ ___('common.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="tbody">
                            @forelse ($data['reels'] as $reel)
                                @php($staff = optional($reel->suggestedBy)->staff)
                                @php($isSuggested = $reel->status === \App\Models\Portal\PortalReel::SUGGESTED)
                                @php($charNames = $data['charactersFieldKey'] ? array_values(array_filter((array) ($reel->custom_fields[$data['charactersFieldKey']] ?? []))) : [])
                                <tr class="{{ $reel->rowStateClass() }}">
                                    @if ($showBulk)
                                        <td><input type="checkbox" class="bn-reel-check" value="{{ $reel->id }}"
                                            data-edit-url="{{ route('portal-social-reels.edit-panel', $reel->id) }}"
                                            data-prompt-url="{{ route('portal-social-reels.prompt-ready', $reel->id) }}"
                                            data-open-paid="{{ $reel->is_open_paid ? '1' : '0' }}"
                                            data-characters="{{ implode(', ', $charNames) }}"></td>
                                    @endif
                                    <td>
                                        @if ($reel->series_name)
                                            @php($seriesColor = optional($reel->seriesModel)->color)
                                            <span class="bn-series-tag" @if ($seriesColor) style="background:{{ $seriesColor }}22;color:{{ $seriesColor }}" @endif>{{ $reel->series_name }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $reel->code ?: '—' }}</td>
                                    <td>
                                        <span class="bn-reel-title">{{ $reel->title }}</span>
                                        @if ($reel->is_document_track)
                                            <span class="badge-basic-info-text d-block mt-1" style="width:fit-content">Document track</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ optional($reel->suggestedBy)->name ?: '—' }}
                                        @if (optional($staff?->designation)->name)
                                            <div class="bn-reel-sub">{{ $staff->designation->name }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="{{ $isSuggested ? 'badge-basic-secondary-text' : $reel->statusBadgeClass() }}">{{ $isSuggested ? 'Suggested' : $reel->statusLabel() }}</span>
                                    </td>
                                    <td>
                                        {{ $reel->deadline?->format('d M Y') ?: '—' }}
                                    </td>
                                    <td>
                                        @php($reelTask = $reel->task)
                                        @if ($reelTask)
                                            <details class="bn-approve-details">
                                                <summary><span class="badge-basic-info-text">{{ str_replace('_', ' ', ucfirst($reelTask->status)) }}</span></summary>
                                                <div class="bn-approve-panel" style="min-width:150px">
                                                    <div style="font-size:12px" class="mb-1"><strong>Claimed by:</strong> {{ optional($reelTask->assignee)->first_name ? trim($reelTask->assignee->first_name . ' ' . $reelTask->assignee->last_name) : '—' }}</div>
                                                    <div style="font-size:12px"><strong>Status:</strong> {{ str_replace('_', ' ', ucfirst($reelTask->status)) }}</div>
                                                </div>
                                            </details>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($reel->promptUpload)
                                            <a href="{{ globalAsset($reel->promptUpload->path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Download</a>
                                        @elseif ($reel->prompt_link)
                                            <a href="{{ $reel->prompt_link }}" target="_blank" class="btn btn-sm btn-outline-secondary">Download</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($reel->canva_link)
                                            <a href="{{ $reel->canva_link }}" target="_blank" class="bn-link-cell {{ $reel->canva_approved_at ? 'text-success' : 'text-warning' }}" title="{{ $reel->canva_approved_at ? 'Approved' : 'Pending approval — tick Canva in Approve once reviewed' }}">{{ $reel->canva_link }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($reel->drive_link)
                                            <a href="{{ $reel->drive_link }}" target="_blank" class="bn-link-cell {{ $reel->video_approved_at ? 'text-success' : 'text-warning' }}" title="{{ $reel->video_approved_at ? 'Approved' : 'Pending approval — tick Video in Approve once reviewed' }}">{{ $reel->drive_link }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($data['canApprove'])
                                            <form action="{{ route('portal-social-reels.plan-date', $reel->id) }}" method="post" class="bn-inline-deadline-form">
                                                @csrf
                                                <input type="date" name="date" class="form-control ot-input form-control-sm" value="{{ $reel->planned_date?->format('Y-m-d') }}" onchange="this.form.submit()" title="Upload Plan date — independent of Deadline, only set when picked here or in Month Plan">
                                            </form>
                                        @else
                                            {{ $reel->planned_date?->format('d M Y') ?: '—' }}
                                        @endif
                                    </td>
                                    <td class="action bn-reel-actions">
                                        @if ($data['canManage'])
                                            <form action="{{ route('portal-social-reels.destroy', $reel->id) }}" method="post" class="d-inline" onsubmit="return confirm('Remove this reel/topic?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">{{ ___('common.delete') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="100%" class="text-center gray-color">{{ ___('common.no_data_available') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('css')
    <style>
        .bn-reel-title { font-weight: 600; display: block; word-break: break-word; }
        .bn-series-tag { font-family: "Lexend", sans-serif; font-weight: 600; font-size: 11px; border-radius: 14px; padding: 3px 10px; background: rgba(var(--bn-turquoise-rgb), .14); color: var(--bn-turquoise-text, #007585); white-space: normal; display: inline-block; line-height: 1.3; }
        .dark-theme .bn-series-tag { background: rgba(0, 151, 178, .22); color: var(--bn-turquoise-light); }
        .bn-reel-sub { color: var(--bn-muted, #8a8fa3); font-size: .78rem; margin-top: .15rem; }
        .bn-inline-deadline-form input { min-width: 120px; }
        .bn-link-cell { display: block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .bn-approve-details summary { cursor: pointer; list-style: none; font-weight: 700; font-size: 13px; letter-spacing: .1em; }
        .bn-approve-details summary::-webkit-details-marker { display: none; }
        .bn-approve-details summary span { margin-right: 4px; }
        .bn-approve-panel { margin-top: 6px; padding: 8px; border: 1px solid rgba(0,0,0,.1); border-radius: 6px; min-width: 110px; }
        .bn-approve-panel label { font-size: 13px; }
        .bn-reel-actions { display: flex; flex-wrap: wrap; align-items: flex-start; gap: .4rem; }
        .bn-reels-table { table-layout: fixed; width: 100%; }
        .bn-reels-table td { vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
        /* Row tint by where a reel actually stands - same cascade as the
           Status badge, just also readable at a glance down the whole list. */
        .bn-row-rejected    { background: rgba(220, 53, 69, .08); }
        .bn-row-uploaded     { background: rgba(13, 110, 253, .10); }
        .bn-row-complete     { background: rgba(25, 135, 84, .08); }
        .bn-row-in-progress  { background: rgba(255, 193, 7, .14); }
        .bn-row-assigned     { background: rgba(111, 66, 193, .10); }
        .bn-row-approved     { background: rgba(var(--bn-turquoise-rgb), .08); }
        .dark-theme .bn-row-rejected    { background: rgba(220, 53, 69, .18); }
        .dark-theme .bn-row-uploaded     { background: rgba(13, 110, 253, .20); }
        .dark-theme .bn-row-complete     { background: rgba(25, 135, 84, .18); }
        .dark-theme .bn-row-in-progress  { background: rgba(255, 193, 7, .22); }
        .dark-theme .bn-row-assigned     { background: rgba(111, 66, 193, .22); }
        .dark-theme .bn-row-approved     { background: rgba(0, 151, 178, .20); }
        /* Bootstrap's d-flex utility sets display:flex with !important,
           which would otherwise beat the plain [hidden] rule and leave
           this bar visible even while "hidden" is set. Same reasoning
           covers the single-selection-only pieces below (.btn sets its
           own display, non-important but still author-level, which an
           unstyled [hidden] still loses to). */
        #bulkActionsBar[hidden],
        #bnPromptStatusForm[hidden],
        #bulkMarkPaidForm[hidden] { display: none !important; }
        /* Each action in the top bar is its own small "chip" - without
           this, every button/select/checkbox just runs together into
           one undifferentiated row once they're compact enough to share
           lines, which reads as cluttered rather than organised. */
        .bn-bar-group { background: rgba(0, 0, 0, .035); border-radius: 10px; padding: 6px 12px; }
        .dark-theme .bn-bar-group { background: rgba(255, 255, 255, .06); }
    </style>
    @endpush

    @if ($showBulk)
        @push('script')
        <script>
            (function () {
                var selectAll = document.getElementById('bnSelectAll');
                var bar = document.getElementById('bulkActionsBar');
                var countEl = document.getElementById('bulkSelectedCount');
                var singleReelPanel = document.getElementById('bnSingleReelPanel');
                var promptForm = document.getElementById('bnPromptStatusForm');
                var markPaidForm = document.getElementById('bulkMarkPaidForm');
                var charactersDisplay = document.getElementById('bnCharactersDisplay');
                var panelLoadedForId = null;

                function checks() {
                    return document.querySelectorAll('.bn-reel-check');
                }

                // The full single-reel editor (Topic/Hook/Format/Pillar/
                // Note/Document track/Prompt file, Approve checkboxes,
                // Reel Setup) - fetched fresh and injected in place of
                // the old standalone Edit page, only while exactly one
                // row is checked. Its own forms post to the same routes
                // the old Edit page used and land back on this same
                // index page, so no further JS wiring is needed once
                // it's in the DOM.
                function updateEditPanel(single) {
                    if (!singleReelPanel) return;
                    if (!single) {
                        panelLoadedForId = null;
                        singleReelPanel.hidden = true;
                        singleReelPanel.innerHTML = '';
                        return;
                    }
                    if (panelLoadedForId === single.value) {
                        singleReelPanel.hidden = false;
                        return;
                    }
                    fetch(single.dataset.editUrl)
                        .then(function (r) { return r.text(); })
                        .then(function (html) {
                            singleReelPanel.innerHTML = html;
                            singleReelPanel.hidden = false;
                            panelLoadedForId = single.value;
                        });
                }

                // Toggling the prompt status of a reel that's already
                // paid is also an inherently single-reel action - it
                // only ever shows up once exactly one row is checked,
                // using that row's own data-prompt-url/data-open-paid.
                function updateSingleSelection(checked) {
                    var single = checked.length === 1 ? checked[0] : null;

                    updateEditPanel(single);

                    var isOpenPaid = single && single.dataset.openPaid === '1';
                    if (promptForm) {
                        promptForm.hidden = !isOpenPaid;
                        if (isOpenPaid) { promptForm.action = single.dataset.promptUrl; }
                    }
                    // Re-marking an already-paid reel as paid would silently
                    // overwrite its committed amount - swap that form out
                    // for the prompt-status one instead while it'd apply to
                    // just that one already-paid reel.
                    if (markPaidForm) { markPaidForm.hidden = !!isOpenPaid; }

                    if (charactersDisplay) {
                        var names = single ? single.dataset.characters : '';
                        charactersDisplay.hidden = !names;
                        if (names) { charactersDisplay.textContent = 'Characters: ' + names; }
                    }
                }

                function updateBar() {
                    var checked = document.querySelectorAll('.bn-reel-check:checked');
                    if (countEl) { countEl.textContent = checked.length; }
                    if (bar) { bar.hidden = checked.length === 0; }
                    if (selectAll) {
                        selectAll.checked = checked.length > 0 && checked.length === checks().length;
                    }
                    updateSingleSelection(checked);
                }

                document.addEventListener('change', function (e) {
                    if (e.target === selectAll) {
                        Array.prototype.forEach.call(checks(), function (cb) { cb.checked = selectAll.checked; });
                        updateBar();
                        return;
                    }
                    if (e.target.classList.contains('bn-reel-check')) {
                        updateBar();
                    }
                });

                // "Prompt ready" and "No prompt" mean two different things
                // but both unblock claiming - ticking one in a given form
                // never leaves the other also ticked, so they read as the
                // single choice they actually are.
                document.addEventListener('change', function (e) {
                    if (!e.target.classList.contains('bn-prompt-ready') && !e.target.classList.contains('bn-no-prompt')) {
                        return;
                    }
                    if (!e.target.checked) {
                        return;
                    }
                    var form = e.target.closest('form');
                    if (!form) return;
                    var other = form.querySelector(e.target.classList.contains('bn-prompt-ready') ? '.bn-no-prompt' : '.bn-prompt-ready');
                    if (other) { other.checked = false; }
                });

                // _form.blade.php has this exact same listener for its own
                // page (create/the old standalone edit page) - it never
                // runs here, because the single-reel panel is injected via
                // innerHTML, and browsers never execute scripts added that
                // way. Without this, choosing a Prompt file/Cover/etc. in
                // that panel still attaches the file correctly (the <input>
                // itself needs no JS), but the "No file chosen" box next to
                // Browse never updates to say otherwise - easy to mistake
                // for the upload not having worked at all.
                document.addEventListener('change', function (e) {
                    if (!e.target.classList.contains('bn-file-input')) return;
                    var wrapper = e.target.closest('.ot_fileUploader');
                    var placeholder = wrapper ? wrapper.querySelector('.bn-file-placeholder') : null;
                    if (placeholder) {
                        placeholder.value = e.target.files.length ? e.target.files[0].name : '';
                    }
                });

                // Each bulk form starts with no ids[] of its own - the
                // checked rows are only known at submit time, so they're
                // added as hidden inputs right before the form actually posts.
                ['bulkApproveForm', 'bulkDiscardForm', 'bulkAssignForm', 'bulkMarkPaidForm', 'bulkUpdateFieldsForm'].forEach(function (formId) {
                    var form = document.getElementById(formId);
                    if (!form) return;
                    form.addEventListener('submit', function (e) {
                        var checked = document.querySelectorAll('.bn-reel-check:checked');
                        if (!checked.length) {
                            e.preventDefault();
                            alert('Select at least one reel first.');
                            return;
                        }
                        form.querySelectorAll('input[name="ids[]"]').forEach(function (i) { i.remove(); });
                        Array.prototype.forEach.call(checked, function (cb) {
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'ids[]';
                            input.value = cb.value;
                            form.appendChild(input);
                        });
                    });
                });

                // Picking a Series in "Set for selected" previews that
                // series' own Format/Pillar defaults into the two selects
                // next to it, purely as a convenience - the backend
                // applies the same defaults itself (and only to whichever
                // selected reels are actually blank on that field), so
                // leaving these two as "don't change" still works.
                var bulkFieldsSeries = document.getElementById('bulkFieldsSeries');
                if (bulkFieldsSeries) {
                    bulkFieldsSeries.addEventListener('change', function () {
                        var opt = bulkFieldsSeries.options[bulkFieldsSeries.selectedIndex];
                        var defaults = {};
                        try { defaults = JSON.parse(opt.getAttribute('data-defaults') || '{}'); } catch (e) { /* ignore */ }
                        var formatSelect = document.getElementById('bulkFieldsFormat');
                        var categorySelect = document.getElementById('bulkFieldsCategory');
                        if (formatSelect && !formatSelect.value && defaults.format) {
                            formatSelect.value = defaults.format;
                        }
                        if (categorySelect && !categorySelect.value && defaults.category) {
                            categorySelect.value = defaults.category;
                        }
                    });
                }
            })();
        </script>
        @endpush
    @endif
@endsection
