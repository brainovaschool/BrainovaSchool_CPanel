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
        .nice-select.work-log-select { min-width: 220px; }
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
                        <tr><th style="width:150px">Time</th><th style="min-width:200px">Activity</th><th style="min-width:260px">Notes, Link &amp; File</th><th>Coordinator Feedback</th><th class="action">{{ ___('common.action') }}</th></tr>
                    </thead>
                    <tbody class="tbody">
                        @foreach ($data['slots'] as $slot)
                            @if ($slot['is_lunch'])
                                <tr class="table-light"><td>{{ $slot['start'] }}–{{ $slot['end'] }}</td><td colspan="4" class="text-secondary">Lunch</td></tr>
                            @else
                                {{-- Tries the slot's current (possibly remembered-override) time
                                     first, then falls back to its original un-customized time - so
                                     an entry logged on an older day, before the time was last
                                     changed, is still found and shown instead of looking unlogged. --}}
                                @php($entry = $entriesByStart->get($slot['start']) ?? $entriesByStart->get($slot['default_start']))
                                @php($prefix = 'slot' . str_replace(':', '', $slot['default_start']))
                                <tr>
                                    @if ($entry && $data['isLocked'])
                                        <td>{{ $slot['start'] }}–{{ $slot['end'] }}</td>
                                        <td>{{ $entry->task ? 'Task: ' . $entry->task->title : $entry->activity }}</td>
                                        <td>
                                            {{ $entry->notes }}
                                            @if ($entry->link)<br><a href="{{ $entry->link }}" target="_blank" rel="noopener">Link</a>@endif
                                            @if ($entry->upload)<br><a href="{{ globalAsset($entry->upload->path) }}" target="_blank">Document</a>@endif
                                        </td>
                                        <td>@if ($entry->feedback)<span class="badge-basic-info-text">{{ $entry->feedback }}</span>@endif</td>
                                        <td class="action"></td>
                                    @elseif ($entry)
                                        <td colspan="3">
                                            <form action="{{ route('portal-work-log.entry.store') }}" method="post" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-center work-log-slot-form">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $entry->id }}">
                                                <input type="hidden" name="date" value="{{ $data['date'] }}">
                                                <input type="hidden" name="default_start" value="{{ $slot['default_start'] }}">
                                                @include('portal.work-log._time-editor', ['prefix' => $prefix, 'startValue' => $slot['start'], 'endValue' => $slot['end']])
                                                @include('portal.work-log._activity-select', ['myTasks' => $data['myTasks'], 'myDuties' => $data['myDuties'], 'activities' => $data['settings']->activities, 'selectedTaskId' => $entry->task_id, 'selectedActivity' => $entry->activity])
                                                @include('portal.work-log._notes-fields', ['prefix' => $prefix, 'notesValue' => $entry->notes, 'linkValue' => $entry->link, 'currentUpload' => $entry->upload])
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
                                        <td colspan="5" class="text-secondary">— not logged —</td>
                                    @else
                                        <td colspan="3">
                                            <form action="{{ route('portal-work-log.entry.store') }}" method="post" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-center work-log-slot-form">
                                                @csrf
                                                <input type="hidden" name="date" value="{{ $data['date'] }}">
                                                <input type="hidden" name="default_start" value="{{ $slot['default_start'] }}">
                                                @include('portal.work-log._time-editor', ['prefix' => $prefix, 'startValue' => $slot['start'], 'endValue' => $slot['end']])
                                                @include('portal.work-log._activity-select', ['myTasks' => $data['myTasks'], 'myDuties' => $data['myDuties'], 'activities' => $data['settings']->activities, 'selectedTaskId' => null, 'selectedActivity' => null])
                                                @include('portal.work-log._notes-fields', ['prefix' => $prefix, 'notesValue' => null, 'linkValue' => null, 'currentUpload' => null])
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
                            @php($prefix = 'extra' . $entry->id)
                            <tr>
                                @if ($data['isLocked'])
                                    <td>{{ $entry->start_label }}–{{ $entry->end_label }} <span class="badge-basic-info-text">Extra</span></td>
                                    <td>{{ $entry->task ? 'Task: ' . $entry->task->title : $entry->activity }}</td>
                                    <td>
                                        {{ $entry->notes }}
                                        @if ($entry->link)<br><a href="{{ $entry->link }}" target="_blank" rel="noopener">Link</a>@endif
                                        @if ($entry->upload)<br><a href="{{ globalAsset($entry->upload->path) }}" target="_blank">Document</a>@endif
                                    </td>
                                @else
                                    <td colspan="3">
                                        <form action="{{ route('portal-work-log.entry.store') }}" method="post" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-center work-log-slot-form">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $entry->id }}">
                                            <input type="hidden" name="date" value="{{ $data['date'] }}">
                                            <input type="hidden" name="is_extra" value="1">
                                            <span class="badge-basic-info-text align-self-center">Extra</span>
                                            @include('portal.work-log._time-editor', ['prefix' => $prefix, 'startValue' => $entry->start_label, 'endValue' => $entry->end_label])
                                            @include('portal.work-log._activity-select', ['myTasks' => $data['myTasks'], 'myDuties' => $data['myDuties'], 'activities' => $data['settings']->activities, 'selectedTaskId' => $entry->task_id, 'selectedActivity' => $entry->activity])
                                            @include('portal.work-log._notes-fields', ['prefix' => $prefix, 'notesValue' => $entry->notes, 'linkValue' => $entry->link, 'currentUpload' => $entry->upload])
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
                        <form action="{{ route('portal-work-log.entry.store') }}" method="post" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-center work-log-slot-form">
                            @csrf
                            <input type="hidden" name="date" value="{{ $data['date'] }}">
                            <input type="hidden" name="is_extra" value="1">
                            @include('portal.work-log._time-editor', ['prefix' => 'newExtra', 'startValue' => '', 'endValue' => ''])
                            @include('portal.work-log._activity-select', ['myTasks' => $data['myTasks'], 'myDuties' => $data['myDuties'], 'activities' => $data['settings']->activities, 'selectedTaskId' => null, 'selectedActivity' => null])
                            @include('portal.work-log._notes-fields', ['prefix' => 'newExtra', 'notesValue' => null, 'linkValue' => null, 'currentUpload' => null])
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

        // main.js hides every input[type=file] on every page - clicking the
        // visible "Browse" <label> still opens the picker natively via its
        // for="", so this only needs to mirror the chosen filename into the
        // readonly text box next to it.
        document.addEventListener('change', function (e) {
            if (!e.target.classList.contains('bn-file-input')) return;
            var wrapper = e.target.closest('.ot_fileUploader');
            var placeholder = wrapper ? wrapper.querySelector('.bn-file-placeholder') : null;
            if (placeholder) {
                placeholder.value = e.target.files.length ? e.target.files[0].name : '';
            }
        });

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
