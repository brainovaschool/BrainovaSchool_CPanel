@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php($m = $data['metrics'])
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">My Performance</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">My Performance</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-12 p-0">
            <form action="{{ route('portal-my-performance.index') }}" method="get">
                <div class="card ot-card mb-24 d-flex flex-row align-items-center gap-3 flex-wrap" style="padding: 1rem 1.25rem;">
                    <input type="date" name="from" class="ot-input" value="{{ $data['from'] }}">
                    <span>to</span>
                    <input type="date" name="to" class="ot-input" value="{{ $data['to'] }}">
                    <button class="btn ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                </div>
            </form>
        </div>

        @if ($m)
            <div class="row gy-3 mb-24">
                @foreach ([
                    ['label' => 'Tasks assigned', 'value' => $m['assigned']],
                    ['label' => 'Completed', 'value' => $m['completed']],
                    ['label' => 'Avg final score', 'value' => $m['avg_score'] !== null ? $m['avg_score'] . '/10' : '—'],
                    ['label' => 'On-time rate', 'value' => $m['on_time_rate'] !== null ? $m['on_time_rate'] . '%' : '—'],
                    ['label' => 'Hours logged', 'value' => $m['hours']],
                    ['label' => 'Attendance days', 'value' => $m['attendance_days']],
                ] as $tile)
                    <div class="col-md-2">
                        <div class="card ot-card text-center p-3">
                            <div class="fs-4 fw-bold">{{ $tile['value'] }}</div>
                            <div class="text-secondary" style="font-size:.85rem">{{ $tile['label'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">Completed task history</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr><th>Title</th><th>Category</th><th>Completed</th><th>Revision score</th><th>Quality score</th><th>Final score</th></tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['history'] as $task)
                                    <tr>
                                        <td><a href="{{ route('portal-tasks.show', $task->id) }}">{{ $task->title }}</a></td>
                                        <td>{{ $task->category ?: '—' }}</td>
                                        <td>{{ $task->completed_at?->format('d M Y') }}</td>
                                        <td>{{ $task->revision_score }}/5</td>
                                        <td>{{ $task->quality_score }}/5</td>
                                        <td><strong>{{ $task->final_score }}/10</strong></td>
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
@endsection
