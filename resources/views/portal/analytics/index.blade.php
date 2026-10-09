@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php($maxTrend = max(1, $data['trend'] ? collect($data['trend'])->max('count') : 1))
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">Team Portal</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Analytics</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-12 p-0">
            <form action="{{ route('portal-analytics.index') }}" method="get">
                <div class="card ot-card mb-24 position-relative z_1">
                    <div class="card-header d-flex align-items-center gap-3 flex-wrap">
                        <h3 class="mb-0">{{ ___('common.Filtering') }}</h3>
                        <input type="date" name="from" class="ot-input" value="{{ $data['from'] }}">
                        <span>to</span>
                        <input type="date" name="to" class="ot-input" value="{{ $data['to'] }}">

                        <select name="assigned_to" class="nice-select niceSelect bordered_style">
                            <option value="">All employees</option>
                            @foreach ($data['employeesList'] as $emp)
                                <option value="{{ $emp->id }}" {{ (string) ($data['filters']['assigned_to'] ?? '') === (string) $emp->id ? 'selected' : '' }}>{{ trim($emp->first_name . ' ' . $emp->last_name) }}</option>
                            @endforeach
                        </select>

                        <select name="category" class="nice-select niceSelect bordered_style">
                            <option value="">All categories</option>
                            @foreach ($data['settings']->categories as $cat)
                                <option value="{{ $cat }}" {{ ($data['filters']['category'] ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>

                        <select name="priority" class="nice-select niceSelect bordered_style">
                            <option value="">All priorities</option>
                            @foreach (['Low', 'Medium', 'High'] as $p)
                                <option value="{{ $p }}" {{ ($data['filters']['priority'] ?? '') === $p ? 'selected' : '' }}>{{ $p }}</option>
                            @endforeach
                        </select>

                        <button class="btn ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="row gy-3 mb-24">
            @foreach ([
                ['label' => 'Tasks assigned', 'value' => $data['summary']['assigned']],
                ['label' => 'Completed', 'value' => $data['summary']['completed']],
                ['label' => 'Overdue now', 'value' => $data['summary']['overdue']],
                ['label' => 'Avg final score', 'value' => $data['summary']['avg_score'] !== null ? $data['summary']['avg_score'] . '/10' : '—'],
                ['label' => 'Hours logged', 'value' => $data['summary']['hours']],
            ] as $tile)
                <div class="col-md-2">
                    <div class="card ot-card text-center p-3">
                        <div class="fs-4 fw-bold">{{ $tile['value'] }}</div>
                        <div class="text-secondary" style="font-size:.85rem">{{ $tile['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="table-content table-basic mb-24">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">By employee</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr><th>Employee</th><th>Assigned</th><th>Completed</th><th>Avg score</th><th>On-time rate</th><th>Hours</th><th>Attendance days</th></tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['rows'] as $row)
                                    <tr>
                                        <td>{{ trim($row['staff']->first_name . ' ' . $row['staff']->last_name) }}</td>
                                        <td>{{ $row['assigned'] }}</td>
                                        <td>{{ $row['completed'] }}</td>
                                        <td>{{ $row['avg_score'] !== null ? $row['avg_score'] . '/10' : '—' }}</td>
                                        <td>{{ $row['on_time_rate'] !== null ? $row['on_time_rate'] . '%' : '—' }}</td>
                                        <td>{{ $row['hours'] }}</td>
                                        <td>{{ $row['attendance_days'] }}</td>
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

        <div class="card ot-card">
            <div class="card-header"><h4 class="mb-0">Completed tasks per day</h4></div>
            <div class="card-body">
                <div class="d-flex align-items-end gap-1" style="height:140px; overflow-x:auto;">
                    @foreach ($data['trend'] as $point)
                        <div class="d-flex flex-column align-items-center justify-content-end" style="min-width:22px; height:100%;" title="{{ $point['date'] }}: {{ $point['count'] }}">
                            <div style="width:14px; background:var(--bn-purple, #8b7bf0); border-radius:3px; height: {{ max(2, round($point['count'] / $maxTrend * 100)) }}%;"></div>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-between text-secondary mt-2" style="font-size:.75rem">
                    <span>{{ \Carbon\Carbon::parse($data['from'])->format('d M') }}</span>
                    <span>{{ \Carbon\Carbon::parse($data['to'])->format('d M') }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection
