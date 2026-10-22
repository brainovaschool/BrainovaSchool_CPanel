@php use App\Enums\RoleEnum; @endphp
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
        <!--  table content start -->
        <div class="table-content table-basic mt-20">
            <div class="row">
                <div class="col-3">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Add {{ $data['title'] }}</h4>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('leave-request.store') }}" enctype="multipart/form-data" method="post" id="marksheet">
                                @csrf
                                <div class="row mb-3">
                                    <div class="col-lg-12">
                                        <div class="row">

                                            <div class="col-md-12 mb-3">
                                                <label for="validationServer04"
                                                       class="form-label">{{ ___('common.Select Role') }} <span
                                                        class="fillable">*</span></label>
                                                <select
                                                    class="nice-select niceSelect bordered_style wide @error('role_id') is-invalid @enderror"
                                                    name="role_id" id="validationServer04"
                                                    aria-describedby="validationServer04Feedback">
                                                    <option value="">{{ ___('common.Select Role') }}</option>
                                                    <option value="{{ RoleEnum::STUDENT }}"
                                                        {{ old('role_id', $editLeave->role_id ?? '') == RoleEnum::STUDENT ? 'selected' : '' }}>
                                                        {{ ___('common.Student') }}
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label for="validationServer04"
                                                       class="form-label">{{ ___('leave.Leave type') }} <span
                                                        class="fillable">*</span></label>
                                                <select
                                                    class="nice-select niceSelect bordered_style wide @error('role_id') is-invalid @enderror"
                                                    name="leave_type" id="validationServer04"
                                                    aria-describedby="validationServer04Feedback">
                                                    <option value="">{{ ___('common.Select leave type') }}</option>
                                                    @foreach ($data['types'] as $type)
                                                        <option value="{{ $type->id }}"
                                                            {{ old('leave_type', $editLeave->leave_type_id ?? '') == $type->id ? 'selected' : '' }}>
                                                            {{ $type->name }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                @error('leave_type')
                                                <div id="validationServer04Feedback" class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                                @enderror
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label for="validationServer04"
                                                       class="form-label">{{ ___('common.Select Class') }} <span
                                                        class="fillable">*</span></label>

                                                <select id="getSections"
                                                        class="class nice-select niceSelect bordered_style wide @error('class') is-invalid @enderror"
                                                        name="class">
                                                    <option value="">{{ ___('student_info.select_class') }} *</option>
                                                    @foreach ($data['classes'] as $item)
                                                        <option value="{{ $item->class->id }}"
                                                            {{ old('class', $data['class_id'] ?? '') == $item->class->id ? 'selected' : '' }}>
                                                            {{ $item->class->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('class')
                                                <div id="validationServer04Feedback" class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                                @enderror
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label for="validationServer04"
                                                       class="form-label">{{ ___('common.Select Section') }}</label>

                                                <select
                                                    class="sections section nice-select niceSelect bordered_style wide @error('section') is-invalid @enderror"
                                                    name="section">
                                                    <option value="">{{ ___('student_info.select_section') }}</option>
                                                    @if(isset($data['section']))
                                                        <option value="{{$data['section']->id}}" selected>{{$data['section']->name}}</option>
                                                    @endif
                                                </select>
                                                @error('section')
                                                <div id="validationServer04Feedback" class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                                @enderror
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label for="validationServer04"
                                                       class="form-label">{{ ___('common.Select Student') }}</label>

                                                <select
                                                    class="students nice-select niceSelect bordered_style wide @error('student') is-invalid @enderror"
                                                    name="student">
                                                    <option value="">{{ ___('student_info.select_student') }}</option>
                                                    @if(isset($data['student_id']))
                                                        <option value="{{ $data['student_id']}}" selected>
                                                            {{ $editLeave->user->full_name ?? $editLeave->user->name }}
                                                        </option>
                                                    @endif
                                                </select>
                                                @error('student')
                                                <div id="validationServer04Feedback" class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                                @enderror
                                            </div>

                                            @if ($data['isManager'] ?? false)
                                                <div class="col-md-12 mb-3">
                                                    <label for="validationServer04"
                                                           class="form-label">{{ ___('common.Select Status') }} <span class="text-danger">*</span></label>

                                                    <select class="nice-select niceSelect bordered_style wide @error('approval_status') is-invalid @enderror"
                                                            name="approval_status">
                                                        <option value="">{{ ___('common.Select Status') }}</option>
                                                        <option value="pending" {{ old('approval_status', $editLeave->approval_status ?? '') == 'pending' ? 'selected' : '' }}>{{ ___('common.Pending') }}</option>
                                                        <option value="approved" {{ old('approval_status', $editLeave->approval_status ?? '') == 'approved' ? 'selected' : '' }}>{{ ___('common.Approved') }}</option>
                                                        <option value="rejected" {{ old('approval_status', $editLeave->approval_status ?? '') == 'rejected' ? 'selected' : '' }}>{{ ___('common.Rejected') }}</option>
                                                    </select>
                                                    @error('approval_status')
                                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                    @enderror
                                                </div>
                                            @else
                                                {{-- A raise-only role never decides the outcome — always submitted
                                                     as pending; the server re-enforces this regardless. --}}
                                                <input type="hidden" name="approval_status" value="pending">
                                            @endif

                                            <input type="hidden" name="editLeaveId" value="{{ $editLeave->id ?? null }}">

                                            {{-- Start Date --}}
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">{{ ___('common.Start Date') }} <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control ot-input @error('start_date') is-invalid @enderror"
                                                       name="start_date"
                                                       value="{{ old('start_date', $editLeave->start_date ?? '') }}">
                                            </div>

                                            {{-- End Date --}}
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">{{ ___('common.End Date') }} <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control ot-input @error('end_date') is-invalid @enderror"
                                                       name="end_date"
                                                       value="{{ old('end_date', $editLeave->end_date ?? '') }}">
                                            </div>


                                            <div class="col-md-12 mb-3">
                                                <label for="exampleDataList"
                                                       class="form-label ">{{ ___('common.Attachment') }}<span
                                                        class="fillable"></span></label>
                                                <div class="ot_fileUploader left-side mb-3">
                                                    <input class="form-control" type="text"
                                                           placeholder="{{ ___('common.Documents') }}" readonly=""
                                                           id="placeholder">
                                                    <button class="primary-btn-small-input" type="button">
                                                        <label class="btn btn-lg ot-btn-primary text-nowrap "
                                                               for="fileBrouse">{{ ___('common.browse') }}</label>
                                                        <input type="file" class="d-none form-control"
                                                               name="attachment" id="fileBrouse">
                                                    </button>
                                                </div>
                                            </div>


                                            {{-- Description --}}
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">{{ ___('fees.description') }}</label>
                                                <textarea class="form-control ot-textarea mt-0 @error('description') is-invalid @enderror"
                                                          name="description">{{ old('description', $editLeave->description ?? '') }}</textarea>
                                            </div>

                                            <div class="col-md-12 mt-24">
                                                <div class="text-end">
                                                    <button class="btn btn-lg ot-btn-primary" type="submit"><span><i
                                                                class="fa-solid fa-save"></i>
                                                            </span>{{ ___('common.submit') }}</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{--                second card--}}
                <div class="col-9">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">{{ $data['title'] }}</h4>

                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered role-table">
                                    <thead class="thead">
                                    <tr>
                                        <th class="serial">{{ ___('common.Sl No') }}</th>
                                        <th class="purchase">{{ ___('common.Student') }}</th>
                                        <th class="purchase">{{ ___('common.Date') }}</th>

                                        <th class="purchase">{{ ___('leave.Leave Type') }}</th>

                                        <th class="purchase">{{ ___('common.Status') }}</th>
                                        <th class="purchase">{{ ___('common.Requested by')}}</th>
                                        <th class="purchase">{{ ___('common.Approved by') }}</th>
                                        <th class="purchase">{{ ___('common.Attachment') }}</th>
                                        @if (hasPermission('leave_request_update') || hasPermission('leave_request_delete'))
                                            <th class="action">{{ ___('common.Action') }}</th>
                                        @endif
                                    </tr>
                                    </thead>
                                    <tbody class="tbody">
                                    @forelse ($data['leave_requests'] ?? [] as $key => $row)
                                        <tr id="row_{{ $row->id }}">
                                            <td class="serial">{{ ++$key }}</td>
                                            <td>{{ $row->user->name}}</td>
                                            <td>{{ dateFormat($row->start_date) }} - {{ dateFormat($row->end_date) }} / ({{ $row->leave_days }} {{ ___('student.days') }})</td>

                                            <td>{{ $row->leaveType->name }}</td>
                                            <td>{{ strtoupper($row->approval_status)}}</td>
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
                                                            {{ $data['leave_requests']->links() }}
                                                        </div>
                            <!--  pagination end -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="view-modal">
        <div class="modal fade" id="openIdCardPreviewModal" tabindex="-1" aria-labelledby="modalWidth"
             aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content" id="modalWidth">
                    <div class="modal-header modal-header-image">
                        <h5 class="modal-title" id="modalLabel2">
                            {{ ___('common.Preview') }}
                        </h5>
                        <button type="button" class="m-0 btn-close d-flex justify-content-center align-items-center"
                                data-bs-dismiss="modal" aria-label="Close"><i class="fa fa-times text-white"
                                                                              aria-hidden="true"></i></button>
                    </div>
                    <div class="modal-body p-5">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    @include('backend.partials.delete-ajax')
@endpush
