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
                    <button class="btn ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                </div>
            </form>
        </div>

        @if ($showBulk)
            <div class="col-12 p-0">
                <div class="card ot-card bn-reels-filters mb-24 d-flex flex-column gap-2" style="padding:1rem 1.25rem" id="bulkActionsBar" hidden>
                    <strong><span id="bulkSelectedCount">0</span> selected</strong>
                    @if ($data['canApprove'])
                        <div class="d-flex align-items-center gap-2 flex-wrap">
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
                        <form id="bulkAssignForm" action="{{ route('portal-social-reels.bulk-assign') }}" method="post" class="d-flex align-items-center gap-2 flex-wrap">
                            @csrf
                            <select name="assigned_to" class="form-control ot-input" required style="min-width:160px">
                                <option value="">Assign to…</option>
                                @foreach ($data['contentTeam'] as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->last_name }}</option>
                                @endforeach
                            </select>
                            <input type="date" name="deadline" class="form-control ot-input" style="width:150px">
                            <button type="submit" class="btn btn-sm ot-btn-primary">Assign selected</button>
                        </form>
                        <form id="bulkMarkPaidForm" action="{{ route('portal-social-reels.bulk-mark-paid') }}" method="post" class="d-flex align-items-center gap-2 flex-wrap">
                            @csrf
                            <span class="text-secondary" style="font-size:.85rem">Mark as paid task —</span>
                            <input type="number" step="0.01" min="0" name="amount" class="form-control ot-input" placeholder="Amount" required style="width:110px">
                            <input type="date" name="deadline" class="form-control ot-input" style="width:150px">
                            <select name="visible_to_staff_ids[]" class="form-control select2_multy" multiple style="min-width:220px" data-placeholder="Visible to everyone">
                                @foreach ($data['contentTeam'] as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->last_name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm ot-btn-primary">Mark as paid</button>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="ot-basic-table ot-table-bg bn-reels-table">
                        <colgroup>
                            @if ($showBulk)<col style="width:4%">@endif
                            <col style="width:{{ $showBulk ? 7 : 8 }}%">
                            <col style="width:6%">
                            <col style="width:{{ $showBulk ? 13 : 14 }}%">
                            <col style="width:{{ $showBulk ? 14 : 15 }}%">
                            <col style="width:9%">
                            <col style="width:11%">
                            <col style="width:8%">
                            <col style="width:7%">
                            <col style="width:7%">
                            <col style="width:{{ $showBulk ? 14 : 15 }}%">
                        </colgroup>
                        <thead class="thead">
                            <tr>
                                @if ($showBulk)
                                    <th><input type="checkbox" id="bnSelectAll"></th>
                                @endif
                                <th>Series</th>
                                <th>Code</th>
                                <th>Topic</th>
                                <th>Hook</th>
                                <th>Suggested by</th>
                                <th>Status</th>
                                <th>Deadline</th>
                                <th>Progress</th>
                                <th>Upload</th>
                                <th class="action">{{ ___('common.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="tbody">
                            @forelse ($data['reels'] as $reel)
                                @php($staff = optional($reel->suggestedBy)->staff)
                                <tr class="{{ $reel->rowStateClass() }}">
                                    @if ($showBulk)
                                        <td><input type="checkbox" class="bn-reel-check" value="{{ $reel->id }}"></td>
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
                                        <a href="{{ route('portal-social-reels.show', $reel->id) }}" class="bn-reel-title">{{ $reel->title }}</a>
                                    </td>
                                    <td>
                                        @if ($reel->hook)
                                            <span class="bn-reel-hook" title="{{ $reel->hook }}">{{ \Illuminate\Support\Str::limit($reel->hook, 60) }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        {{ optional($reel->suggestedBy)->name ?: '—' }}
                                        @if (optional($staff?->designation)->name)
                                            <div class="bn-reel-sub">{{ $staff->designation->name }}</div>
                                        @endif
                                    </td>
                                    <td><span class="{{ $reel->statusBadgeClass() }}">{{ $reel->statusLabel() }}</span></td>
                                    <td>{{ $reel->deadline?->format('d M Y') ?: '—' }}</td>
                                    <td>
                                        <span class="{{ $reel->promptBadgeClass() }} bn-progress-dot" title="Prompt: {{ $reel->prompt_approved_at ? 'approved' : ($reel->prompt_link ? 'pending approval' : 'not submitted') }}">P</span>
                                        <span class="{{ $reel->videoBadgeClass() }} bn-progress-dot" title="Video: {{ $reel->video_approved_at ? 'approved' : ($reel->drive_link ? 'pending approval' : 'not submitted') }}">V</span>
                                    </td>
                                    <td>
                                        @if ($reel->planned_date)
                                            {{ $reel->planned_date->format('d M Y') }}
                                        @elseif ($reel->deadline)
                                            <span class="text-secondary" title="Provisional - from Production Plan's deadline, not yet confirmed on Upload Plan">{{ $reel->deadline->format('d M Y') }} (planned)</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="action bn-reel-actions">
                                        @php($needsAssignment = $reel->status !== \App\Models\Portal\PortalReel::SUGGESTED && !$reel->is_open_paid && !$reel->assigned_to)
                                        @if ($reel->status === \App\Models\Portal\PortalReel::SUGGESTED || $needsAssignment)
                                            <a href="{{ route('portal-social-reels.show', $reel->id) }}" class="btn btn-sm ot-btn-primary">Open</a>
                                        @else
                                            <a href="{{ route('portal-social-reels.setup', $reel->id) }}" class="btn btn-sm ot-btn-primary">Open</a>
                                        @endif
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
        .bn-reel-hook { color: var(--bn-muted, #8a8fa3); font-size: .85rem; font-style: italic; word-break: break-word; }
        .bn-reel-sub { color: var(--bn-muted, #8a8fa3); font-size: .78rem; margin-top: .15rem; }
        .bn-progress-dot { display: inline-flex; align-items: center; justify-content: center; width: 1.5rem; height: 1.5rem; border-radius: 50%; font-size: .72rem; font-weight: 700; margin-right: .35rem; }
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
           this bar visible even while "hidden" is set. */
        #bulkActionsBar[hidden] { display: none !important; }
    </style>
    @endpush

    @if ($showBulk)
        @push('script')
        <script>
            (function () {
                var selectAll = document.getElementById('bnSelectAll');
                var bar = document.getElementById('bulkActionsBar');
                var countEl = document.getElementById('bulkSelectedCount');

                function checks() {
                    return document.querySelectorAll('.bn-reel-check');
                }

                function updateBar() {
                    var checked = document.querySelectorAll('.bn-reel-check:checked');
                    if (countEl) { countEl.textContent = checked.length; }
                    if (bar) { bar.hidden = checked.length === 0; }
                    if (selectAll) {
                        selectAll.checked = checked.length > 0 && checked.length === checks().length;
                    }
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

                // Each bulk form starts with no ids[] of its own - the
                // checked rows are only known at submit time, so they're
                // added as hidden inputs right before the form actually posts.
                ['bulkApproveForm', 'bulkDiscardForm', 'bulkAssignForm', 'bulkMarkPaidForm'].forEach(function (formId) {
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
            })();
        </script>
        @endpush
    @endif
@endsection
