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
                        <li class="breadcrumb-item">Attendance</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-12 p-0">
            <form action="{{ route('portal-attendance.index') }}" method="get">
                <div class="card ot-card mb-24 position-relative z_1">
                    <div class="card-header d-flex align-items-center gap-4 flex-wrap">
                        <h3 class="mb-0">{{ ___('common.Filtering') }}</h3>
                        <div class="card_header_right d-flex align-items-center gap-3 flex-fill justify-content-end flex-wrap">
                            <input type="date" name="date" class="ot-input" value="{{ $data['date'] }}">
                            <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">Attendance — {{ \Carbon\Carbon::parse($data['date'])->format('d M Y') }}</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>First login</th>
                                    <th>{{ ___('common.status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @foreach ($data['rows'] as $row)
                                    <tr>
                                        <td>{{ trim($row['staff']->first_name . ' ' . $row['staff']->last_name) }}</td>
                                        <td>{{ optional($row['staff']->role)->name ?? '—' }}</td>
                                        <td>{{ $row['attendance'] ? $row['attendance']->first_login_at->format('h:i A') : '—' }}</td>
                                        <td>
                                            @if ($row['attendance'])
                                                <span class="badge-basic-success-text">Present</span>
                                            @else
                                                <span class="badge-basic-danger-text">Not signed in</span>
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
    </div>
@endsection
