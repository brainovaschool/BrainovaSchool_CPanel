@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    @php
        $module = $data['module'];
        $lesson = $data['item'];
    @endphp
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $lesson->title }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('class-content-module.index') }}">Class Content</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('class-content-module.lessons', $module->id) }}">{{ $module->title }}</a></li>
                        <li class="breadcrumb-item active">{{ $lesson->title }}</li>
                    </ol>
                </div>
                @if (hasPermission('class_content_update') && in_array($module->review_status, [\App\Models\ClassContent\ClassContentModule::DRAFT, \App\Models\ClassContent\ClassContentModule::CHANGES_REQUESTED]))
                    <div class="col-sm-6 text-sm-end">
                        <a href="{{ route('class-content-module.lessons.edit', [$module->id, $lesson->id]) }}" class="btn btn-lg ot-btn-primary">
                            <i class="fa-solid fa-pen-to-square"></i> {{ ___('common.edit') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        @include('class-content._lesson-card', ['lesson' => $lesson])
    </div>
@endsection
