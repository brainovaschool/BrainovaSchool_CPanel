@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    @include('backend.partials.learning-engine-styles')
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
                <div class="col-sm-6 d-flex align-items-start justify-content-sm-end">
                    <a href="{{ route('skill-mastery-report.guide') }}" class="btn btn-outline-primary">
                        <i class="fa-solid fa-circle-question"></i> How to read this report
                    </a>
                </div>
            </div>
        </div>

        <div class="card ot-card mb-24">
            <div class="card-body">
                <form method="get" action="{{ route('skill-mastery-report.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">{{ ___('common.class') }}</label>
                            <select class="form-control ot-input" name="classes_id" onchange="this.form.submit()">
                                <option value="">{{ ___('common.select') }}</option>
                                @foreach ($data['classes'] as $class)
                                    <option value="{{ $class->id }}" {{ $data['selected_class'] == $class->id ? 'selected' : '' }}>
                                        {{ $class->class_tran }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ ___('common.section') }} ({{ ___('common.optional') }})</label>
                            <select class="form-control ot-input" name="section_id" onchange="this.form.submit()">
                                <option value="">{{ ___('common.all') }}</option>
                                @foreach ($data['sections'] as $section)
                                    <option value="{{ $section->id }}" {{ $data['selected_section'] == $section->id ? 'selected' : '' }}>
                                        {{ $section->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('common.Filtering') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if ($data['snapshot'] && dashboard_feature_enabled('teacher', 'skill_mastery_report'))
            <div class="table-content table-basic">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">{{ ___('common.skill_mastery_overview') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered class-table">
                                <thead class="thead">
                                    <tr>
                                        <th>{{ ___('common.sr_no') }}</th>
                                        <th>{{ ___('student_info.student_name') }}</th>
                                        <th>{{ ___('common.not_started') }}</th>
                                        <th>{{ ___('common.developing') }}</th>
                                        <th>{{ ___('common.proficient') }}</th>
                                        <th>{{ ___('common.advanced') }}</th>
                                        <th>{{ ___('common.could_use_another_look') }}</th>
                                        @if (dashboard_feature_enabled('teacher', 'struggle_flag'))
                                            <th>{{ ___('common.possible_struggle') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="tbody">
                                    @forelse ($data['snapshot']['rows'] as $key => $row)
                                        <tr>
                                            <td>{{ ++$key }}</td>
                                            <td>{{ $row['student']->first_name }} {{ $row['student']->last_name }}</td>
                                            <td><span class="bn-pill bn-pill--not-started">{{ $row['not_started'] }}</span></td>
                                            <td><span class="bn-pill bn-pill--developing">{{ $row['developing'] }}</span></td>
                                            <td><span class="bn-pill bn-pill--proficient">{{ $row['proficient'] }}</span></td>
                                            <td><span class="bn-pill bn-pill--advanced">{{ $row['advanced'] }}</span></td>
                                            <td>
                                                @if ($row['needs_review'] > 0)
                                                    <span class="bn-pill bn-pill--review">{{ $row['needs_review'] }}</span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            @if (dashboard_feature_enabled('teacher', 'struggle_flag'))
                                                <td>
                                                    @if ($row['struggling'] > 0)
                                                        <span class="bn-pill" style="background:var(--bn-accent-soft);color:var(--bn-accent-strong);">{{ $row['struggling'] }}</span>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="100%" class="text-center gray-color">
                                                <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <p class="gray-color mb-0 mt-2">{{ ___('settings.skills_defined_for_this_class_note') }}: {{ $data['snapshot']['total_skills'] }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
