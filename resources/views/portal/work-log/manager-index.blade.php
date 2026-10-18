@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">Team Portal</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Work Logs</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card mb-24">
            <div class="card-header d-flex gap-2">
                @foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month'] as $key => $label)
                    <a href="{{ route('portal-work-logs.index', ['range' => $key]) }}" class="btn btn-sm {{ $data['range'] === $key ? 'ot-btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
                @endforeach
                <span class="text-secondary align-self-center ms-2">{{ \Carbon\Carbon::parse($data['from'])->format('d M') }} – {{ \Carbon\Carbon::parse($data['to'])->format('d M Y') }}</span>
            </div>
        </div>

        @if ($data['isSuperAdmin'])
            <div class="card ot-card mb-24">
                <div class="card-header"><h5 class="mb-0">Clean up old entries</h5></div>
                <div class="card-body">
                    <form action="{{ route('portal-work-logs.bulk-delete') }}" method="post" class="d-flex gap-2 align-items-center flex-wrap"
                        onsubmit="return confirm('This permanently deletes every work log entry older than the number of days you enter. This cannot be undone. Continue?');">
                        @csrf
                        <span>Delete every entry logged more than</span>
                        <input type="number" name="days" value="45" min="1" class="form-control ot-input" style="width:90px">
                        <span>days ago</span>
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </div>
            </div>
        @endif

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="ot-basic-table ot-table-bg">
                        <thead class="thead"><tr><th>Employee</th><th>Role</th><th>Total hours</th><th class="action">{{ ___('common.action') }}</th></tr></thead>
                        <tbody class="tbody">
                            @foreach ($data['rows'] as $row)
                                <tr>
                                    <td>{{ trim($row['staff']->first_name . ' ' . $row['staff']->last_name) }}</td>
                                    <td>{{ optional($row['staff']->role)->name ?? '—' }}</td>
                                    <td>{{ $row['hours'] }}</td>
                                    <td class="action">
                                        <a href="{{ route('portal-work-logs.show', ['staffId' => $row['staff']->id, 'range' => $data['range']]) }}" class="btn btn-sm ot-btn-primary">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
