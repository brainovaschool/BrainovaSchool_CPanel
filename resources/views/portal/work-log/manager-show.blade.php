@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('portal-work-logs.index') }}">Work Logs</a></li>
                        <li class="breadcrumb-item">{{ trim($data['staff']->first_name . ' ' . $data['staff']->last_name) }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card mb-24">
            <div class="card-header d-flex gap-2">
                @foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month'] as $key => $label)
                    <a href="{{ route('portal-work-logs.show', ['staffId' => $data['staff']->id, 'range' => $key]) }}" class="btn btn-sm {{ $data['range'] === $key ? 'ot-btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
                @endforeach
                <span class="text-secondary align-self-center ms-2">{{ \Carbon\Carbon::parse($data['from'])->format('d M') }} – {{ \Carbon\Carbon::parse($data['to'])->format('d M Y') }}</span>
            </div>
        </div>

        @forelse ($data['days'] as $date => $entries)
            <div class="card ot-card mb-3">
                <div class="card-header"><h5 class="mb-0">{{ \Carbon\Carbon::parse($date)->format('l, d M Y') }} — {{ round($entries->sum(fn($e) => $e->hours()), 2) }}h</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="table table-bordered class-table">
                        <thead class="thead"><tr><th style="width:140px">Time</th><th>Activity</th><th>Notes</th></tr></thead>
                        <tbody class="tbody">
                            @foreach ($entries as $entry)
                                <tr>
                                    <td>{{ $entry->start_label }}–{{ $entry->end_label }} @if ($entry->is_extra)<span class="badge-basic-info-text">Extra</span>@endif</td>
                                    <td>{{ $entry->task ? 'Task: ' . $entry->task->title : $entry->activity }}</td>
                                    <td>{{ $entry->notes }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="card ot-card">
                <div class="card-body text-center gray-color">
                    <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                    <p class="mb-0">No work log entries in this range.</p>
                </div>
            </div>
        @endforelse
    </div>
@endsection
