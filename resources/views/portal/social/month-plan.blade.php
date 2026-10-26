@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php($monthLabel = \Carbon\Carbon::parse($data['month'] . '-01')->format('F Y'))
    @php($formats = $data['settings']->reel_formats ?: \App\Models\Portal\PortalSetting::DEFAULT_REEL_FORMATS)
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">Social Board</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Month Plan</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-12 p-0">
            <form action="{{ route('portal-social-month-plan.index') }}" method="get">
                <div class="card ot-card mb-24 d-flex flex-row align-items-center gap-3 flex-wrap" style="padding:1rem 1.25rem">
                    <label class="form-label mb-0">Month</label>
                    <input type="month" name="month" value="{{ $data['month'] }}" class="form-control ot-input" style="max-width:200px">
                    <button class="btn ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                </div>
            </form>
        </div>

        <div class="table-content table-basic mb-24">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">{{ $monthLabel }} — Actual vs Target</h4></div>
                <div class="card-body">
                    <p class="text-secondary">Counts any reel accepted or further along the pipeline, planned for {{ $monthLabel }}.</p>
                    <div class="table-responsive">
                    <table class="ot-basic-table ot-table-bg">
                        <thead class="thead"><tr><th>Series</th><th>Format</th><th>Actual / Target</th></tr></thead>
                        <tbody class="tbody">
                            @forelse ($data['plan'] as $row)
                                <tr>
                                    <td>{{ $row['series'] ?: '—' }}</td>
                                    <td>{{ $row['format'] ?: '—' }}</td>
                                    <td>{{ $row['actual'] }} / {{ $row['target'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="100%" class="text-center gray-color">No targets set yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>

        @if ($data['canManage'])
            <div class="card ot-card mb-24">
                <div class="card-header"><h4 class="mb-0">Edit targets</h4></div>
                <div class="card-body">
                    <form action="{{ route('portal-social-month-plan.targets') }}" method="post">
                        @csrf
                        <div class="table-responsive">
                        <table class="ot-basic-table ot-table-bg mb-3" id="targetsTable">
                            <thead class="thead"><tr><th>Series</th><th>Format</th><th>Quantity / month</th></tr></thead>
                            <tbody>
                                @forelse ($data['plan'] as $row)
                                    <tr>
                                        <td>
                                            <select name="series_name[]" class="form-control ot-input">
                                                <option value="">—</option>
                                                @foreach ($data['seriesList'] as $s)
                                                    <option value="{{ $s->name }}" {{ $row['series'] === $s->name ? 'selected' : '' }}>{{ $s->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="format[]" class="form-control ot-input">
                                                <option value="">—</option>
                                                @foreach ($formats as $f)
                                                    <option value="{{ $f }}" {{ $row['format'] === $f ? 'selected' : '' }}>{{ $f }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="number" min="0" name="target[]" class="form-control ot-input" value="{{ $row['target'] }}"></td>
                                    </tr>
                                @empty
                                @endforelse
                                <tr>
                                    <td>
                                        <select name="series_name[]" class="form-control ot-input">
                                            <option value="">—</option>
                                            @foreach ($data['seriesList'] as $s)
                                                <option value="{{ $s->name }}">{{ $s->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="format[]" class="form-control ot-input">
                                            <option value="">—</option>
                                            @foreach ($formats as $f)
                                                <option value="{{ $f }}">{{ $f }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" min="0" name="target[]" class="form-control ot-input" value="0"></td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                        <button type="submit" class="btn ot-btn-primary">Save Targets</button>
                    </form>
                </div>
            </div>
        @endif

        <div class="card ot-card">
            <div class="card-header">
                <ul class="nav theme_tabs" id="planTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="production-plan-tab" data-bs-toggle="tab" data-bs-target="#productionPlan" type="button" role="tab">Production Plan</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="upload-plan-tab" data-bs-toggle="tab" data-bs-target="#uploadPlan" type="button" role="tab">Upload Plan</button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">

                    {{-- Production Plan: who's working on what and by when - every
                         approved reel regardless of video status, keyed by its
                         deadline, more than one reel can share a day. --}}
                    <div class="tab-pane fade show active" id="productionPlan" role="tabpanel">
                        <p class="text-secondary">Pick the series, then the topic for each day — Code / Format / Assigned to / Drive link fill themselves in. Every approved topic shows up here, whether or not its video is ready yet. Use "+ Add" for a second (or third) reel due the same day.</p>
                        @if ($data['canManage'])
                            <form action="{{ route('portal-social-month-plan.production') }}" method="post">
                                @csrf
                                <input type="hidden" name="month" value="{{ $data['month'] }}">
                        @endif
                                <div class="table-responsive">
                                <table class="ot-basic-table ot-table-bg bn-cal-table" id="productionTable">
                                    <thead class="thead">
                                        <tr>
                                            <th>Date</th><th>Day</th><th>Series</th><th>Topic</th><th>Code</th>
                                            <th>Format</th><th>Assigned to</th><th>Drive link</th>
                                            @if ($data['canManage'])<th></th>@endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($data['production']['days'] as $day)
                                            @php($dateKey = $day['date']->format('Y-m-d'))
                                            @php($existing = $day['slots'])
                                            @if ($data['canManage'])
                                                @php($rowCount = max($existing->count(), 1))
                                                @for ($i = 0; $i < $rowCount; $i++)
                                                    @php($slot = $existing->get($i))
                                                    @php($reel = $slot['reel'] ?? null)
                                                    <tr class="bn-prod-row" data-date="{{ $dateKey }}">
                                                        @if ($i === 0)
                                                            <td class="bn-prod-date-cell" rowspan="{{ $rowCount }}">{{ $day['date']->format('d M Y') }}</td>
                                                            <td class="bn-prod-day-cell" rowspan="{{ $rowCount }}">{{ $day['date']->format('l') }}</td>
                                                        @endif
                                                        <td>
                                                            <select name="assignments[{{ $dateKey }}][series][]" class="form-control ot-input bn-cal-series">
                                                                <option value="">—</option>
                                                                @foreach ($data['series'] as $s)
                                                                    <option value="{{ $s }}" {{ ($slot['series_name'] ?? null) === $s ? 'selected' : '' }}>{{ $s }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select name="assignments[{{ $dateKey }}][topic][]" class="form-control ot-input bn-cal-content">
                                                                <option value="">—</option>
                                                                @foreach ($data['production']['reels'] as $r)
                                                                    <option value="{{ $r->id }}"
                                                                            data-series="{{ $r->series_name }}"
                                                                            data-code="{{ $r->code ?: '—' }}"
                                                                            data-format="{{ $r->format }}"
                                                                            data-assignee="{{ optional($r->assignee)->first_name ? trim($r->assignee->first_name . ' ' . $r->assignee->last_name) : '—' }}"
                                                                            data-drive-link="{{ $r->drive_link }}"
                                                                            {{ optional($reel)->id === $r->id ? 'selected' : '' }}>{{ $r->title }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="bn-cal-code">{{ optional($reel)->code ?: '—' }}</td>
                                                        <td class="bn-cal-format">{{ optional($reel)->format ?: '—' }}</td>
                                                        <td class="bn-cal-assignee">{{ optional($reel)->assignee?->first_name ? trim($reel->assignee->first_name . ' ' . $reel->assignee->last_name) : '—' }}</td>
                                                        <td class="bn-cal-drive-link">
                                                            @if (optional($reel)->drive_link)
                                                                <a href="{{ $reel->drive_link }}" target="_blank">{{ $reel->drive_link }}</a>
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($i === $rowCount - 1)
                                                                <button type="button" class="btn btn-sm btn-outline-secondary bn-prod-add" data-date="{{ $dateKey }}">+ Add</button>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endfor
                                            @else
                                                @forelse ($existing as $i => $slot)
                                                    @php($reel = $slot['reel'] ?? null)
                                                    <tr>
                                                        @if ($i === 0)
                                                            <td rowspan="{{ $existing->count() }}">{{ $day['date']->format('d M Y') }}</td>
                                                            <td rowspan="{{ $existing->count() }}">{{ $day['date']->format('l') }}</td>
                                                        @endif
                                                        <td>{{ $slot['series_name'] ?: '—' }}</td>
                                                        <td>{{ optional($reel)->title ?: '—' }}</td>
                                                        <td>{{ optional($reel)->code ?: '—' }}</td>
                                                        <td>{{ optional($reel)->format ?: '—' }}</td>
                                                        <td>{{ optional($reel)->assignee?->first_name ? trim($reel->assignee->first_name . ' ' . $reel->assignee->last_name) : '—' }}</td>
                                                        <td>
                                                            @if (optional($reel)->drive_link)
                                                                <a href="{{ $reel->drive_link }}" target="_blank">{{ $reel->drive_link }}</a>
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td>{{ $day['date']->format('d M Y') }}</td>
                                                        <td>{{ $day['date']->format('l') }}</td>
                                                        <td colspan="6" class="text-center gray-color">—</td>
                                                    </tr>
                                                @endforelse
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                                </div>
                        @if ($data['canManage'])
                                <button type="submit" class="btn ot-btn-primary mt-3">Save Production Plan</button>
                            </form>
                        @endif
                    </div>

                    {{-- Upload Plan: when it actually goes live - only reels with
                         an approved video, one per day (same table as before, just
                         Content renamed to Topic and a Code column added). --}}
                    <div class="tab-pane fade" id="uploadPlan" role="tabpanel">
                        <p class="text-secondary">Pick the series, then the topic — Code / Format / Created by / Drive link fill themselves in. Only reels with an approved video show up here.</p>
                        @if ($data['canManage'])
                            <form action="{{ route('portal-social-month-plan.calendar') }}" method="post">
                                @csrf
                                <input type="hidden" name="month" value="{{ $data['month'] }}">
                        @endif
                                <div class="table-responsive">
                                <table class="ot-basic-table ot-table-bg bn-cal-table">
                                    <thead class="thead">
                                        <tr>
                                            <th>Date</th><th>Day</th><th>Series</th><th>Topic</th><th>Code</th>
                                            <th>Format</th><th>Created by</th><th>Drive link</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($data['calendar']['days'] as $day)
                                            @php($reel = $day['reel'])
                                            <tr>
                                                <td>{{ $day['date']->format('d M Y') }}</td>
                                                <td>{{ $day['date']->format('l') }}</td>
                                                <td>
                                                    @if ($data['canManage'])
                                                        <select class="form-control ot-input bn-cal-series">
                                                            <option value="">—</option>
                                                            @foreach ($data['series'] as $s)
                                                                <option value="{{ $s }}" {{ optional($reel)->series_name === $s ? 'selected' : '' }}>{{ $s }}</option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        {{ optional($reel)->series_name ?: '—' }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($data['canManage'])
                                                        <select name="assignments[{{ $day['date']->format('Y-m-d') }}]" class="form-control ot-input bn-cal-content">
                                                            <option value="">—</option>
                                                            @foreach ($data['calendar']['reels'] as $r)
                                                                <option value="{{ $r->id }}"
                                                                        data-series="{{ $r->series_name }}"
                                                                        data-code="{{ $r->code ?: '—' }}"
                                                                        data-format="{{ $r->format }}"
                                                                        data-assignee="{{ optional($r->assignee)->first_name ? trim($r->assignee->first_name . ' ' . $r->assignee->last_name) : '—' }}"
                                                                        data-drive-link="{{ $r->drive_link }}"
                                                                        {{ optional($reel)->id === $r->id ? 'selected' : '' }}>{{ $r->title }}</option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        {{ optional($reel)->title ?: '—' }}
                                                    @endif
                                                </td>
                                                <td class="bn-cal-code">{{ optional($reel)->code ?: '—' }}</td>
                                                <td class="bn-cal-format">{{ optional($reel)->format ?: '—' }}</td>
                                                <td class="bn-cal-assignee">{{ optional($reel)->assignee?->first_name ? trim($reel->assignee->first_name . ' ' . $reel->assignee->last_name) : '—' }}</td>
                                                <td class="bn-cal-drive-link">
                                                    @if (optional($reel)->drive_link)
                                                        <a href="{{ $reel->drive_link }}" target="_blank">{{ $reel->drive_link }}</a>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                </div>
                        @if ($data['canManage'])
                                <button type="submit" class="btn ot-btn-primary mt-3">Save Upload Plan</button>
                            </form>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>

    @push('css')
    <style>
        .bn-cal-table td { vertical-align: middle; }
        .bn-cal-table .bn-cal-drive-link { word-wrap: break-word; max-width: 160px; }
    </style>
    @endpush

    @push('script')
    <script>
        (function () {
            // Each row's Series select only filters which <option>s show in
            // that SAME row's Topic select - and once a Topic is picked,
            // Code/Format/Assigned-or-Created-by/Drive link are read straight
            // off the selected <option>'s data-* attributes, never typed in.
            // Shared by both the Production Plan and Upload Plan tables.
            function syncRow(tr) {
                var seriesSelect = tr.querySelector('.bn-cal-series');
                var contentSelect = tr.querySelector('.bn-cal-content');
                if (!contentSelect) return;

                if (seriesSelect) {
                    var series = seriesSelect.value;
                    Array.prototype.forEach.call(contentSelect.options, function (o) {
                        if (!o.value) return;
                        var matches = !series || o.getAttribute('data-series') === series;
                        o.hidden = !matches;
                        if (!matches && o.selected) { o.selected = false; contentSelect.value = ''; }
                    });
                }

                var opt = contentSelect.options[contentSelect.selectedIndex];
                var hasSelection = opt && opt.value;
                var codeCell = tr.querySelector('.bn-cal-code');
                if (codeCell) { codeCell.textContent = hasSelection ? opt.getAttribute('data-code') : '—'; }
                tr.querySelector('.bn-cal-format').textContent = hasSelection ? opt.getAttribute('data-format') : '—';
                tr.querySelector('.bn-cal-assignee').textContent = hasSelection ? opt.getAttribute('data-assignee') : '—';
                var driveCell = tr.querySelector('.bn-cal-drive-link');
                var link = hasSelection ? opt.getAttribute('data-drive-link') : '';
                driveCell.innerHTML = link ? '<a href="' + link + '" target="_blank">' + link + '</a>' : '—';
            }

            document.querySelectorAll('.bn-cal-table tbody tr').forEach(function (tr) {
                // Rows with no series dropdown have nothing to sync -
                // they're just the read-only view for non-managers.
                if (tr.querySelector('.bn-cal-series') || tr.querySelector('.bn-cal-content')) { syncRow(tr); }
            });

            document.addEventListener('change', function (e) {
                if (!e.target.classList.contains('bn-cal-series') && !e.target.classList.contains('bn-cal-content')) return;
                syncRow(e.target.closest('tr'));
            });

            // Production Plan only: "+ Add" clones a blank slot row onto the
            // same day, and bumps the Date/Day cells' rowspan to cover it -
            // those cells only exist on that day's FIRST row.
            function attachAddHandler(btn) {
                if (!btn) return;
                btn.addEventListener('click', function () {
                    var date = btn.getAttribute('data-date');
                    var row = btn.closest('tr');
                    var clone = row.cloneNode(true);

                    // The day's FIRST row is the only one that ever carries
                    // the rowspan'd Date/Day cells - cloning THAT row (a day
                    // with only one reel so far) would otherwise duplicate
                    // them into the new row and shift every column after.
                    var cloneDateCell = clone.querySelector('.bn-prod-date-cell');
                    if (cloneDateCell) { cloneDateCell.remove(); }
                    var cloneDayCell = clone.querySelector('.bn-prod-day-cell');
                    if (cloneDayCell) { cloneDayCell.remove(); }

                    clone.querySelectorAll('select').forEach(function (s) {
                        s.selectedIndex = 0;
                        s.querySelectorAll('option').forEach(function (o) { o.hidden = false; });
                    });
                    var codeCell = clone.querySelector('.bn-cal-code');
                    if (codeCell) { codeCell.textContent = '—'; }
                    clone.querySelector('.bn-cal-format').textContent = '—';
                    clone.querySelector('.bn-cal-assignee').textContent = '—';
                    clone.querySelector('.bn-cal-drive-link').innerHTML = '—';

                    var oldBtnCell = row.querySelector('.bn-prod-add');
                    if (oldBtnCell) { oldBtnCell.parentNode.innerHTML = ''; }

                    row.parentNode.insertBefore(clone, row.nextSibling);

                    var firstRow = document.querySelector('.bn-prod-row[data-date="' + date + '"]');
                    var dateCell = firstRow ? firstRow.querySelector('.bn-prod-date-cell') : null;
                    var dayCell = firstRow ? firstRow.querySelector('.bn-prod-day-cell') : null;
                    if (dateCell) { dateCell.rowSpan = dateCell.rowSpan + 1; }
                    if (dayCell) { dayCell.rowSpan = dayCell.rowSpan + 1; }

                    attachAddHandler(clone.querySelector('.bn-prod-add'));
                    syncRow(clone);
                });
            }
            document.querySelectorAll('.bn-prod-add').forEach(attachAddHandler);
        })();
    </script>
    @endpush
@endsection
