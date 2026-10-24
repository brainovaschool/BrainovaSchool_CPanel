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
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}

        @if ($data['showClassTab'] ?? false)
            <ul class="nav nav-tabs mb-20">
                <li class="nav-item">
                    <a class="nav-link {{ ($data['activeView'] ?? 'mine') === 'mine' ? 'active' : '' }}"
                       href="{{ route('leave-request.index') }}">{{ ___('leave.My Leave') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($data['activeView'] ?? 'mine') === 'class' ? 'active' : '' }}"
                       href="{{ route('leave-request.index', ['view' => 'class']) }}">{{ ___('leave.My Class Students') }}</a>
                </li>
            </ul>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card ot-card mb-24 position-relative z_1">
                    <form action="{{ route('leave-request.index') }}" method="get">
                        <input type="hidden" name="view" value="{{ $data['activeView'] ?? 'mine' }}">
                        <div class="card-header d-flex align-items-center gap-4 flex-wrap">
                            <h3 class="mb-0">{{ ___('common.Filtering') }}</h3>
                            <div class="card_header_right d-flex align-items-end gap-3 flex-wrap">
                                <div class="single_large_selectBox">
                                    <label class="form-label d-block mb-1">{{ ___('leave.Leave Type') }}</label>
                                    <select class="nice-select niceSelect bordered_style wide" name="leave_type">
                                        <option value="">{{ ___('common.All') }}</option>
                                        @foreach ($data['types'] as $type)
                                            <option value="{{ $type->id }}"
                                                {{ (string) ($data['filters']['leave_type'] ?? '') === (string) $type->id ? 'selected' : '' }}>
                                                {{ $type->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="single_large_selectBox">
                                    <label class="form-label d-block mb-1">{{ ___('common.Status') }}</label>
                                    <select class="nice-select niceSelect bordered_style wide" name="status">
                                        <option value="">{{ ___('common.All') }}</option>
                                        <option value="pending" {{ ($data['filters']['status'] ?? '') === 'pending' ? 'selected' : '' }}>{{ ___('common.Pending') }}</option>
                                        <option value="approved" {{ ($data['filters']['status'] ?? '') === 'approved' ? 'selected' : '' }}>{{ ___('common.Approved') }}</option>
                                        <option value="rejected" {{ ($data['filters']['status'] ?? '') === 'rejected' ? 'selected' : '' }}>{{ ___('common.Rejected') }}</option>
                                    </select>
                                </div>
                                <div class="single_large_selectBox">
                                    <label class="form-label d-block mb-1">{{ ___('common.Month') }}</label>
                                    <input value="{{ $data['filters']['month'] ?? '' }}" name="month"
                                           class="form-control ot-input" type="month">
                                </div>
                                <button class="btn btn-lg ot-btn-primary text-nowrap">
                                    {{ ___('common.Search') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

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

                                <th class="purchase">{{ ___('common.Status') }}</th>
                                <th class="purchase">{{ ___('common.Half Day') }}</th>
                                <th class="purchase">{{ ___('common.Substitute Day') }}</th>
                                <th class="purchase">{{ ___('common.Requested by')}}</th>
                                <th class="purchase">{{ ___('common.Approved by') }}</th>
                                <th class="purchase">{{ ___('common.Attachment') }}</th>
                                @if (hasPermission('leave_request_update') || hasPermission('leave_request_delete'))
                                    <th class="action">{{ ___('common.Action') }}</th>
                                @endif
                            </tr>
                            </thead>
                            <tbody class="tbody">
                            @forelse ($data['leave_requests'] ?? [] as $row)
                                <tr id="row_{{ $row->id }}">
                                    <td class="serial">{{ $row->identifier }}</td>
                                    <td>{{ $row->user->name}}</td>
                                    <td>{{ dateFormat($row->start_date) }} - {{ dateFormat($row->end_date) }} / ({{ $row->leave_days }} {{ ___('student.days') }})</td>

                                    <td>{{ $row->leaveType->name }}</td>
                                    <td>{{ strtoupper($row->approval_status)}}</td>
                                    <td>{{ $row->is_half_day ? ___('common.Yes') : ___('common.No') }}</td>
                                    <td>{{ $row->replacement_day ? dateFormat($row->replacement_day) : '—' }}</td>
                                    <td>{{ optional($row->requestedBy)->name}}</td>
                                    <td>{{ optional($row->approvedBy)->name}}</td>
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


                                @if (hasPermission('leave_request_update') || hasPermission('leave_request_delete'))
                                        <td class="action">
                                            <div class="dropdown dropdown-action">
                                                <button type="button" class="btn-dropdown"
                                                        data-bs-toggle="dropdown"
                                                        aria-expanded="false">
                                                    <i class="fa-solid fa-ellipsis"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end ">
                                                    @if (hasPermission('leave_request_update'))
                                                        <li>
                                                            <a class="dropdown-item"
                                                               href="{{ route('leave-request.edit', $row->id) }}"><span
                                                                    class="icon mr-8"><i
                                                                        class="fa-solid fa-pen-to-square"></i></span>
                                                                {{ ___('common.edit') }}</a>
                                                        </li>
                                                    @endif
                                                    @if (hasPermission('leave_request_delete'))
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:void(0);"
                                                               onclick="delete_row('leave-request/delete', {{ $row->id }})">
                         <span class="icon mr-8"><i
                                 class="fa-solid fa-trash-can"></i></span>
                                                                <span>{{ ___('common.delete') }}</span>
                                                            </a>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="100%" class="text-center gray-color">
                                        <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary"
                                             width="100">
                                        <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                        <p class="mb-0 text-center text-secondary font-size-90">
                                            {{ ___('common.please_add_new_entity_regarding_this_table') }}
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!--  table end -->
                    <!--  pagination start -->
                    <div class="pagination-area mt-20">
                        {{ $data['leave_requests']->appends(request()->except('page'))->links() }}
                    </div>
                    <!--  pagination end -->
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    @include('backend.partials.delete-ajax')
@endpush
