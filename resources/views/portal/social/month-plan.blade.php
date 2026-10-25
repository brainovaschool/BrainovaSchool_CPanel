@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php($monthLabel = \Carbon\Carbon::parse($data['month'] . '-01')->format('F Y'))
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
                <div class="card-header"><h4 class="mb-0">{{ $monthLabel }} — targets vs actual</h4></div>
                <div class="card-body">
                    <p class="text-secondary">Counts any reel/carousel accepted or further along the pipeline, planned for {{ $monthLabel }}.</p>
                    <div class="table-responsive">
                    <table class="ot-basic-table ot-table-bg">
                        <thead class="thead"><tr><th>Pillar</th><th>Reels</th><th>Carousels</th></tr></thead>
                        <tbody class="tbody">
                            @forelse ($data['plan'] as $row)
                                <tr>
                                    <td>{{ $row['category'] }}</td>
                                    <td>{{ $row['reel_actual'] }} / {{ $row['reel_target'] }}</td>
                                    <td>{{ $row['carousel_actual'] }} / {{ $row['carousel_target'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="100%" class="text-center gray-color">No pillar targets set yet.</td></tr>
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
                            <thead class="thead"><tr><th>Pillar</th><th>Reel target / month</th><th>Carousel target / month</th></tr></thead>
                            <tbody>
                                @forelse ($data['plan'] as $row)
                                    <tr>
                                        <td><input type="text" name="category[]" class="form-control ot-input" value="{{ $row['category'] }}"></td>
                                        <td><input type="number" min="0" name="reel_target[]" class="form-control ot-input" value="{{ $row['reel_target'] }}"></td>
                                        <td><input type="number" min="0" name="carousel_target[]" class="form-control ot-input" value="{{ $row['carousel_target'] }}"></td>
                                    </tr>
                                @empty
                                @endforelse
                                <tr>
                                    <td><input type="text" name="category[]" class="form-control ot-input" placeholder="New pillar"></td>
                                    <td><input type="number" min="0" name="reel_target[]" class="form-control ot-input" value="0"></td>
                                    <td><input type="number" min="0" name="carousel_target[]" class="form-control ot-input" value="0"></td>
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
            <div class="card-header"><h4 class="mb-0">{{ $monthLabel }} — content calendar</h4></div>
            <div class="card-body">
                <p class="text-secondary">Pick the series, then the content — format, who it's assigned to, and the approved video link fill themselves in. Only reels with an approved video show up here.</p>
                @if ($data['canManage'])
                    <form action="{{ route('portal-social-month-plan.calendar') }}" method="post">
                        @csrf
                        <input type="hidden" name="month" value="{{ $data['month'] }}">
                @endif
                        <div class="table-responsive">
                        <table class="ot-basic-table ot-table-bg bn-cal-table">
                            <thead class="thead">
                                <tr>
                                    <th>Date</th><th>Day</th><th>Series</th><th>Content</th>
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
                        <button type="submit" class="btn ot-btn-primary mt-3">Save Calendar</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @push('css')
    <style>
        .bn-cal-table td { vertical-align: middle; }
    </style>
    @endpush

    @push('script')
    <script>
        (function () {
            // Each row's Series select only filters which <option>s show in
            // that SAME row's Content select - and once Content is picked,
            // Format/Created by/Drive link are read straight off the
            // selected <option>'s data-* attributes, never typed in.
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
        })();
    </script>
    @endpush
@endsection
