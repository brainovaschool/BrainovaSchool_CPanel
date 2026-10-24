@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        {{-- bradecrumb Area S t a r t --}}
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('leave-request.index') }}">{{ ___('leave.Leave Log') }}</a></li>
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}

        <!--  table content start -->
        <div class="table-content table-basic mt-20">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $data['title'] }}</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered role-table">
                            <thead class="thead">
                            <tr>
                                <th class="serial">{{ ___('common.ID') }}</th>
                                <th class="purchase">{{ ___('common.Student') }}</th>
                                <th class="purchase">{{ ___('common.Date') }}</th>
                                <th class="purchase">{{ ___('leave.Leave Type') }}</th>
                                <th class="purchase">{{ ___('common.Substitute Day') }}</th>
                                <th class="purchase">{{ ___('common.Attachment') }}</th>
                                <th class="action">{{ ___('common.Action') }}</th>
                                <th class="purchase">{{ ___('common.Approved by') }}</th>
                                <th class="purchase">{{ ___('common.Status') }}</th>
                            </tr>
                            </thead>
                            <tbody class="tbody">
                            @forelse ($data['leave_requests'] ?? [] as $row)
                                <tr id="row_{{ $row->id }}">
                                    <td class="serial">{{ $row->identifier }}</td>
                                    <td>{{ $row->user->name}}</td>
                                    <td>{{ dateFormat($row->start_date) }} - {{ dateFormat($row->end_date) }} / ({{ $row->leave_days }} {{ ___('student.days') }})</td>
                                    <td>{{ $row->leaveType->name }}</td>
                                    <td>{{ $row->replacement_day ? dateFormat($row->replacement_day) : '—' }}</td>
                                    <td>
                                        @if ($row->attachment)
                                            <a href="{{ asset($row->attachment->path) }}" target="_blank">
                                                {{ ___('common.Preview') }}
                                                <i class="fa fa-download ms-1"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">{{ ___('common.No Attachment') }}</span>
                                        @endif
                                    </td>
                                    <td class="action">
                                        <form action="{{ route('leave-request.approve', $row->id) }}" method="post" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm badge-basic-success-text" type="submit">{{ ___('common.Approved') }}</button>
                                        </form>
                                        <form action="{{ route('leave-request.reject', $row->id) }}" method="post" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm badge-basic-danger-text" type="submit">{{ ___('common.Rejected') }}</button>
                                        </form>
                                    </td>
                                    <td>{{ optional($row->approvedBy)->name}}</td>
                                    <td>{{ strtoupper($row->approval_status)}}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="100%" class="text-center gray-color">
                                        <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary"
                                             width="100">
                                        <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                        <p class="mb-0 text-center text-secondary font-size-90">
                                            {{ ___('leave.no_pending_leave_requests') }}
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-area mt-20">
                        {{ $data['leave_requests']->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
