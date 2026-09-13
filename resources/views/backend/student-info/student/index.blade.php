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
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                            <li class="breadcrumb-item">{{ $data['title'] }}</li>
                        </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}


        <div class="col-12">
            <form action="{{ route('student.search') }}" method="post" id="marksheed" enctype="multipart/form-data">
                @csrf
                <div class="card ot-card mb-24 position-relative z_1">
                    <div class="card-header d-flex align-items-center gap-4 flex-wrap">
                        <h3 class="mb-0">{{ ___('common.Filtering') }}</h3>

                        <div
                            class="card_header_right d-flex align-items-center gap-3 flex-fill justify-content-end flex-wrap">
                            <!-- table_searchBox -->
                            <div class="single_large_selectBox">
                                <select id="getSections"
                                    class="class nice-select niceSelect bordered_style wide @error('class') is-invalid @enderror"
                                    name="class">
                                    <option value="">{{ ___('student_info.select_class') }}</option>
                                    @foreach ($data['classes'] as $item)
                                        <option
                                            {{ old('class', @$data['request']->class) == $item->class->id ? 'selected' : '' }}
                                            value="{{ $item->class->id }}">{{ $item->class->name }}</option>
                                    @endforeach
                                </select>
                                @error('class')
                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="single_large_selectBox">
                                <select
                                    class="sections section nice-select niceSelect bordered_style wide @error('section') is-invalid @enderror"
                                    name="section">
                                    <option value="">{{ ___('student_info.select_section') }}</option>
                                    @foreach ($data['sections'] as $item)
                                        <option
                                            {{ old('section', @$data['request']->section) == $item->section->id ? 'selected' : '' }}
                                            value="{{ $item->section->id }}">{{ $item->section->name }}</option>
                                    @endforeach
                                </select>
                                @error('section')
                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="single_large_selectBox">
                                <input class="form-control ot-input" name="keyword" list="datalistOptions"
                                    id="exampleDataList" placeholder="{{ ___('student_info.enter_keyword') }}"
                                    value="{{ old('keyword', @$data['request']->keyword) }}">
                            </div>

                            <button class="btn btn-lg ot-btn-primary" type="submit">
                                {{ ___('common.Search') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>


        <!--  table content start -->
        <div class="table-content table-basic mt-20">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $data['title'] }}</h4>
                    @if (hasPermission('student_create'))
                        <a href="{{ route('student.create') }}" class="btn btn-lg ot-btn-primary">
                            <span><i class="fa-solid fa-plus"></i> </span>
                            <span class="">{{ ___('common.add') }}</span>
                        </a>
                    @endif
                </div>
                @if (@$data['students'])
                    <div class="card-body">
                        @if (hasPermission('student_delete') || hasPermission('student_update'))
                            @include('backend.partials.bulk-actions-bar')
                        @endif
                        <div class="table-responsive">
                            <table class="table table-bordered role-table">
                                <thead class="thead">
                                    <tr>
                                        @if (hasPermission('student_delete') || hasPermission('student_update'))
                                            <th style="width:36px"><input type="checkbox" id="bulkSelectAll"></th>
                                        @endif
                                        <th class="serial">{{ ___('common.sr_no') }}</th>
                                        <th class="purchase">{{ ___('student_info.admission_no') }}</th>
                                        <th class="purchase">{{ ___('student_info.roll_no') }}</th>
                                        <th class="purchase">{{ ___('student_info.Department') }}</th>
                                        <th class="purchase">{{ ___('student_info.name') }}</th>
                                        <th class="purchase">{{ ___('academic.class') }} ({{ ___('academic.section') }})</th>
                                        <th class="purchase">{{ ___('student_info.guardian') }}</th>
                                        <th class="purchase">{{ ___('common.gender') }}</th>
                                        <th class="purchase">{{ ___('student_info.mobile') }}</th>
                                        @if (hasPermission('student_update') || hasPermission('student_delete'))
                                            <th class="action">{{ ___('common.action') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="tbody">
                                    {{-- @dd($data['students']) --}}
                                    @forelse ($data['students'] as $key => $row)
                                        <tr id="row_{{ @$row->student->id }}">
                                            @if (hasPermission('student_delete') || hasPermission('student_update'))
                                                <td><input type="checkbox" class="bulk-row-checkbox" value="{{ @$row->student_id }}"></td>
                                            @endif
                                            <td class="serial">{{ ++$key }}</td>
                                            <td class="serial">{{ @$row->student->admission_no }}</td>
                                            <td class="serial">{{ @$row->roll }}</td>
                                            <td class="serial">{{ @$row->student->department->name }}</td>
                                            <td>
                                                <div class="">
                                                    <a href="{{ route('student.show', @$row->student->id) }}">
                                                        <div class="user-card">
                                                            <div class="user-avatar">
                                                                <img src="{{ @globalAsset(@$row->student->user->upload->path, 'avater.png') }}"
                                                                    alt="{{ @$row->student->name }}">
                                                            </div>
                                                            <div class="user-info">
                                                                {{ @$row->student->first_name }}
                                                                {{ @$row->student->last_name }}
                                                            </div>
                                                        </div>
                                                    </a>
                                                </div>
                                            </td>
                                            <td>{{ @$row->class->name }} ({{ @$row->section->name }})</td>
                                            <td>{{ @$row->student->parent->guardian_name }}</td>

                                            <td>{{ @$row->student->gender->name }}</td>
                                            <td>{{ @$row->student->mobile }}</td>

                                            @if (hasPermission('student_update') || hasPermission('student_delete'))
                                                <td class="action">
                                                    <div class="dropdown dropdown-action">
                                                        <button type="button" class="btn-dropdown"
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fa-solid fa-ellipsis"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end ">
                                                            @if (hasPermission('student_update'))
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="{{ route('student.edit', @$row->id) }}"><span
                                                                            class="icon mr-8"><i
                                                                                class="fa-solid fa-pen-to-square"></i></span>
                                                                        {{ ___('common.edit') }}</a>
                                                                </li>
                                                            @endif
                                                            @if (hasPermission('student_delete'))
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"
                                                                        onclick="delete_row('student/delete', {{ @$row->student_id }})">
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
                                                <img src="{{ asset('images/no_data.svg') }}" alt=""
                                                    class="mb-primary" width="100">
                                                <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                                <p class="mb-0 text-center text-secondary font-size-90">
                                                    {{ ___('common.please_add_new_entity_regarding_this_table') }}</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <!--  table end -->
                        <!--  pagination start -->

                        <div class="ot-pagination pagination-content d-flex justify-content-end align-content-center py-3">
                            <nav aria-label="Page navigation example">
                                <ul class="pagination justify-content-between">
                                    {!! $data['students']->appends(\Request::capture()->except('page'))->links() !!}
                                </ul>
                            </nav>
                        </div>

                        <!--  pagination end -->
                    </div>
                @else
                    <div class="text-center gray-color p-5">
                        <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                        <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                        <p class="mb-0 text-center text-secondary font-size-90">
                            {{ ___('common.please_add_new_entity_regarding_this_table') }}</p>
                    </div>
                @endif

            </div>
        </div>
        <!--  table content end -->

    </div>
@endsection
@include('backend.partials.bulk-actions-ajax', ['bulkRoute' => 'student'])
