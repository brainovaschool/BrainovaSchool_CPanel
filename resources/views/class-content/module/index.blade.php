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
                @if (hasPermission('class_content_create'))
                    <div class="col-sm-6 text-sm-end">
                        <a href="{{ route('class-content-module.create') }}" class="btn btn-lg ot-btn-primary">
                            <span><i class="fa-solid fa-plus"></i> </span>
                            <span>+ New module</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <p class="text-secondary mb-3">Your big picture: units, modules and lessons — a teacher builds it, a coordinator reviews it, and admin approves it before students ever see it.</p>

        @include('class-content._status-summary', [
            'counts' => $data['counts'],
            'tiles'  => [
                [[\App\Models\ClassContent\ClassContentModule::DRAFT], 'Draft', 'fa-pencil'],
                [[\App\Models\ClassContent\ClassContentModule::SUBMITTED, \App\Models\ClassContent\ClassContentModule::COORDINATOR_REVIEWED], 'In review', 'fa-paper-plane'],
                [[\App\Models\ClassContent\ClassContentModule::CHANGES_REQUESTED], 'Changes requested', 'fa-rotate-left'],
                [[\App\Models\ClassContent\ClassContentModule::APPROVED], 'Approved', 'fa-circle-check'],
            ],
        ])

        <div class="cc-stat-row mb-24">
            <div class="cc-stat"><div class="cc-stat-icon" style="border-color:#5e17eb;color:#5e17eb;"><i class="fa-solid fa-layer-group"></i></div><div class="cc-stat-label">Units</div><div class="cc-stat-value">{{ $data['dashboard']['unitsCount'] }}</div></div>
            <div class="cc-stat"><div class="cc-stat-icon" style="border-color:#0097b2;color:#0097b2;"><i class="fa-solid fa-table-cells-large"></i></div><div class="cc-stat-label">Modules</div><div class="cc-stat-value">{{ $data['dashboard']['modulesCount'] }}</div></div>
            <div class="cc-stat"><div class="cc-stat-icon" style="border-color:#16a34a;color:#16a34a;"><i class="fa-solid fa-book-open"></i></div><div class="cc-stat-label">Lessons</div><div class="cc-stat-value">{{ $data['dashboard']['lessonsCount'] }}</div></div>
            <div class="cc-stat"><div class="cc-stat-icon" style="border-color:#b45309;color:#b45309;"><i class="fa-solid fa-trophy"></i></div><div class="cc-stat-label">Lessons with a project</div><div class="cc-stat-value">{{ $data['dashboard']['projectsDefinedCount'] }}<span class="cc-stat-of">/{{ $data['dashboard']['lessonsCount'] }}</span></div></div>
            <div class="cc-stat"><div class="cc-stat-icon" style="border-color:#c026d3;color:#c026d3;"><i class="fa-solid fa-circle-question"></i></div><div class="cc-stat-label">Lessons with a quiz</div><div class="cc-stat-value">{{ $data['dashboard']['quizzesDefinedCount'] }}<span class="cc-stat-of">/{{ $data['dashboard']['lessonsCount'] }}</span></div></div>
        </div>

        @if (hasPermission('class_content_create'))
            <div class="card ot-card mb-16">
                <div class="card-body">
                    <details>
                        <summary class="cc-add-unit-toggle">+ New unit</summary>
                        <form action="{{ route('class-content-unit.store') }}" method="post" class="mt-3">
                            @csrf
                            @include('class-content.unit._fields', ['idSuffix' => 'new', 'options' => $data['options'], 'unit' => null])
                        </form>
                    </details>
                </div>
            </div>
        @endif

        @forelse ($data['dashboard']['groups'] as $group)
            <div class="mb-28">
                <div class="cc-unit-head">
                    <h3 class="cc-unit-title">Unit {{ $group['number'] }} — {{ $group['unit']->title }}</h3>
                    <span class="cc-unit-count">{{ $group['modules']->count() }} {{ \Illuminate\Support\Str::plural('module', $group['modules']->count()) }}</span>
                    @if (hasPermission('class_content_update') || hasPermission('class_content_delete'))
                        <details class="cc-unit-manage">
                            <summary>Manage unit</summary>
                            <div class="cc-unit-manage-body">
                                @if (hasPermission('class_content_update'))
                                    <form action="{{ route('class-content-unit.update', $group['unit']->id) }}" method="post">
                                        @csrf
                                        @method('PUT')
                                        @include('class-content.unit._fields', ['idSuffix' => 'u' . $group['unit']->id, 'options' => $data['options'], 'unit' => $group['unit']])
                                    </form>
                                @endif
                                @if (hasPermission('class_content_delete'))
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="delete_row('class-content-units/delete', {{ $group['unit']->id }})">Delete unit</button>
                                @endif
                            </div>
                        </details>
                    @endif
                </div>

                <details class="cc-collapse" open>
                    <summary class="cc-collapse-summary">{{ $group['modules']->isEmpty() ? 'No modules yet' : 'Show modules' }}</summary>
                    <div class="cc-collapse-body">
                        @foreach ($group['modules'] as $entry)
                            @include('class-content.module._dashboard-card', ['row' => $entry['module'], 'number' => $entry['number']])
                        @endforeach
                        @if (hasPermission('class_content_create'))
                            <a href="{{ route('class-content-module.create', ['unit_id' => $group['unit']->id, 'classes_id' => $group['unit']->classes_id, 'section_id' => $group['unit']->section_id, 'subject_id' => $group['unit']->subject_id]) }}" class="cc-add-link"><i class="fa-solid fa-plus"></i> Add a module to this unit</a>
                        @endif
                    </div>
                </details>
            </div>
        @empty
        @endforelse

        @if ($data['dashboard']['ungrouped']->isNotEmpty())
            <div class="mb-28">
                <div class="cc-unit-head">
                    <h3 class="cc-unit-title">Other modules</h3>
                    <span class="cc-unit-count">not in a unit</span>
                </div>
                @foreach ($data['dashboard']['ungrouped'] as $entry)
                    @include('class-content.module._dashboard-card', ['row' => $entry['module'], 'number' => $entry['number']])
                @endforeach
            </div>
        @endif

        @if ($data['dashboard']['unitsCount'] === 0 && $data['dashboard']['ungrouped']->isEmpty())
            <div class="card ot-card">
                <div class="card-body text-center gray-color">
                    <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                    <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('script')
    @include('backend.partials.delete-ajax')
@endpush

@push('css')
<style>
    .cc-stat-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; background: var(--ot-bg-table-card, #fff); border: 1px solid var(--ot-border-basic-table, #e4e7ec); border-radius: 16px; padding: 20px; }
    .cc-stat { display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center; }
    .cc-stat-icon { width: 46px; height: 46px; border-radius: 50%; border: 2px solid; display: flex; align-items: center; justify-content: center; font-size: 16px; }
    .cc-stat-label { font-size: 12px; color: var(--ot-text-secondary, #5b6472); }
    .cc-stat-value { font-size: 15px; font-weight: 700; }
    .cc-stat-of { color: var(--ot-text-secondary, #8b93a3); font-weight: 500; }

    .cc-add-unit-toggle { cursor: pointer; font-weight: 600; color: #0097b2; }

    .cc-unit-head { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding-left: 14px; border-left: 4px solid #5e17eb; margin-bottom: 12px; }
    .cc-unit-title { margin: 0; font-size: 18px; font-weight: 700; }
    .cc-unit-count { font-size: 13px; color: var(--ot-text-secondary, #8b93a3); }
    .cc-unit-manage { margin-left: auto; font-size: 12px; }
    .cc-unit-manage > summary { cursor: pointer; color: #5b6472; font-weight: 600; }
    .cc-unit-manage-body { margin-top: 10px; max-width: 520px; }

    .cc-collapse-summary { cursor: pointer; font-size: 12.5px; font-weight: 600; color: #5b6472; margin-bottom: 10px; }
    .cc-collapse[open] > .cc-collapse-summary { margin-bottom: 10px; }
    .cc-add-link { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #0097b2; margin-top: 4px; }
</style>
@endpush
