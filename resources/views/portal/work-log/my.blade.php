@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php
        $entriesByStart = $data['entries']->keyBy(fn ($e) => $e->start_label);
        $extraEntries = $data['entries']->where('is_extra', true);
    @endphp
    <style>
        /* The theme's nice-select plugin was rendering too narrow for a task
           title or a long activity name to fit, truncating the visible text. */
        .nice-select.work-log-select { min-width: 280px; }
        .nice-select.work-log-select .current { white-space: normal; overflow: visible; }
    </style>
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">My Work Log</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">My Work Log</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-12 p-0">
            <form action="{{ route('portal-work-log.index') }}" method="get">
                <div class="card ot-card mb-24 position-relative z_1">
                    <div class="card-header d-flex align-items-center gap-4 flex-wrap">
                        <input type="date" name="date" class="form-control ot-input" value="{{ $data['date'] }}">
                        <button class="btn ot-btn-primary" type="submit">Go</button>
                        @if ($data['isSubmitted'])
                            <span class="badge-basic-success-text">Submitted — locked</span>
                        @elseif ($data['isLocked'])
                            <span class="badge-basic-warning-text">Locked — grace period expired (never submitted)</span>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="card ot-card">
            <div class="card-header"><h4 class="mb-0">{{ \Carbon\Carbon::parse($data['date'])->format('l, d M Y') }}</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="ot-basic-table ot-table-bg">
                    <thead class="thead">
                        <tr><th style="width:140px">Time</th><th colspan="2">Activity &amp; Notes</th><th>Coordinator Feedback</th><th class="action">{{ ___('common.action') }}</th></tr>
                    </thead>
                    <tbody class="tbody">
                        @foreach ($data['slots'] as $slot)
                            @if ($slot['is_lunch'])
                                <tr class="table-light"><td>{{ $slot['start'] }}–{{ $slot['end'] }}</td><td colspan="4" class="text-secondary">Lunch</td></tr>
                            @else
                                @php($entry = $entriesByStart->get($slot['start']))
                                <tr>
                                    <td>{{ $slot['start'] }}–{{ $slot['end'] }}</td>
                                    @if ($entry && $data['isLocked'])
                                        <td colspan="2">{{ $entry->task ? 'Task: ' . $entry->task->title : $entry->activity }} — {{ $entry->notes }}</td>
                                        <td>@if ($entry->feedback)<span class="badge-basic-info-text">{{ $entry->feedback }}</span>@endif</td>
                                        <td class="action"></td>
                                    @elseif ($entry)
                                        <td colspan="2">
                                            <form action="{{ route('portal-work-log.entry.store') }}" method="post" class="d-flex gap-2 flex-wrap align-items-center work-log-slot-form">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $entry->id }}">
                                                <input type="hidden" name="date" value="{{ $data['date'] }}">
                                                <input type="hidden" name="start_time" value="{{ $slot['start'] }}">
                                                <input type="hidden" name="end_time" value="{{ $slot['end'] }}">
                                                @include('portal.work-log._activity-select', ['myTasks' => $data['myTasks'], 'activities' => $data['settings']->activities, 'selectedTaskId' => $entry->task_id, 'selectedActivity' => $entry->activity])
                                                <input type="text" name="notes" value="{{ $entry->notes }}" class="form-control ot-input" placeholder="Notes (optional)" style="max-width:200px">
                                                <button type="submit" class="btn btn-sm ot-btn-primary">Update</button>
                                            </form>
                                        </td>
                                        <td>@if ($entry->feedback)<span class="badge-basic-info-text">{{ $entry->feedback }}</span>@endif</td>
                                        <td class="action">
                                            <form action="{{ route('portal-work-log.entry.delete', $entry->id) }}" method="post" onsubmit="return confirm('Remove this entry?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Remove</button>
                                            </form>
                                        </td>
                                    @elseif ($data['isLocked'])
                                        <td colspan="4" class="text-secondary">— not logged —</td>
                                    @else
                                        <td colspan="2">
                                            <form action="{{ route('portal-work-log.entry.store') }}" method="post" class="d-flex gap-2 flex-wrap align-items-center work-log-slot-form">
                                                @csrf
                                                <input type="hidden" name="date" value="{{ $data['date'] }}">
                                                <input type="hidden" name="start_time" value="{{ $slot['start'] }}">
                                                <input type="hidden" name="end_time" value="{{ $slot['end'] }}">
                                                @include('portal.work-log._activity-select', ['myTasks' => $data['myTasks'], 'activities' => $data['settings']->activities, 'selectedTaskId' => null, 'selectedActivity' => null])
                                                <input type="text" name="notes" class="form-control ot-input" placeholder="Notes (optional)" style="max-width:200px">
                                                <button type="submit" class="btn btn-sm ot-btn-primary">Save</button>
                                            </form>
                                        </td>
                                        <td></td>
                                        <td class="action"></td>
                                    @endif
                                </tr>
                            @endif
                        @endforeach

                        @foreach ($extraEntries as $entry)
                            <tr>
                                <td>{{ $entry->start_label }}–{{ $entry->end_label }} <span class="badge-basic-info-text">Extra</span></td>
                                @if ($data['isLocked'])
                                    <td colspan="2">{{ $entry->task ? 'Task: ' . $entry->task->title : $entry->activity }} — {{ $entry->notes }}</td>
                                @else
                                    <td colspan="2">
                                        <form action="{{ route('portal-work-log.entry.store') }}" method="post" class="d-flex gap-2 flex-wrap align-items-center work-log-slot-form">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $entry->id }}">
                                            <input type="hidden" name="date" value="{{ $data['date'] }}">
                                            <input type="hidden" name="start_time" value="{{ $entry->start_label }}">
                                            <input type="hidden" name="end_time" value="{{ $entry->end_label }}">
                                            <input type="hidden" name="is_extra" value="1">
                                            @include('portal.work-log._activity-select', ['myTasks' => $data['myTasks'], 'activities' => $data['settings']->activities, 'selectedTaskId' => $entry->task_id, 'selectedActivity' => $entry->activity])
                                            <input type="text" name="notes" value="{{ $entry->notes }}" class="form-control ot-input" placeholder="Notes (optional)" style="max-width:200px">
                                            <button type="submit" class="btn btn-sm ot-btn-primary">Update</button>
                                        </form>
                                    </td>
                                @endif
                                <td>@if ($entry->feedback)<span class="badge-basic-info-text">{{ $entry->feedback }}</span>@endif</td>
                                <td class="action">
                                    @unless ($data['isLocked'])
                                        <form action="{{ route('portal-work-log.entry.delete', $entry->id) }}" method="post" onsubmit="return confirm('Remove this entry?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Remove</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>

                @unless ($data['isLocked'])
                    <div class="d-flex justify-content-end mb-3">
                        <button type="button" id="bulkSaveAllBtn" class="btn ot-btn-primary">Save All Changes</button>
                    </div>

                    <div class="border-top pt-3 mt-2">
                        <h5>Add an extra entry</h5>
                        <form action="{{ route('portal-work-log.entry.store') }}" method="post" class="d-flex gap-2 flex-wrap align-items-center work-log-slot-form">
                            @csrf
                            <input type="hidden" name="date" value="{{ $data['date'] }}">
                            <input type="hidden" name="is_extra" value="1">

                            <input type="hidden" name="start_time" id="extraStartTime">
                            <div class="d-flex align-items-center gap-1">
                                <select class="form-select ot-input" style="width:72px" onchange="window.combineWorkLogTime('extraStartHour','extraStartMin','extraStartTime')" id="extraStartHour">
                                    <option value="">HH</option>
                                    @for ($h = 0; $h < 24; $h++)
                                        <option value="{{ sprintf('%02d', $h) }}">{{ sprintf('%02d', $h) }}</option>
                                    @endfor
                                </select>
                                <span>:</span>
                                <select class="form-select ot-input" style="width:72px" onchange="window.combineWorkLogTime('extraStartHour','extraStartMin','extraStartTime')" id="extraStartMin">
                                    <option value="">MM</option>
                                    @foreach ([0, 15, 30, 45] as $m)
                                        <option value="{{ sprintf('%02d', $m) }}">{{ sprintf('%02d', $m) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <span>to</span>

                            <input type="hidden" name="end_time" id="extraEndTime">
                            <div class="d-flex align-items-center gap-1">
                                <select class="form-select ot-input" style="width:72px" onchange="window.combineWorkLogTime('extraEndHour','extraEndMin','extraEndTime')" id="extraEndHour">
                                    <option value="">HH</option>
                                    @for ($h = 0; $h < 24; $h++)
                                        <option value="{{ sprintf('%02d', $h) }}">{{ sprintf('%02d', $h) }}</option>
                                    @endfor
                                </select>
                                <span>:</span>
                                <select class="form-select ot-input" style="width:72px" onchange="window.combineWorkLogTime('extraEndHour','extraEndMin','extraEndTime')" id="extraEndMin">
                                    <option value="">MM</option>
                                    @foreach ([0, 15, 30, 45] as $m)
                                        <option value="{{ sprintf('%02d', $m) }}">{{ sprintf('%02d', $m) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @include('portal.work-log._activity-select', ['myTasks' => $data['myTasks'], 'activities' => $data['settings']->activities, 'selectedTaskId' => null, 'selectedActivity' => null])
                            <input type="text" name="notes" class="form-control ot-input" placeholder="Notes (optional)" style="max-width:200px">
                            <button type="submit" class="btn btn-sm ot-btn-primary">Add</button>
                        </form>
                    </div>

                    <form action="{{ route('portal-work-log.submit') }}" method="post" class="mt-4" onsubmit="return confirm('Submit this day? It will be locked and you won\'t be able to add or remove entries afterwards.');">
                        @csrf
                        <input type="hidden" name="date" value="{{ $data['date'] }}">
                        <button type="submit" class="btn btn-lg ot-btn-success">Submit Day</button>
                    </form>
                @endunless
            </div>
        </div>

        <div class="card ot-card mt-4">
            <div class="card-header"><h4 class="mb-0">Last 14 days</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="ot-basic-table ot-table-bg">
                    <thead class="thead"><tr><th>Date</th><th>Hours logged</th><th>{{ ___('common.status') }}</th></tr></thead>
                    <tbody class="tbody">
                        @foreach ($data['history'] as $row)
                            <tr>
                                <td><a href="{{ route('portal-work-log.index', ['date' => $row['date']]) }}">{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</a></td>
                                <td>{{ $row['hours'] }}</td>
                                <td>
                                    @if ($row['submitted'])
                                        <span class="badge-basic-success-text">Submitted</span>
                                    @elseif ($row['locked'])
                                        <span class="badge-basic-danger-text">Locked — never submitted</span>
                                    @else
                                        <span class="badge-basic-warning-text">Not submitted</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Combines a paired HH/MM select into the hidden time field the
        // backend actually reads -- native <input type="time"> displays
        // AM/PM on some systems, so this guarantees 24-hour entry instead.
        window.combineWorkLogTime = function (hourId, minId, hiddenId) {
            var h = document.getElementById(hourId).value;
            var m = document.getElementById(minId).value;
            document.getElementById(hiddenId).value = (h && m) ? (h + ':' + m) : '';
        };

        // "Save All Changes" -- fires every slot/entry form on the page in
        // the background instead of making someone click Save on each row
        // one at a time. Forms left untouched (no activity/task picked)
        // are skipped rather than submitted empty.
        document.getElementById('bulkSaveAllBtn')?.addEventListener('click', function () {
            var forms = Array.prototype.slice.call(document.querySelectorAll('.work-log-slot-form'));
            var requests = forms.filter(function (form) {
                var taskId = form.querySelector('[name="task_id"]');
                var activity = form.querySelector('[name="activity"]');
                var hasExisting = !!form.querySelector('[name="id"]');
                var hasChoice = (taskId && taskId.value) || (activity && activity.value);
                return hasExisting || hasChoice;
            }).map(function (form) {
                return fetch(form.action, { method: 'POST', body: new FormData(form), redirect: 'manual' }).catch(function () {});
            });

            if (!requests.length) {
                return;
            }
            var btn = document.getElementById('bulkSaveAllBtn');
            btn.disabled = true;
            btn.textContent = 'Saving...';
            Promise.allSettled(requests).then(function () {
                window.location.reload();
            });
        });
    </script>
@endsection
