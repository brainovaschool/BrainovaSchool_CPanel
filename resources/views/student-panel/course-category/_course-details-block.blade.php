{{--
    Student-facing Course Details - same visual language as the admin/
    teacher/coordinator version (resources/views/class-content/
    _course-details-block.blade.php, reuses its .cc-cd-* class names) but
    deliberately a SEPARATE template: only Approved lessons ever reach
    here (scoped at the query level in CourseCategoryRepository), there's
    no review workflow or manage/delete actions, and each gradable item
    additionally shows this student's own submission form / status.

    Expects: $data['courseOptions'] (this student's enrolled Programs in
    this category), $data['selectedProgram'] (Program, with teachers +
    Approved programClasses.materials/activities/outcomes eager loaded),
    $data['progress'] (from ProgramClassMarkRepository::progressForStudent()).
--}}
@php
    $sp_options  = $data['courseOptions'] ?? collect();
    $sp_selected = $data['selectedProgram'] ?? null;
    $sp_progress = $data['progress'] ?? null;
    $sp_gradable = [
        'quiz'       => ['label' => 'Quiz',       'open_label' => 'Open quiz'],
        'project'    => ['label' => 'Project',    'open_label' => 'Open project'],
        'worksheet'  => ['label' => 'Worksheet',  'open_label' => 'Open worksheet'],
        'assignment' => ['label' => 'Assignment', 'open_label' => 'Open assignment'],
    ];
@endphp

<div class="cc-cd">
    <div class="cc-cd-card">
        <p class="cc-cd-label">Course</p>
        @if ($sp_options->isEmpty())
            <p class="text-secondary mb-0" style="font-size: 13px;">You're not enrolled in any course in this category yet.</p>
        @else
            <form method="GET" class="cc-cd-select-form">
                <select name="program" class="cc-cd-select" onchange="this.form.submit()">
                    @foreach ($sp_options as $opt)
                        <option value="{{ $opt->id }}" {{ $sp_selected && (int) $sp_selected->id === (int) $opt->id ? 'selected' : '' }}>{{ $opt->title }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if ($sp_selected)
        <div class="cc-cd-card">
            <div class="cc-cd-meta-grid">
                @if ($sp_selected->age_range)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Ages</p><p class="cc-cd-meta-value">{{ $sp_selected->age_range }}</p></div>
                @endif
                @if ($sp_selected->skill_level_text)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Skill level</p><p class="cc-cd-meta-value">{{ $sp_selected->skill_level_text }}</p></div>
                @endif
                @if ($sp_selected->duration)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Duration</p><p class="cc-cd-meta-value">{{ $sp_selected->duration }}</p></div>
                @endif
                @if ($sp_selected->start_date)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Starts</p><p class="cc-cd-meta-value">{{ $sp_selected->start_date->format('d M Y') }}</p></div>
                @endif
                @if ($sp_selected->end_date)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Ends</p><p class="cc-cd-meta-value">{{ $sp_selected->end_date->format('d M Y') }}</p></div>
                @endif
                @if ($sp_selected->class_days)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Days</p><p class="cc-cd-meta-value">{{ $sp_selected->class_days }}</p></div>
                @endif
                @if ($sp_selected->class_time)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Time</p><p class="cc-cd-meta-value">{{ $sp_selected->class_time }}</p></div>
                @endif
                @if ($sp_selected->lessons)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Classes</p><p class="cc-cd-meta-value">{{ $sp_selected->lessons }}</p></div>
                @endif
                @if ($sp_selected->classes_per_week)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Classes per week</p><p class="cc-cd-meta-value">{{ $sp_selected->classes_per_week }}/week</p></div>
                @endif
                @if ($sp_selected->teachers->isNotEmpty())
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Instructor</p><p class="cc-cd-meta-value">{{ $sp_selected->teachers->map(fn ($t) => trim($t->first_name . ' ' . $t->last_name))->join(', ') }}</p></div>
                @endif
            </div>
        </div>

        <div class="cc-cd-card">
            <div class="cc-cd-head-row">
                <p class="cc-cd-label mb-0">Lessons in this course</p>
            </div>

            @php $sp_num = 0; @endphp

            @forelse ($sp_selected->programClasses as $pc)
                @php
                    $sp_num++;
                    $sp_row = $sp_progress['lessonRows'][$pc->id]['items'] ?? [];
                    $sp_chips = [];
                    if ($pc->materials->isNotEmpty()) { $sp_chips[] = ['📎', $pc->materials->count(), 'materials']; }
                    if ($pc->hasQuiz()) { $sp_chips[] = ['❓', 1, 'quiz']; }
                    if ($pc->hasProject()) { $sp_chips[] = ['🏆', 1, 'project']; }
                    if ($pc->hasWorksheet()) { $sp_chips[] = ['📝', 1, 'worksheet']; }
                    if ($pc->hasAssignment()) { $sp_chips[] = ['📋', 1, 'assignment']; }
                    if ($pc->activities->isNotEmpty()) { $sp_chips[] = ['✏️', $pc->activities->count(), 'activities']; }
                    if ($pc->outcomes->isNotEmpty()) { $sp_chips[] = ['🎯', $pc->outcomes->count(), 'outcomes']; }
                @endphp
                <details class="cc-cd-lesson" {{ $sp_num === 1 ? 'open' : '' }}>
                    <summary class="cc-cd-lesson-summary">
                        <span class="cc-cd-lesson-num">{{ $sp_num }}</span>
                        <span class="cc-cd-lesson-title">
                            {{ $pc->title }}
                            @if ($sp_chips)
                                <span class="cc-cd-chip-row">
                                    @foreach ($sp_chips as [$icon, $count, $target])
                                        <span class="cc-cd-chip" data-target="{{ $target }}" title="Show just this">{{ $icon }}@if($count > 1) {{ $count }}@endif</span>
                                    @endforeach
                                </span>
                            @endif
                        </span>
                        <span class="cc-cd-lesson-chev">&#9662;</span>
                    </summary>
                    <div class="cc-cd-lesson-body">
                        @if ($pc->description)
                            <p class="cc-cd-desc cc-cd-desc--sm">{{ $pc->description }}</p>
                        @endif
                        @if ($pc->video_url)
                            <p class="cc-cd-desc cc-cd-desc--sm"><a href="{{ $pc->video_url }}" target="_blank" rel="noopener">Watch recap video &rarr;</a></p>
                        @endif

                        @if ($pc->materials->isNotEmpty())
                            <div class="cc-cd-sub" data-section="materials">
                                <span class="cc-cd-section-pill cc-cd-section-pill--resources">Resources</span>
                                <ul class="cc-cd-res-list">
                                    @foreach ($pc->materials as $m)
                                        <li>
                                            <span class="cc-cd-ico">{{ $m->storage_key ? '📄' : '🔗' }}</span>
                                            <a href="{{ $m->downloadUrl() }}" target="_blank" rel="noopener">{{ $m->label }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @foreach ($sp_gradable as $sp_type => $sp_meta)
                            @php
                                $sp_hasFn = 'has' . ucfirst($sp_type);
                                $sp_item  = $sp_row[$sp_type] ?? null;
                                $sp_mark  = $sp_item['mark'] ?? null;
                            @endphp
                            @if ($pc->$sp_hasFn())
                                <div class="cc-cd-sub" data-section="{{ $sp_type }}">
                                    <span class="cc-cd-section-pill cc-cd-section-pill--{{ $sp_type }}">{{ $sp_meta['label'] }}</span>
                                    <p class="cc-cd-desc cc-cd-desc--sm cc-cd-sub-title">{{ $pc->{$sp_type . '_title'} }}</p>
                                    @if ($pc->{$sp_type . '_description'})
                                        <p class="cc-cd-desc cc-cd-desc--sm">{{ $pc->{$sp_type . '_description'} }}</p>
                                    @endif
                                    @if ($pc->{$sp_type . '_link_url'})
                                        <p class="cc-cd-desc cc-cd-desc--sm"><a href="{{ $pc->{$sp_type . '_link_url'} }}" target="_blank" rel="noopener">{{ $sp_meta['open_label'] }} &rarr;</a></p>
                                    @endif

                                    @if ($sp_mark && $sp_mark->isGraded())
                                        <p class="cc-cd-score">Your score: <strong>{{ $sp_mark->marks }}{{ $pc->{$sp_type . '_max_marks'} ? ' / ' . $pc->{$sp_type . '_max_marks'} : '' }}</strong></p>
                                    @elseif ($sp_mark && $sp_mark->isSubmitted())
                                        <p class="cc-cd-score">
                                            <i class="fa-solid fa-circle-check" style="color:#16a34a;"></i> Submitted {{ $sp_mark->submitted_at->diffForHumans() }} &mdash; waiting to be graded.
                                            <a href="{{ $sp_mark->downloadUrl() }}" target="_blank" rel="noopener">View what you submitted &rarr;</a>
                                        </p>
                                    @else
                                        <form action="{{ route('student-panel-course.submit') }}" method="post" enctype="multipart/form-data" class="cc-cd-submit-form">
                                            @csrf
                                            <input type="hidden" name="program_class_id" value="{{ $pc->id }}">
                                            <input type="hidden" name="assessment_type" value="{{ $sp_type }}">
                                            <input type="hidden" name="category" value="{{ $data['categorySlug'] }}">
                                            <input type="hidden" name="program_id" value="{{ $sp_selected->id }}">
                                            <p class="cc-cd-submit-label">Submit your work &mdash; paste a link, or upload a photo/file:</p>
                                            <div class="cc-cd-submit-row">
                                                <input type="text" name="link_url" class="form-control form-control-sm" placeholder="Paste a link to your work">
                                                <span class="cc-cd-submit-or">or</span>
                                                <input type="file" name="file" class="form-control form-control-sm" accept="image/*,.pdf,.doc,.docx">
                                            </div>
                                            <button type="submit" class="btn btn-sm ot-btn-primary mt-2">Submit</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        @endforeach

                        @if ($pc->activities->isNotEmpty())
                            <div class="cc-cd-sub" data-section="activities">
                                <span class="cc-cd-section-pill cc-cd-section-pill--activities">Activities</span>
                                <ul class="cc-cd-out-list">
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
                            <div class="cc-cd-sub" data-section="outcomes">
                                <span class="cc-cd-section-pill cc-cd-section-pill--outcomes">Learning outcomes</span>
                                <ul class="cc-cd-out-list">
                                    @foreach ($pc->outcomes as $o)
                                        <li>{{ $o->outcome_text }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </details>
            @empty
                <div class="cc-cd-empty">
                    <p class="mb-0">No lessons published for <strong>{{ $sp_selected->title }}</strong> yet.</p>
                </div>
            @endforelse
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
        .cc-cd-select {
            appearance: none;
            -webkit-appearance: none;
            width: 100%;
            max-width: 420px;
            font: inherit;
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
            background: #f8f9fb url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='9' viewBox='0 0 14 9'%3E%3Cpath d='M1 1l6 6 6-6' stroke='%235e17eb' stroke-width='2' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") no-repeat right 1rem center;
            border: 1px solid var(--ot-border-basic-table, #e4e7ec);
            border-radius: 12px;
            padding: 0.7rem 2.5rem 0.7rem 1rem;
            cursor: pointer;
        }
        .cc-cd-select:focus { outline: none; border-color: #5e17eb; box-shadow: 0 0 0 3px rgba(94, 23, 235, .12); }
        .cc-cd-meta-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1rem 1.25rem; }
        .cc-cd-meta-label { font-size: 0.72rem; color: #8b93a3; margin: 0 0 0.15rem; }
        .cc-cd-meta-value { font-size: 0.95rem; font-weight: 700; color: #0f172a; margin: 0; }
        .cc-cd-desc { font-size: 0.9rem; color: #475569; line-height: 1.6; margin: 0 0 0.75rem; }
        .cc-cd-desc--sm { font-size: 0.85rem; margin: 0.6rem 0; }
        .cc-cd-desc:last-child { margin-bottom: 0; }
        .cc-cd-head-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.9rem; }
        .cc-cd-empty { border: 1.5px dashed var(--ot-border-basic-table, #e4e7ec); border-radius: 14px; padding: 1.2rem 1.4rem; }
        .cc-cd-lesson {
            border: 1px solid var(--ot-border-basic-table, #e4e7ec);
            border-left: 4px solid #cbd5e1;
            border-radius: 14px; margin-bottom: 0.65rem; overflow: hidden;
            transition: box-shadow .15s ease, border-color .15s ease;
        }
        .cc-cd-lesson:hover { box-shadow: 0 6px 18px rgba(15, 23, 42, .06); }
        .cc-cd-lesson:last-child { margin-bottom: 0; }
        .cc-cd-lesson-summary { display: flex; align-items: center; gap: 0.75rem; padding: 0.8rem 1rem; cursor: pointer; list-style: none; font-size: 0.9rem; font-weight: 600; color: #0f172a; }
        .cc-cd-lesson-summary::-webkit-details-marker { display: none; }
        .cc-cd-lesson-num {
            width: 26px; height: 26px; border-radius: 50%;
            background: #ece4fd; color: #5e17eb;
            font-size: 0.76rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .cc-cd-lesson-title { flex: 1; min-width: 0; display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
        .cc-cd-chip-row { display: inline-flex; gap: 0.3rem; }
        .cc-cd-chip { font-size: 0.72rem; background: #f1f5f9; color: #475569; border-radius: 999px; padding: 0.08rem 0.4rem; font-weight: 600; }
        .cc-cd-lesson-chev { color: #8b93a3; transition: transform .15s ease; flex-shrink: 0; }
        details[open] .cc-cd-lesson-chev { transform: rotate(180deg); }
        .cc-cd-lesson-body { padding: 0 1rem 1rem 3.25rem; border-top: 1px solid var(--ot-border-basic-table, #e4e7ec); }
        .cc-cd-sub { margin-top: 0.9rem; }
        .cc-cd-section-pill {
            display: inline-block;
            font-size: 0.62rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em;
            padding: 0.2rem 0.55rem; border-radius: 999px; margin-bottom: 0.55rem;
        }
        .cc-cd-section-pill--resources { background: #ccfbf1; color: #0f766e; }
        .cc-cd-section-pill--activities { background: #ede9fe; color: #5b21b6; }
        .cc-cd-section-pill--outcomes { background: #e0e7ff; color: #4338ca; }
        .cc-cd-section-pill--quiz { background: #dbeafe; color: #1d4ed8; }
        .cc-cd-section-pill--project { background: #fce7f3; color: #9d174d; }
        .cc-cd-section-pill--worksheet { background: #ccfbf1; color: #0d9488; }
        .cc-cd-section-pill--assignment { background: #dbeafe; color: #2563eb; }
        .cc-cd-sub-title { display: block; font-weight: 700; color: #0f172a; }
        .cc-cd-res-list, .cc-cd-out-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.4rem; }
        .cc-cd-out-list { list-style: disc; padding-left: 1.1rem; }
        .cc-cd-res-list li { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; }
        .cc-cd-out-list li { font-size: 0.85rem; color: #475569; line-height: 1.5; }
        .cc-cd-ico { flex-shrink: 0; }
        .cc-cd-score { font-size: 0.85rem; font-weight: 700; color: #0f172a; margin: 0.5rem 0 0; }
        .cc-cd-submit-form { margin-top: 0.5rem; background: #f8f9fb; border: 1px dashed var(--ot-border-basic-table, #e4e7ec); border-radius: 10px; padding: 0.75rem; }
        .cc-cd-submit-label { font-size: 0.78rem; font-weight: 600; color: #475569; margin: 0 0 0.5rem; }
        .cc-cd-submit-row { display: flex; gap: 0.6rem; flex-wrap: wrap; align-items: center; }
        .cc-cd-submit-row input[type="text"] { flex: 1 1 220px; min-width: 180px; }
        .cc-cd-submit-row input[type="file"] { flex: 1 1 220px; min-width: 180px; }
        .cc-cd-submit-or { font-size: 0.78rem; color: #94a3b8; font-weight: 600; }
    </style>
    @endpush
@endonce
