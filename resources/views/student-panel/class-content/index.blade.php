@extends('student-panel.partials.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    @php
        $map = $data['map'];
        $allModules = $data['dashboard']['groups']->flatMap(fn ($g) => $g['modules'])->concat($data['dashboard']['ungrouped'])->pluck('module');
        $allLessons = $allModules->flatMap(fn ($m) => $m->lessons);
        $lessonsDone  = $allLessons->filter(fn ($l) => $l->isDoneFor($map))->count();
        $withProject  = $allLessons->filter(fn ($l) => $l->hasProject());
        $withQuiz     = $allLessons->filter(fn ($l) => $l->hasQuiz());
        $projectsDone = $withProject->filter(fn ($l) => isset($map['project:' . $l->id]))->count();
        $quizzesDone  = $withQuiz->filter(fn ($l) => isset($map['quiz:' . $l->id]))->count();
    @endphp
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

        <p class="text-secondary mb-3">Your big picture: units, modules and lessons your teacher has shared, with Activities, Project and Quiz tracked per lesson.</p>

        <div class="cc-stat-row mb-24">
            <div class="cc-stat"><div class="cc-stat-icon" style="border-color:#5e17eb;color:#5e17eb;"><i class="fa-solid fa-layer-group"></i></div><div class="cc-stat-label">Units</div><div class="cc-stat-value">{{ $data['dashboard']['unitsCount'] }}</div></div>
            <div class="cc-stat"><div class="cc-stat-icon" style="border-color:#0097b2;color:#0097b2;"><i class="fa-solid fa-table-cells-large"></i></div><div class="cc-stat-label">Modules</div><div class="cc-stat-value">{{ $data['dashboard']['modulesCount'] }}</div></div>
            <div class="cc-stat"><div class="cc-stat-icon" style="border-color:#16a34a;color:#16a34a;"><i class="fa-solid fa-book-open"></i></div><div class="cc-stat-label">Lessons</div><div class="cc-stat-value">{{ $lessonsDone }}<span class="cc-stat-of">/{{ $data['dashboard']['lessonsCount'] }}</span></div></div>
            <div class="cc-stat"><div class="cc-stat-icon" style="border-color:#b45309;color:#b45309;"><i class="fa-solid fa-trophy"></i></div><div class="cc-stat-label">Projects</div><div class="cc-stat-value">{{ $projectsDone }}<span class="cc-stat-of">/{{ $withProject->count() }}</span></div></div>
            <div class="cc-stat"><div class="cc-stat-icon" style="border-color:#c026d3;color:#c026d3;"><i class="fa-solid fa-circle-question"></i></div><div class="cc-stat-label">Quizzes</div><div class="cc-stat-value">{{ $quizzesDone }}<span class="cc-stat-of">/{{ $withQuiz->count() }}</span></div></div>
        </div>

        @if ($data['dashboard']['unitsCount'] === 0 && $data['dashboard']['ungrouped']->isEmpty())
            <div class="card ot-card">
                <div class="card-body text-center">
                    <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                    <p class="mb-0">No class content has been shared yet.</p>
                </div>
            </div>
        @else
            @foreach ($data['dashboard']['groups'] as $group)
                <div class="mb-28">
                    <div class="cc-unit-head">
                        <h3 class="cc-unit-title">Unit {{ $group['number'] }} — {{ $group['unit']->title }}</h3>
                        <span class="cc-unit-count">{{ $group['modules']->count() }} {{ \Illuminate\Support\Str::plural('module', $group['modules']->count()) }}</span>
                    </div>
                    @foreach ($group['modules'] as $entry)
                        @include('student-panel.class-content._module-card', ['module' => $entry['module'], 'number' => $entry['number'], 'map' => $map])
                    @endforeach
                </div>
            @endforeach

            @if ($data['dashboard']['ungrouped']->isNotEmpty())
                <div class="mb-28">
                    @foreach ($data['dashboard']['ungrouped'] as $entry)
                        @include('student-panel.class-content._module-card', ['module' => $entry['module'], 'number' => $entry['number'], 'map' => $map])
                    @endforeach
                </div>
            @endif
        @endif
    </div>
@endsection

@push('css')
<style>
    .cc-stat-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; background: #fff; border: 1px solid #e4e7ec; border-radius: 16px; padding: 20px; }
    .cc-stat { display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center; }
    .cc-stat-icon { width: 46px; height: 46px; border-radius: 50%; border: 2px solid; display: flex; align-items: center; justify-content: center; font-size: 16px; }
    .cc-stat-label { font-size: 12px; color: #5b6472; }
    .cc-stat-value { font-size: 15px; font-weight: 700; }
    .cc-stat-of { color: #8b93a3; font-weight: 500; }

    .cc-unit-head { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding-left: 14px; border-left: 4px solid #5e17eb; margin-bottom: 12px; }
    .cc-unit-title { margin: 0; font-size: 18px; font-weight: 700; }
    .cc-unit-count { font-size: 13px; color: #8b93a3; }
</style>
@endpush
