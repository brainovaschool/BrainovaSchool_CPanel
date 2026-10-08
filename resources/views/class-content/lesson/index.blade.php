@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    @php
        $module = $data['module'];
        $isLocked = (int) auth()->user()->role_id === 5
            && !in_array($module->review_status, [\App\Models\ClassContent\ClassContentModule::DRAFT, \App\Models\ClassContent\ClassContentModule::CHANGES_REQUESTED]);
    @endphp
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $module->title }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('class-content-module.index') }}">Class Content</a></li>
                        <li class="breadcrumb-item active">Lessons</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                    <div>
                        <div class="mb-1">@include('class-content._status-badge', ['status' => $module->review_status])</div>
                        <p class="mb-0 text-secondary" style="font-size:.85rem;">
                            {{ optional($module->class)->name }}{{ $module->section ? ' - ' . optional($module->section)->name : '' }} — {{ optional($module->subject)->name }}
                        </p>
                    </div>
                    @if (hasPermission('class_content_submit') && !$isLocked && in_array($module->review_status, [\App\Models\ClassContent\ClassContentModule::DRAFT, \App\Models\ClassContent\ClassContentModule::CHANGES_REQUESTED]))
                        <form action="{{ route('class-content-module.submit', $module->id) }}" method="post" onsubmit="return confirm('Submit this module for coordinator review?');">
                            @csrf
                            <button class="btn btn-lg ot-btn-primary" @if ($data['lessons']->isEmpty()) disabled title="Add at least one lesson first" @endif>
                                <span><i class="fa-solid fa-paper-plane"></i> </span> Submit for review
                            </button>
                        </form>
                    @endif
                </div>

                @include('class-content._status-stepper', ['status' => $module->review_status])

                @include('class-content._feedback-panel', ['item' => $module])
            </div>
        </div>

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Lessons</h4>
                    @if (hasPermission('class_content_create') && !$isLocked)
                        <a href="{{ route('class-content-module.lessons.create', $module->id) }}" class="btn btn-lg ot-btn-primary">
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
                                    <th>{{ ___('common.Serial') }}</th>
                                    <th>Title</th>
                                    <th>Class Date</th>
                                    <th>Materials</th>
                                    <th>Activities</th>
                                    <th>Outcomes</th>
                                    <th class="action">{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['lessons'] as $row)
                                    <tr id="row_{{ $row->id }}">
                                        <td>{{ $row->sort_order }}</td>
                                        <td>{{ $row->title }}</td>
                                        <td>{{ $row->class_date ? \Carbon\Carbon::parse($row->class_date)->format('d M Y') : '—' }}</td>
                                        <td>{{ $row->materials_count }}</td>
                                        <td>{{ $row->activities_count }}</td>
                                        <td>{{ $row->outcomes_count }}</td>
                                        <td class="action">
                                            <div class="dropdown dropdown-action">
                                                <button type="button" class="btn-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa-solid fa-ellipsis"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if (hasPermission('class_content_update') && !$isLocked)
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('class-content-module.lessons.edit', [$module->id, $row->id]) }}">
                                                                <span class="icon mr-8"><i class="fa-solid fa-pen-to-square"></i></span>{{ ___('common.edit') }}
                                                            </a>
                                                        </li>
                                                    @endif
                                                    @if (hasPermission('class_content_delete') && !$isLocked)
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:void(0);"
                                                                onclick="delete_row('class-content/{{ $module->id }}/lessons/delete', {{ $row->id }})">
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
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    @include('backend.partials.delete-ajax')
@endpush
