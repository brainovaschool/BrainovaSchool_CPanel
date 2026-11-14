@extends('student-panel.partials.master')

@section('title')
{{ $data['title'] }}
@endsection

@section('content')
<div class="page-content">
    <div class="sp-mc">
        <div class="sp-mc-card">
            <p class="sp-mc-label">My Course</p>
            @if ($data['programs']->isEmpty())
                <p class="text-secondary mb-0" style="font-size: 13px;">You're not enrolled in any course yet.</p>
            @else
                <form method="GET" class="sp-mc-select-form">
                    <select name="program" class="sp-mc-select" onchange="this.form.submit()">
                        @foreach ($data['programs'] as $opt)
                            <option value="{{ $opt->id }}" {{ $data['selectedProgram'] && (int) $data['selectedProgram']->id === (int) $opt->id ? 'selected' : '' }}>{{ $opt->title }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>

        @if ($data['selectedProgram'])
            @php
                $sp = $data['selectedProgram'];
                $pr = $data['progress'];
            @endphp

            <div class="sp-mc-cards">
                <div class="sp-mc-stat">
                    <p class="sp-mc-stat-label">Lessons</p>
                    <p class="sp-mc-stat-value">{{ $sp->programClasses->count() }}</p>
                </div>
                <div class="sp-mc-stat">
                    <p class="sp-mc-stat-label">Items Graded</p>
                    <p class="sp-mc-stat-value">{{ $pr['graded'] }}/{{ $pr['totalSlots'] }}</p>
                </div>
                <div class="sp-mc-stat">
                    <p class="sp-mc-stat-label">Your Average</p>
                    <p class="sp-mc-stat-value">{{ $pr['pct'] !== null ? $pr['pct'] . '%' : '—' }}</p>
                </div>
            </div>

            <div class="sp-mc-card">
                <div class="sp-mc-meta-grid">
                    @if ($sp->age_range)
                        <div class="sp-mc-meta-item"><p class="sp-mc-meta-label">Ages</p><p class="sp-mc-meta-value">{{ $sp->age_range }}</p></div>
                    @endif
                    @if ($sp->duration)
                        <div class="sp-mc-meta-item"><p class="sp-mc-meta-label">Duration</p><p class="sp-mc-meta-value">{{ $sp->duration }}</p></div>
                    @endif
                    @if ($sp->start_date)
                        <div class="sp-mc-meta-item"><p class="sp-mc-meta-label">Starts</p><p class="sp-mc-meta-value">{{ $sp->start_date->format('d M Y') }}</p></div>
                    @endif
                    @if ($sp->end_date)
                        <div class="sp-mc-meta-item"><p class="sp-mc-meta-label">Ends</p><p class="sp-mc-meta-value">{{ $sp->end_date->format('d M Y') }}</p></div>
                    @endif
                    @if ($sp->class_days)
                        <div class="sp-mc-meta-item"><p class="sp-mc-meta-label">Days</p><p class="sp-mc-meta-value">{{ $sp->class_days }}</p></div>
                    @endif
                    @if ($sp->class_time)
                        <div class="sp-mc-meta-item"><p class="sp-mc-meta-label">Time</p><p class="sp-mc-meta-value">{{ $sp->class_time }}</p></div>
                    @endif
                    @if ($sp->teachers->isNotEmpty())
                        <div class="sp-mc-meta-item"><p class="sp-mc-meta-label">Instructor</p><p class="sp-mc-meta-value">{{ $sp->teachers->map(fn ($t) => trim($t->first_name . ' ' . $t->last_name))->join(', ') }}</p></div>
                    @endif
                </div>
            </div>

            <div class="sp-mc-card">
                <p class="sp-mc-label">Lessons</p>

                @php $num = 0; @endphp
                @forelse ($sp->programClasses as $pc)
                    @php
                        $num++;
                        $row = $pr['lessonRows'][$pc->id] ?? null;
                    @endphp
                    <details class="sp-mc-lesson" {{ $num === 1 ? 'open' : '' }}>
                        <summary class="sp-mc-lesson-summary">
                            <span class="sp-mc-lesson-num">{{ $num }}</span>
                            <span class="sp-mc-lesson-title">{{ $pc->title }}</span>
                            <span class="sp-mc-lesson-chev">&#9662;</span>
                        </summary>
                        <div class="sp-mc-lesson-body">
                            @if ($pc->description)
                                <p class="sp-mc-desc">{{ $pc->description }}</p>
                            @endif
                            @if ($pc->video_url)
                                <p class="sp-mc-desc"><a href="{{ $pc->video_url }}" target="_blank" rel="noopener">Watch recap video &rarr;</a></p>
                            @endif

                            @if ($pc->materials->isNotEmpty())
                                <div class="sp-mc-sub">
                                    <span class="sp-mc-pill sp-mc-pill--resources">Resources</span>
                                    <ul class="sp-mc-list">
                                        @foreach ($pc->materials as $m)
                                            <li><a href="{{ $m->downloadUrl() }}" target="_blank" rel="noopener">{{ $m->label }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($pc->hasQuiz())
                                @php $quizMark = $row['items']['quiz']['marks'] ?? null; @endphp
                                <div class="sp-mc-sub">
                                    <span class="sp-mc-pill sp-mc-pill--quiz">Quiz</span>
                                    <p class="sp-mc-sub-title">{{ $pc->quiz_title }}</p>
                                    @if ($pc->quiz_description)
                                        <p class="sp-mc-desc">{{ $pc->quiz_description }}</p>
                                    @endif
                                    @if ($pc->quiz_link_url)
                                        <p class="sp-mc-desc"><a href="{{ $pc->quiz_link_url }}" target="_blank" rel="noopener">Open quiz &rarr;</a></p>
                                    @endif
                                    <p class="sp-mc-score">Your score:
                                        @if ($quizMark !== null) {{ $quizMark }}{{ $pc->quiz_max_marks ? ' / ' . $pc->quiz_max_marks : '' }}
                                        @else <span class="text-secondary">Not graded yet</span>
                                        @endif
                                    </p>
                                </div>
                            @endif

                            @if ($pc->hasProject())
                                @php $projectMark = $row['items']['project']['marks'] ?? null; @endphp
                                <div class="sp-mc-sub">
                                    <span class="sp-mc-pill sp-mc-pill--project">Project</span>
                                    <p class="sp-mc-sub-title">{{ $pc->project_title }}</p>
                                    @if ($pc->project_description)
                                        <p class="sp-mc-desc">{{ $pc->project_description }}</p>
                                    @endif
                                    @if ($pc->project_link_url)
                                        <p class="sp-mc-desc"><a href="{{ $pc->project_link_url }}" target="_blank" rel="noopener">Open project &rarr;</a></p>
                                    @endif
                                    <p class="sp-mc-score">Your score:
                                        @if ($projectMark !== null) {{ $projectMark }}{{ $pc->project_max_marks ? ' / ' . $pc->project_max_marks : '' }}
                                        @else <span class="text-secondary">Not graded yet</span>
                                        @endif
                                    </p>
                                </div>
                            @endif

                            @if ($pc->hasWorksheet())
                                @php $worksheetMark = $row['items']['worksheet']['marks'] ?? null; @endphp
                                <div class="sp-mc-sub">
                                    <span class="sp-mc-pill sp-mc-pill--worksheet">Worksheet</span>
                                    <p class="sp-mc-sub-title">{{ $pc->worksheet_title }}</p>
                                    @if ($pc->worksheet_description)
                                        <p class="sp-mc-desc">{{ $pc->worksheet_description }}</p>
                                    @endif
                                    @if ($pc->worksheet_link_url)
                                        <p class="sp-mc-desc"><a href="{{ $pc->worksheet_link_url }}" target="_blank" rel="noopener">Open worksheet &rarr;</a></p>
                                    @endif
                                    <p class="sp-mc-score">Your score:
                                        @if ($worksheetMark !== null) {{ $worksheetMark }}{{ $pc->worksheet_max_marks ? ' / ' . $pc->worksheet_max_marks : '' }}
                                        @else <span class="text-secondary">Not graded yet</span>
                                        @endif
                                    </p>
                                </div>
                            @endif

                            @if ($pc->hasAssignment())
                                @php $assignmentMark = $row['items']['assignment']['marks'] ?? null; @endphp
                                <div class="sp-mc-sub">
                                    <span class="sp-mc-pill sp-mc-pill--assignment">Assignment</span>
                                    <p class="sp-mc-sub-title">{{ $pc->assignment_title }}</p>
                                    @if ($pc->assignment_description)
                                        <p class="sp-mc-desc">{{ $pc->assignment_description }}</p>
                                    @endif
                                    @if ($pc->assignment_link_url)
                                        <p class="sp-mc-desc"><a href="{{ $pc->assignment_link_url }}" target="_blank" rel="noopener">Open assignment &rarr;</a></p>
                                    @endif
                                    <p class="sp-mc-score">Your score:
                                        @if ($assignmentMark !== null) {{ $assignmentMark }}{{ $pc->assignment_max_marks ? ' / ' . $pc->assignment_max_marks : '' }}
                                        @else <span class="text-secondary">Not graded yet</span>
                                        @endif
                                    </p>
                                </div>
                            @endif

                            @if ($pc->activities->isNotEmpty())
                                <div class="sp-mc-sub">
                                    <span class="sp-mc-pill sp-mc-pill--activities">Activities</span>
                                    <ul class="sp-mc-list">
                                        @foreach ($pc->activities as $a)
                                            <li>
                                                <strong>{{ $a->title }}.</strong> {{ $a->description }}
                                                @if ($a->link_url)
                                                    <a href="{{ $a->link_url }}" target="_blank" rel="noopener">Open &rarr;</a>
                                                @endif
                                                @if ($a->video_url)
                                                    <a href="{{ $a->video_url }}" target="_blank" rel="noopener">Watch video &rarr;</a>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($pc->outcomes->isNotEmpty())
                                <div class="sp-mc-sub">
                                    <span class="sp-mc-pill sp-mc-pill--outcomes">Learning outcomes</span>
                                    <ul class="sp-mc-list">
                                        @foreach ($pc->outcomes as $o)
                                            <li>{{ $o->outcome_text }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </details>
                @empty
                    <p class="text-secondary mb-0" style="font-size: 13px;">No lessons published for this course yet.</p>
                @endforelse
            </div>
        @endif
    </div>
</div>
@endsection

@push('css')
<style>
    .sp-mc-card {
        background: #ffffff;
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        border-radius: 20px;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1rem;
    }
    .sp-mc-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #8b93a3; margin: 0 0 0.6rem; }
    .sp-mc-select {
        appearance: none; -webkit-appearance: none;
        width: 100%; max-width: 420px;
        font: inherit; font-size: 0.95rem; font-weight: 600; color: #0f172a;
        background: #f8f9fb url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='9' viewBox='0 0 14 9'%3E%3Cpath d='M1 1l6 6 6-6' stroke='%235e17eb' stroke-width='2' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") no-repeat right 1rem center;
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        border-radius: 12px;
        padding: 0.7rem 2.5rem 0.7rem 1rem;
        cursor: pointer;
    }
    .sp-mc-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
    .sp-mc-stat { background: #ffffff; border: 1px solid var(--ot-border-basic-table, #e4e7ec); border-radius: 16px; padding: 1rem 1.1rem; }
    .sp-mc-stat-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #8b93a3; margin: 0 0 0.35rem; }
    .sp-mc-stat-value { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
    .sp-mc-meta-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1rem 1.25rem; }
    .sp-mc-meta-label { font-size: 0.72rem; color: #8b93a3; margin: 0 0 0.15rem; }
    .sp-mc-meta-value { font-size: 0.95rem; font-weight: 700; color: #0f172a; margin: 0; }
    .sp-mc-lesson {
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        border-radius: 14px; margin-bottom: 0.65rem; overflow: hidden;
    }
    .sp-mc-lesson:last-child { margin-bottom: 0; }
    .sp-mc-lesson-summary { display: flex; align-items: center; gap: 0.75rem; padding: 0.8rem 1rem; cursor: pointer; list-style: none; font-size: 0.9rem; font-weight: 600; color: #0f172a; }
    .sp-mc-lesson-summary::-webkit-details-marker { display: none; }
    .sp-mc-lesson-num {
        width: 26px; height: 26px; border-radius: 50%;
        background: #ece4fd; color: #5e17eb;
        font-size: 0.76rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .sp-mc-lesson-title { flex: 1; min-width: 0; }
    .sp-mc-lesson-chev { color: #8b93a3; transition: transform .15s ease; flex-shrink: 0; }
    details[open] .sp-mc-lesson-chev { transform: rotate(180deg); }
    .sp-mc-lesson-body { padding: 0 1rem 1rem 3.25rem; border-top: 1px solid var(--ot-border-basic-table, #e4e7ec); }
    .sp-mc-sub { margin-top: 0.9rem; }
    .sp-mc-pill {
        display: inline-block;
        font-size: 0.62rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em;
        padding: 0.2rem 0.55rem; border-radius: 999px; margin-bottom: 0.55rem;
    }
    .sp-mc-pill--resources { background: #ccfbf1; color: #0f766e; }
    .sp-mc-pill--activities { background: #ede9fe; color: #5b21b6; }
    .sp-mc-pill--outcomes { background: #e0e7ff; color: #4338ca; }
    .sp-mc-pill--quiz { background: #dbeafe; color: #1d4ed8; }
    .sp-mc-pill--project { background: #fce7f3; color: #9d174d; }
    .sp-mc-pill--worksheet { background: #ccfbf1; color: #0d9488; }
    .sp-mc-pill--assignment { background: #dbeafe; color: #2563eb; }
    .sp-mc-sub-title { display: block; font-weight: 700; color: #0f172a; margin: 0 0 0.3rem; }
    .sp-mc-desc { font-size: 0.85rem; color: #475569; line-height: 1.6; margin: 0 0 0.5rem; }
    .sp-mc-score { font-size: 0.85rem; font-weight: 700; color: #0f172a; margin: 0.4rem 0 0; }
    .sp-mc-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.4rem; }
</style>
@endpush
