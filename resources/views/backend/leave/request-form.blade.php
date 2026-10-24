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
                <div class="col-lg-3">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">{{ $data['title'] }}</h4>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('leave-request.store') }}" enctype="multipart/form-data" method="post"
                                  id="marksheet">
                                @csrf
                                <div class="row mb-3">
                                    <div class="col-lg-12">
                                        <div class="row">

                                            <div class="col-md-12 mb-3">
                                                <label for="validationServer04"
                                                       class="form-label">{{ ___('leave.Leave type') }} <span
                                                        class="fillable">*</span></label>
                                                <select
                                                    class="nice-select niceSelect bordered_style wide @error('leave_type') is-invalid @enderror"
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

                                            <input type="hidden" name="editLeaveId" value="{{ $editLeave->id ?? null }}">

                                            {{-- Start Date --}}
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">{{ ___('common.Start Date') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="date"
                                                       class="form-control ot-input @error('start_date') is-invalid @enderror"
                                                       name="start_date"
                                                       value="{{ old('start_date', $editLeave->start_date ?? '') }}">
                                            </div>

                                            {{-- End Date --}}
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">{{ ___('common.End Date') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="date"
                                                       class="form-control ot-input @error('end_date') is-invalid @enderror"
                                                       name="end_date"
                                                       value="{{ old('end_date', $editLeave->end_date ?? '') }}">
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_half_day"
                                                           value="1" id="is_half_day"
                                                        {{ old('is_half_day', $editLeave->is_half_day ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label"
                                                           for="is_half_day">{{ ___('common.Half Day') }}</label>
                                                </div>
                                            </div>

                                            @if ($data['selfIsStaff'] ?? false)
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">{{ ___('common.Substitute Day') }}</label>
                                                    <input type="date"
                                                           class="form-control ot-input @error('replacement_day') is-invalid @enderror"
                                                           name="replacement_day"
                                                           value="{{ old('replacement_day', $editLeave->replacement_day ?? '') }}">
                                                    @error('replacement_day')
                                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                    @enderror
                                                </div>
                                            @endif

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
                                                        <input type="file" class="d-none form-control" name="attachment"
                                                               id="fileBrouse">
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Description --}}
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">{{ ___('fees.description') }}</label>
                                                <textarea
                                                    class="form-control ot-textarea mt-0 @error('description') is-invalid @enderror"
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

                {{--  second card --}}
                <div class="col-lg-9">
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
                                        <th class="purchase">{{ ___('common.Date') }}</th>
                                        <th class="purchase">{{ ___('leave.Leave Type') }}</th>
                                        <th class="purchase">{{ ___('common.Status') }}</th>
                                        <th class="purchase">{{ ___('common.Half Day') }}</th>
                                        @if ($data['selfIsStaff'] ?? false)
                                            <th class="purchase">{{ ___('common.Substitute Day') }}</th>
                                        @endif
                                        <th class="purchase">{{ ___('common.Attachment') }}</th>
                                        <th class="action">{{ ___('common.Action') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody class="tbody">
                                    @forelse ($data['leave_requests'] ?? [] as $key => $row)
                                        <tr id="row_{{ $row->id }}">
                                            <td class="serial">{{ ++$key }}</td>
                                            <td>{{ dateFormat($row->start_date) }} - {{ dateFormat($row->end_date) }} / ({{ $row->leave_days }} {{ ___('student.days') }})</td>
                                            <td>{{ $row->leaveType->name }}</td>
                                            <td>{{ strtoupper($row->approval_status) }}</td>
                                            <td>{{ $row->is_half_day ? ___('common.Yes') : ___('common.No') }}</td>
                                            @if ($data['selfIsStaff'] ?? false)
                                                <td>{{ $row->replacement_day ? dateFormat($row->replacement_day) : '—' }}</td>
                                            @endif
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
                                                @if ($row->approval_status == 'pending')
                                                    <div class="dropdown dropdown-action">
                                                        <button type="button" class="btn-dropdown"
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fa-solid fa-ellipsis"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end ">
                                                            <li>
                                                                <a class="dropdown-item"
                                                                   href="{{ route('leave-request.edit', $row->id) }}"><span
                                                                        class="icon mr-8"><i
                                                                            class="fa-solid fa-pen-to-square"></i></span>
                                                                    {{ ___('common.edit') }}</a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0);"
                                                                   onclick="delete_row('leave-request/delete', {{ $row->id }})">
                                                                    <span class="icon mr-8"><i
                                                                            class="fa-solid fa-trash-can"></i></span>
                                                                    <span>{{ ___('common.delete') }}</span>
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="100%" class="text-center gray-color">
                                                <img src="{{ asset('images/no_data.svg') }}" alt=""
                                                     class="mb-primary" width="100">
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
@endsection
@push('script')
    @include('backend.partials.delete-ajax')
@endpush
