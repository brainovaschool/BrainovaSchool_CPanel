@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php
        $entriesByStart = $data['entries']->keyBy(fn ($e) => $e->start_label);
        $extraEntries = $data['entries']->where('is_extra', true);
    @endphp
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
                        <input type="date" name="date" class="ot-input" value="{{ $data['date'] }}">
                        <button class="btn ot-btn-primary" type="submit">Go</button>
                        @if ($data['isLocked'])
                            <span class="badge-basic-success-text">Submitted — locked</span>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="card ot-card">
            <div class="card-header"><h4 class="mb-0">{{ \Carbon\Carbon::parse($data['date'])->format('l, d M Y') }}</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-bordered class-table">
                    <thead class="thead">
                        <tr><th style="width:140px">Time</th><th>Activity</th><th>Notes</th><th class="action">{{ ___('common.action') }}</th></tr>
                    </thead>
                    <tbody class="tbody">
                        @foreach ($data['slots'] as $slot)
                            @if ($slot['is_lunch'])
                                <tr class="table-light"><td>{{ $slot['start'] }}–{{ $slot['end'] }}</td><td colspan="3" class="text-secondary">Lunch</td></tr>
                            @else
                                @php($entry = $entriesByStart->get($slot['start']))
                                <tr>
                                    <td>{{ $slot['start'] }}–{{ $slot['end'] }}</td>
                                    @if ($entry)
                                        <td>{{ $entry->task ? 'Task: ' . $entry->task->title : $entry->activity }}</td>
                                        <td>{{ $entry->notes }}</td>
                                        <td class="action">
                                            @unless ($data['isLocked'])
                                                <form action="{{ route('portal-work-log.entry.delete', $entry->id) }}" method="post" onsubmit="return confirm('Remove this entry?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Remove</button>
                                                </form>
                                            @endunless
                                        </td>
                                    @elseif ($data['isLocked'])
                                        <td colspan="3" class="text-secondary">— not logged —</td>
                                    @else
                                        <td colspan="3">
                                            <form action="{{ route('portal-work-log.entry.store') }}" method="post" class="d-flex gap-2 flex-wrap align-items-center">
                                                @csrf
                                                <input type="hidden" name="date" value="{{ $data['date'] }}">
                                                <input type="hidden" name="start_time" value="{{ $slot['start'] }}">
                                                <input type="hidden" name="end_time" value="{{ $slot['end'] }}">
                                                <select name="task_or_activity" class="nice-select niceSelect bordered_style" style="min-width:220px" onchange="
                                                    var v=this.value; var f=this.form;
                                                    if (v.indexOf('task:')===0){ f.task_id.value=v.slice(5); f.activity.value=''; }
                                                    else { f.task_id.value=''; f.activity.value=v; }
                                                ">
                                                    <option value="">Pick one...</option>
                                                    <optgroup label="My Tasks">
                                                        @foreach ($data['myTasks'] as $t)
                                                            <option value="task:{{ $t->id }}">{{ $t->title }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                    <optgroup label="Activities">
                                                        @foreach ($data['settings']->activities as $act)
                                                            <option value="{{ $act }}">{{ $act }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                </select>
                                                <input type="hidden" name="task_id">
                                                <input type="hidden" name="activity">
                                                <input type="text" name="notes" class="ot-input" placeholder="Notes (optional)" style="max-width:200px">
                                                <button type="submit" class="btn btn-sm ot-btn-primary">Save</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endif
                        @endforeach

                        @foreach ($extraEntries as $entry)
                            <tr>
                                <td>{{ $entry->start_label }}–{{ $entry->end_label }} <span class="badge-basic-info-text">Extra</span></td>
                                <td>{{ $entry->task ? 'Task: ' . $entry->task->title : $entry->activity }}</td>
                                <td>{{ $entry->notes }}</td>
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
                    <div class="border-top pt-3 mt-2">
                        <h5>Add an extra entry</h5>
                        <form action="{{ route('portal-work-log.entry.store') }}" method="post" class="d-flex gap-2 flex-wrap align-items-center">
                            @csrf
                            <input type="hidden" name="date" value="{{ $data['date'] }}">
                            <input type="hidden" name="is_extra" value="1">
                            <input type="time" name="start_time" class="ot-input" required>
                            <input type="time" name="end_time" class="ot-input" required>
                            <select name="task_or_activity" class="nice-select niceSelect bordered_style" style="min-width:220px" onchange="
                                var v=this.value; var f=this.form;
                                if (v.indexOf('task:')===0){ f.task_id.value=v.slice(5); f.activity.value=''; }
                                else { f.task_id.value=''; f.activity.value=v; }
                            ">
                                <option value="">Pick one...</option>
                                <optgroup label="My Tasks">
                                    @foreach ($data['myTasks'] as $t)
                                        <option value="task:{{ $t->id }}">{{ $t->title }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Activities">
                                    @foreach ($data['settings']->activities as $act)
                                        <option value="{{ $act }}">{{ $act }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                            <input type="hidden" name="task_id">
                            <input type="hidden" name="activity">
                            <input type="text" name="notes" class="ot-input" placeholder="Notes (optional)" style="max-width:200px">
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
                <table class="table table-bordered class-table">
                    <thead class="thead"><tr><th>Date</th><th>Hours logged</th><th>{{ ___('common.status') }}</th></tr></thead>
                    <tbody class="tbody">
                        @foreach ($data['history'] as $row)
                            <tr>
                                <td><a href="{{ route('portal-work-log.index', ['date' => $row['date']]) }}">{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</a></td>
                                <td>{{ $row['hours'] }}</td>
                                <td>
                                    @if ($row['submitted'])
                                        <span class="badge-basic-success-text">Submitted</span>
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
@endsection
