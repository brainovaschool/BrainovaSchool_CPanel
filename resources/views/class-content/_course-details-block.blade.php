{{--
    Shared by all 4 category Overview pages' Course Details section.
    Expects: $courseOptions (Program collection, id+title, for the
    dropdown), $selectedProgram (Program model or null, with teachers
    eager loaded), $contentModules (ClassContentModule collection with
    lessons.materials/activities/outcomes/homeworks eager loaded),
    $homeworkStats (array keyed by homework id -> submitted/awaiting_marks/
    graded, from HomeworkRepository::getSubmissionStatsForHomeworks()).

    Reuses the real Homework tab's own classes (.hw-type-pill, and - inside
    a .hw-gamified wrapper - .hw-sub-mini*/.hw-btn-grade*) rather than
    recreating them, so this looks like "more of the Homework tab", not a
    reinterpretation of it. Loads homework-teacher-gamified.css itself via
    @once so each parent view doesn't have to remember to.
--}}
@once
    @push('css')
    <link rel="stylesheet" href="{{ global_asset('backend/assets/css/homework-teacher-gamified.css') }}">
    @endpush
@endonce

@php
    $cd_options  = $courseOptions ?? collect();
    $cd_selected = $selectedProgram ?? null;
    $cd_modules  = $contentModules ?? collect();
    $cd_stats    = $homeworkStats ?? [];
    $cd_typeKey  = ['homework' => 'hw', 'quiz' => 'quiz', 'project' => 'project', 'activity' => 'activity', 'game' => 'game', 'assignment' => 'assignment'];
@endphp

<div class="cc-cd">
    <div class="cc-cd-card">
        <p class="cc-cd-label">Course</p>
        @if ($cd_options->isEmpty())
            <p class="text-secondary mb-0" style="font-size: 13px;">No courses in this category yet.</p>
        @else
            <form method="GET" class="cc-cd-select-form">
                <select name="program" class="nice-select niceSelect bordered_style" onchange="this.form.submit()">
                    @foreach ($cd_options as $opt)
                        <option value="{{ $opt->id }}" {{ $cd_selected && (int) $cd_selected->id === (int) $opt->id ? 'selected' : '' }}>{{ $opt->title }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if ($cd_selected)
        <div class="cc-cd-card">
            <div class="cc-cd-meta-grid">
                @if ($cd_selected->age_range)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Ages</p><p class="cc-cd-meta-value">{{ $cd_selected->age_range }}</p></div>
                @endif
                @if ($cd_selected->duration)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Duration</p><p class="cc-cd-meta-value">{{ $cd_selected->duration }}</p></div>
                @endif
                @if ($cd_selected->lessons)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Classes</p><p class="cc-cd-meta-value">{{ $cd_selected->lessons }}</p></div>
                @endif
                @if ($cd_selected->teachers->isNotEmpty())
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Instructor</p><p class="cc-cd-meta-value">{{ $cd_selected->teachers->map(fn ($t) => trim($t->first_name . ' ' . $t->last_name))->join(', ') }}</p></div>
                @endif
                @if ($cd_selected->status_label_text)
                    <div class="cc-cd-meta-item">
                        <p class="cc-cd-meta-label">Status</p>
                        <p class="cc-cd-meta-value"><span class="cc-cd-status-pill cc-cd-status-pill--{{ $cd_selected->status_label }}">{{ $cd_selected->status_label_text }}</span></p>
                    </div>
                @endif
            </div>
        </div>

        @if ($cd_selected->description || !empty($cd_selected->overview) || !empty($cd_selected->highlights))
            <div class="cc-cd-card">
                <p class="cc-cd-label">Course overview</p>
                @if ($cd_selected->description)
                    <p class="cc-cd-desc">{{ $cd_selected->description }}</p>
                @endif
                @foreach (($cd_selected->overview ?? []) as $para)
                    <p class="cc-cd-desc">{{ $para }}</p>
                @endforeach
                @if (!empty($cd_selected->highlights))
                    <ul class="cc-cd-highlights">
                        @foreach ($cd_selected->highlights as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <div class="cc-cd-card">
            <div class="cc-cd-head-row">
                <p class="cc-cd-label mb-0">Classes in this course</p>
                @if (hasPermission('program_update'))
                    <a class="cc-cd-manage-link" href="{{ route('program.edit', $cd_selected->id) }}">Manage in Website Setup <i class="fa-solid fa-arrow-right"></i></a>
                @endif
            </div>

            @if (!$cd_selected->isLinkedToClassContent())
                <div class="cc-cd-empty">
                    <p class="mb-1"><strong>{{ $cd_selected->title }}</strong> isn't linked to Class Content yet.</p>
                    @if (hasPermission('program_update'))
                        <p class="text-secondary mb-0" style="font-size: 13px;">
                            Open this program in <a href="{{ route('program.edit', $cd_selected->id) }}">Website Setup &rarr; Course Catalogue &rarr; Program</a>
                            and set "Link to Class Content" (Class / Section / Subject) to show its real classes, materials and homework here.
                        </p>
                    @else
                        <p class="text-secondary mb-0" style="font-size: 13px;">Ask an admin to link this program to its Class Content (Class / Section / Subject) in Website Setup.</p>
                    @endif
                </div>
            @elseif ($cd_modules->isEmpty())
                <p class="text-secondary mb-0" style="font-size: 13px;">Linked, but there's no approved Class Content module yet for this class, section and subject.</p>
            @else
                @foreach ($cd_modules as $module)
                    @foreach ($module->lessons as $lesson)
                        <details class="cc-cd-lesson" {{ $loop->parent->first && $loop->first ? 'open' : '' }}>
                            <summary class="cc-cd-lesson-summary">
                                <span class="cc-cd-lesson-num">{{ $loop->parent->iteration }}.{{ $loop->iteration }}</span>
                                <span class="cc-cd-lesson-title">
                                    {{ $lesson->title }}
                                    <small>{{ $module->title }}</small>
                                </span>
                                <span class="cc-cd-lesson-chev">&#9662;</span>
                            </summary>
                            <div class="cc-cd-lesson-body">
                                @if ($lesson->description)
                                    <p class="cc-cd-desc cc-cd-desc--sm">{{ $lesson->description }}</p>
                                @endif

                                @if ($lesson->materials->isNotEmpty())
                                    <div class="cc-cd-sub">
                                        <h5>Resources</h5>
                                        <ul class="cc-cd-res-list">
                                            @foreach ($lesson->materials as $m)
                                                <li>
                                                    <span class="cc-cd-ico">{{ $m->storage_key ? '📄' : '🔗' }}</span>
                                                    <a href="{{ $m->downloadUrl() }}" target="_blank" rel="noopener">{{ $m->label }}</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if ($lesson->hasQuiz())
                                    <div class="cc-cd-sub">
                                        <h5>Quiz (self check)</h5>
                                        <div class="cc-cd-pill-row"><span class="hw-type-pill hw-type-pill--quiz">Quiz</span><span class="cc-cd-hwtitle">{{ $lesson->quiz_title }}</span></div>
                                        @if ($lesson->quiz_description)
                                            <p class="cc-cd-desc cc-cd-desc--sm">{{ $lesson->quiz_description }}</p>
                                        @endif
                                    </div>
                                @endif

                                @if ($lesson->hasProject())
                                    <div class="cc-cd-sub">
                                        <h5>Project</h5>
                                        <div class="cc-cd-pill-row"><span class="hw-type-pill hw-type-pill--project">Project</span><span class="cc-cd-hwtitle">{{ $lesson->project_title }}</span></div>
                                        @if ($lesson->project_description)
                                            <p class="cc-cd-desc cc-cd-desc--sm">{{ $lesson->project_description }}</p>
                                        @endif
                                    </div>
                                @endif

                                @if ($lesson->activities->isNotEmpty())
                                    <div class="cc-cd-sub">
                                        <h5>Activities</h5>
                                        <ul class="cc-cd-out-list">
                                            @foreach ($lesson->activities as $a)
                                                <li><strong>{{ $a->title }}.</strong> {{ $a->description }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if ($lesson->outcomes->isNotEmpty())
                                    <div class="cc-cd-sub">
                                        <h5>Learning outcomes</h5>
                                        <ul class="cc-cd-out-list">
                                            @foreach ($lesson->outcomes as $o)
                                                <li>{{ $o->outcome_text }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="cc-cd-sub hw-gamified">
                                    <h5>Homework &amp; assignments</h5>
                                    @if ($lesson->homeworks->isEmpty())
                                        <p class="cc-cd-empty-sub">No homework task linked to this class yet.</p>
                                    @else
                                        <div class="cc-cd-hw-wrap">
                                            @foreach ($lesson->homeworks as $hw)
                                                @php
                                                    $stats = $cd_stats[$hw->id] ?? ['submitted' => 0, 'awaiting_marks' => 0, 'graded' => 0];
                                                    $key = $cd_typeKey[$hw->task_type ?? 'homework'] ?? 'hw';
                                                @endphp
                                                <div class="cc-cd-hwrow">
                                                    <span class="hw-type-pill hw-type-pill--{{ $key }}">{{ $hw->task_type }}</span>
                                                    <span class="cc-cd-hwtitle">{{ $hw->title }}</span>
                                                    @if ($hw->submission_date)
                                                        <span class="cc-cd-hwdue">Due {{ \Carbon\Carbon::parse($hw->submission_date)->format('d M') }}</span>
                                                    @endif
                                                    @if ((int) $stats['submitted'] === 0)
                                                        <span class="hw-sub-mini hw-sub-mini--none"><i class="fa-solid fa-inbox"></i> None yet</span>
                                                    @else
                                                        <div class="hw-sub-mini-stack">
                                                            <span class="hw-sub-mini hw-sub-mini--in"><i class="fa-solid fa-paper-plane"></i> {{ $stats['submitted'] }} in</span>
                                                            @if ($stats['awaiting_marks'] > 0)
                                                                <span class="hw-sub-mini hw-sub-mini--wait"><i class="fa-solid fa-hourglass-half"></i> {{ $stats['awaiting_marks'] }} waiting</span>
                                                            @endif
                                                            @if ($stats['graded'] > 0)
                                                                <span class="hw-sub-mini hw-sub-mini--done"><i class="fa-solid fa-check"></i> {{ $stats['graded'] }} done</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                    @if (hasPermission('homework_update'))
                                                        <a href="{{ route('homework.edit', $hw->id) }}"
                                                           class="btn btn-sm {{ $stats['awaiting_marks'] > 0 ? 'hw-btn-grade hw-btn-grade--pending' : 'btn-outline-secondary hw-btn-grade' }}">
                                                            {{ $stats['awaiting_marks'] > 0 ? 'Grade ' . $stats['awaiting_marks'] : 'View' }}
                                                        </a>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </details>
                    @endforeach
                @endforeach
            @endif
        </div>
    @endif
</div>

@once
    @push('css')
    <style>
        .cc-cd-card {
            background: #ffffff;
            border: 1px solid var(--ot-border-basic-table, #e4e7ec);
            border-radius: 20px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
        }
        .cc-cd-card:last-child { margin-bottom: 0; }
        .cc-cd-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #8b93a3; margin: 0 0 0.6rem; }
        .cc-cd-select-form select { max-width: 420px; }
        .cc-cd-meta-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1rem 1.25rem; }
        .cc-cd-meta-label { font-size: 0.72rem; color: #8b93a3; margin: 0 0 0.15rem; }
        .cc-cd-meta-value { font-size: 0.95rem; font-weight: 700; color: #0f172a; margin: 0; }
        .cc-cd-status-pill { display: inline-block; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.65rem; border-radius: 999px; }
        .cc-cd-status-pill--ongoing { background: #dcfce7; color: #15803d; }
        .cc-cd-status-pill--upcoming { background: #dbeafe; color: #1d4ed8; }
        .cc-cd-status-pill--coming_soon { background: #f1f5f9; color: #64748b; }
        .cc-cd-status-pill--enrolling { background: #ece4fd; color: #5e17eb; }
        .cc-cd-desc { font-size: 0.9rem; color: #475569; line-height: 1.6; margin: 0 0 0.75rem; }
        .cc-cd-desc--sm { font-size: 0.85rem; margin: 0.6rem 0; }
        .cc-cd-desc:last-child { margin-bottom: 0; }
        .cc-cd-highlights { margin: 0; padding-left: 1.1rem; }
        .cc-cd-highlights li { font-size: 0.88rem; color: #475569; line-height: 1.6; }
        .cc-cd-head-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.9rem; }
        .cc-cd-manage-link { font-size: 0.82rem; font-weight: 600; color: #6366f1; text-decoration: none; white-space: nowrap; }
        .cc-cd-empty { border: 1.5px dashed var(--ot-border-basic-table, #e4e7ec); border-radius: 14px; padding: 1.2rem 1.4rem; }
        .cc-cd-lesson { border: 1px solid var(--ot-border-basic-table, #e4e7ec); border-radius: 14px; margin-bottom: 0.65rem; overflow: hidden; }
        .cc-cd-lesson:last-child { margin-bottom: 0; }
        .cc-cd-lesson-summary { display: flex; align-items: center; gap: 0.75rem; padding: 0.8rem 1rem; cursor: pointer; list-style: none; font-size: 0.9rem; font-weight: 600; color: #0f172a; }
        .cc-cd-lesson-summary::-webkit-details-marker { display: none; }
        .cc-cd-lesson-num { width: 24px; height: 24px; border-radius: 50%; background: #ece4fd; color: #5e17eb; font-size: 0.76rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .cc-cd-lesson-title { flex: 1; min-width: 0; }
        .cc-cd-lesson-title small { display: block; font-weight: 500; color: #8b93a3; font-size: 0.74rem; margin-top: 0.1rem; }
        .cc-cd-lesson-chev { color: #8b93a3; transition: transform .15s ease; flex-shrink: 0; }
        details[open] .cc-cd-lesson-chev { transform: rotate(180deg); }
        .cc-cd-lesson-body { padding: 0 1rem 1rem 3.25rem; border-top: 1px solid var(--ot-border-basic-table, #e4e7ec); }
        .cc-cd-sub { margin-top: 0.9rem; }
        .cc-cd-sub h5 { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #8b93a3; margin: 0 0 0.5rem; }
        .cc-cd-res-list, .cc-cd-out-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.4rem; }
        .cc-cd-out-list { list-style: disc; padding-left: 1.1rem; }
        .cc-cd-res-list li { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; }
        .cc-cd-out-list li { font-size: 0.85rem; color: #475569; line-height: 1.5; }
        .cc-cd-ico { flex-shrink: 0; }
        .cc-cd-pill-row { display: flex; align-items: center; gap: 0.5rem; }
        .cc-cd-hwtitle { font-size: 0.85rem; font-weight: 600; color: #0f172a; }
        .cc-cd-hw-wrap { display: flex; flex-direction: column; gap: 0.5rem; background: #f8f9fb; border: 1px solid var(--ot-border-basic-table, #e4e7ec); border-radius: 12px; padding: 0.55rem; }
        .cc-cd-hwrow { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; padding: 0.5rem 0.6rem; background: #fff; border: 1px solid #edeef1; border-radius: 10px; }
        .cc-cd-hwdue { font-size: 0.74rem; color: #8b93a3; white-space: nowrap; margin-left: auto; }
        .cc-cd-empty-sub { font-size: 0.82rem; color: #8b93a3; font-style: italic; margin: 0; }
    </style>
    @endpush
@endonce
