@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">

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

        <div class="table-content table-basic mt-20">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Class Content</h4>
                        <p class="text-secondary mb-0" style="font-size:.85rem;">Modules, lessons, materials and activities for your classes — a teacher builds it, a coordinator reviews it, and admin approves it before students ever see it.</p>
                    </div>
                    @if (hasPermission('class_content_create'))
                        <a href="{{ route('class-content-module.create') }}" class="btn btn-lg ot-btn-primary">
                            <span><i class="fa-solid fa-plus"></i> </span>
                            <span>{{ ___('common.add') }}</span>
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    <th>Title</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Status</th>
                                    <th class="action">{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['modules'] as $row)
                                    @php
                                        $rowLocked = (int) auth()->user()->role_id === 5
                                            && !in_array($row->review_status, [\App\Models\ClassContent\ClassContentModule::DRAFT, \App\Models\ClassContent\ClassContentModule::CHANGES_REQUESTED]);
                                    @endphp
                                    <tr id="row_{{ $row->id }}">
                                        <td>{{ $row->title }}</td>
                                        <td>{{ optional($row->class)->name }}{{ $row->section ? ' - ' . optional($row->section)->name : '' }}</td>
                                        <td>{{ optional($row->subject)->name }}</td>
                                        <td>{{ trim(optional($row->creator)->first_name . ' ' . optional($row->creator)->last_name) ?: '—' }}</td>
                                        <td>@include('class-content._status-badge', ['status' => $row->review_status])</td>
                                        <td class="action">
                                            <div class="dropdown dropdown-action">
                                                <button type="button" class="btn-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa-solid fa-ellipsis"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('class-content-module.lessons', $row->id) }}">
                                                            <span class="icon mr-8"><i class="fa-solid fa-list"></i></span>Lessons ({{ $row->lessons_count }})
                                                        </a>
                                                    </li>
                                                    @if (hasPermission('class_content_update') && !$rowLocked)
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('class-content-module.edit', $row->id) }}">
                                                                <span class="icon mr-8"><i class="fa-solid fa-pen-to-square"></i></span>{{ ___('common.edit') }}
                                                            </a>
                                                        </li>
                                                    @endif
                                                    @if (hasPermission('class_content_submit') && !$rowLocked && in_array($row->review_status, [\App\Models\ClassContent\ClassContentModule::DRAFT, \App\Models\ClassContent\ClassContentModule::CHANGES_REQUESTED]))
                                                        @if ($row->lessons_count > 0)
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0);" onclick="document.getElementById('submit-form-{{ $row->id }}').submit();">
                                                                    <span class="icon mr-8"><i class="fa-solid fa-paper-plane"></i></span>Submit for review
                                                                </a>
                                                                <form id="submit-form-{{ $row->id }}" action="{{ route('class-content-module.submit', $row->id) }}" method="post" class="d-none">
                                                                    @csrf
                                                                </form>
                                                            </li>
                                                        @else
                                                            <li>
                                                                <span class="dropdown-item text-secondary" style="cursor:not-allowed;" title="Add at least one lesson first">
                                                                    <span class="icon mr-8"><i class="fa-solid fa-paper-plane"></i></span>Submit for review
                                                                </span>
                                                            </li>
                                                        @endif
                                                    @endif
                                                    @if (hasPermission('class_content_delete') && !$rowLocked)
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:void(0);"
                                                                onclick="delete_row('class-content/delete', {{ $row->id }})">
                                                                <span class="icon mr-8"><i class="fa-solid fa-trash-can"></i></span>
                                                                <span>{{ ___('common.delete') }}</span>
                                                            </a>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center gray-color">
                                            <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                            <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="ot-pagination pagination-content d-flex justify-content-end align-content-center py-3">
                        <nav>
                            <ul class="pagination justify-content-between">
                                {!! $data['modules']->links() !!}
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    @include('backend.partials.delete-ajax')
@endpush
