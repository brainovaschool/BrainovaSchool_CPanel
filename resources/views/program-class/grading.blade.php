@extends('backend.master')
@section('title') {{ @$data['title'] }} @endsection

@push('css')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ global_asset('backend/assets/css/homework-teacher-gamified.css') }}">
@endpush

@section('content')
<div class="page-content homework-index-page hw-gamified">

    <div class="hw-game-hero mt-20">
        <div class="hw-game-hero-inner row align-items-start g-3">
            <div class="col-lg">
                <p class="hw-game-eyebrow">Mission control</p>
                <h1 class="hw-game-title">{{ @$data['title'] }}</h1>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="hw-game-badge"><i class="fa-solid fa-trophy"></i> Level up your courses</span>
                </div>
                <p class="hw-game-lead">
                    Pick a course (optional), then check who's submitted their quiz, project, worksheet, or assignment and grade it.
                </p>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ @$data['title'] }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="table-content table-basic mt-20">
        <div class="card ot-card hw-mission-panel">
            <div class="card-header">
                <span class="hw-section-chip"><i class="fa-solid fa-crosshairs"></i> Stage 1</span>
                <h4 class="hw-mission-title mb-0">{{ ___('common.filter') }}</h4>
                <p class="hw-mission-desc">Narrow it down to one course, or leave it on "All courses" to see everything that needs grading.</p>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-8 col-md-6 col-xl-4">
                        <label class="filter-label" for="pcg-program">Course</label>
                        <select id="pcg-program" name="program" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All courses</option>
                            @foreach ($data['programs'] as $p)
                                <option value="{{ $p->id }}" {{ (int) $data['selectedProgramId'] === (int) $p->id ? 'selected' : '' }}>{{ $p->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4 col-md-6 col-xl-8 d-flex gap-2 justify-content-end">
                        <button type="submit" class="btn ot-btn-primary hw-btn-deploy"><i class="fa-solid fa-rocket me-1"></i>Deploy</button>
                        @if ($data['selectedProgramId'])
                            <a href="{{ route('program-class-grading.index') }}" class="btn btn-outline-secondary hw-btn-reset" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="table-content table-basic hw-quest-log-wrap mt-20">
        <div class="card ot-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="w-100">
                    <span class="hw-section-chip"><i class="fa-solid fa-scroll"></i> Stage 2</span>
                    <h4 class="mb-0">{{ @$data['title'] }} &mdash; quest log</h4>
                    <div class="hw-eval-column mt-2 w-100">
                        <div class="row g-2 hw-eval-stats">
                            <div class="col-6 col-md-4">
                                <div class="hw-eval-stat hw-eval-stat--done">
                                    <span class="hw-eval-stat__icon"><i class="fa-solid fa-circle-check"></i></span>
                                    <div><span class="hw-eval-stat__value">{{ $data['stats']['graded'] }}</span>
                                    <span class="hw-eval-stat__label">Already graded</span></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="hw-eval-stat hw-eval-stat--urgent">
                                    <span class="hw-eval-stat__icon"><i class="fa-solid fa-pen-to-square"></i></span>
                                    <div><span class="hw-eval-stat__value">{{ $data['stats']['needMarks'] }}</span>
                                    <span class="hw-eval-stat__label">Need your marks</span></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="hw-eval-stat {{ $data['stats']['noHandIns'] > 0 ? 'hw-eval-stat--waiting' : 'hw-eval-stat--done' }}">
                                    <span class="hw-eval-stat__icon"><i class="fa-solid fa-inbox"></i></span>
                                    <div><span class="hw-eval-stat__value">{{ $data['stats']['noHandIns'] }}</span>
                                    <span class="hw-eval-stat__label">Haven't submitted</span></div>
                                </div>
                            </div>
                        </div>
                        <p class="hw-eval-legend small mb-0 mt-2">
                            <strong>Need your marks</strong> = students who already handed in work. <strong>Haven&rsquo;t submitted</strong> = still waiting on the student. Use <strong>Grade</strong> to mark work.
                        </p>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered religion-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Lesson</th>
                            <th>Course</th>
                            <th>Gradable items</th>
                            <th>Date</th>
                            <th>Submissions</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['rows'] as $row)
                            @php $pc = $row['class']; @endphp
                            <tr @if ($row['needMarks'] > 0) class="hw-row-needs-marking" @endif>
                                <td class="serial">{{ $loop->iteration }}</td>
                                <td>
                                    <strong>{{ $pc->title }}</strong>
                                    @if ($row['needMarks'] > 0)
                                        <span class="hw-awaiting-pill" title="{{ $row['needMarks'] }} submission(s) need marks">{{ $row['needMarks'] }} to grade</span>
                                    @endif
                                </td>
                                <td>{{ $pc->program->title }}</td>
                                <td>
                                    @foreach ($row['items'] as $type => $item)
                                        <span class="pcg-pill pcg-pill--{{ $type }}">{{ $item['title'] }}</span>
                                    @endforeach
                                </td>
                                <td>{{ $pc->class_date ? \Carbon\Carbon::parse($pc->class_date)->format('d M Y') : '—' }}</td>
                                <td class="hw-submission-cell">
                                    @if ($row['graded'] === 0 && $row['needMarks'] === 0)
                                        <span class="hw-sub-mini hw-sub-mini--none"><i class="fa-solid fa-inbox"></i> None yet</span>
                                    @else
                                        <div class="hw-sub-mini-stack">
                                            @if ($row['needMarks'] > 0)
                                                <span class="hw-sub-mini hw-sub-mini--wait"><i class="fa-solid fa-hourglass-half"></i> {{ $row['needMarks'] }} waiting</span>
                                            @endif
                                            @if ($row['graded'] > 0)
                                                <span class="hw-sub-mini hw-sub-mini--done"><i class="fa-solid fa-check"></i> {{ $row['graded'] }} done</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="action text-end">
                                    <a href="{{ route('program-class.grade.edit', $pc->id) }}"
                                       class="btn btn-sm {{ $row['needMarks'] > 0 ? 'hw-btn-grade hw-btn-grade--pending' : 'btn-outline-secondary hw-btn-grade' }}"
                                       title="{{ $row['needMarks'] > 0 ? 'Grade ' . $row['needMarks'] . ' submission(s)' : 'View or grade' }}">
                                        <i class="fa-solid fa-pen-to-square me-1"></i>Grade
                                        @if ($row['needMarks'] > 0)
                                            <span class="hw-grade-count">{{ $row['needMarks'] }}</span>
                                        @endif
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-inbox d-block mb-2 fs-3 opacity-25"></i>
                                    No gradable lessons found{{ $data['selectedProgramId'] ? ' for this course' : '' }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
    .pcg-pill { display: inline-block; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; padding: 0.15rem 0.5rem; border-radius: 999px; margin: 0 0.25rem 0.25rem 0; }
    .pcg-pill--quiz { background: #dbeafe; color: #1d4ed8; }
    .pcg-pill--project { background: #fce7f3; color: #9d174d; }
    .pcg-pill--worksheet { background: #ccfbf1; color: #0d9488; }
    .pcg-pill--assignment { background: #dbeafe; color: #2563eb; }
</style>
@endpush
