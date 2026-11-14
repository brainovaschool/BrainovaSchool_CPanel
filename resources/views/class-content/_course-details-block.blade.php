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
    {{-- Provides the delete_row() JS the delete button below calls - the
         same confirm-then-ajax-delete helper used everywhere else in the
         admin dashboard. --}}
    @include('backend.partials.delete-ajax')
@endonce

{{-- Clicking a lesson's icon chip (📎 ❓ 🏆 ✏️ 🎯) opens that lesson and
     shows ONLY the matching section, instead of the chip being purely
     decorative. Opening the row any other way (title, chevron) resets it
     back to showing everything. Event-delegated once for the whole page,
     since lessons from both sources (Program Classes and Class Content)
     share the same .cc-cd-lesson/.cc-cd-sub markup. --}}
@once
    @push('script')
    <script>
    (function () {
        document.addEventListener('click', function (e) {
            var chip = e.target.closest('.cc-cd-chip[data-target]');
            if (chip) {
                e.preventDefault();
                e.stopPropagation();
                var details = chip.closest('details.cc-cd-lesson');
                if (!details) { return; }
                details.open = true;
                var target = chip.getAttribute('data-target');
                var matched = null;
                details.querySelectorAll('.cc-cd-sub[data-section]').forEach(function (sec) {
                    var isMatch = sec.getAttribute('data-section') === target;
                    sec.style.display = isMatch ? '' : 'none';
                    if (isMatch) { matched = sec; }
                });
                if (matched) {
                    matched.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
                return;
            }

            var summary = e.target.closest('.cc-cd-lesson-summary');
            if (summary) {
                var row = summary.closest('details.cc-cd-lesson');
                if (row && !row.open) {
                    row.querySelectorAll('.cc-cd-sub[data-section]').forEach(function (sec) {
                        sec.style.display = '';
                    });
                }
            }
        });
    })();
    </script>
    @endpush
@endonce

@php
    // The 4 parent Overview controllers put everything into $data (e.g.
    // $data['courseOptions']) rather than passing bare variables, and
    // @include shares the parent view's scope as-is - so this reads
    // straight from $data rather than from top-level variables that were
    // never actually set.
    $cd_options  = $data['courseOptions'] ?? collect();
    $cd_selected = $data['selectedProgram'] ?? null;
    $cd_modules  = $data['contentModules'] ?? collect();
    $cd_stats    = $data['homeworkStats'] ?? [];
    $cd_typeKey  = ['homework' => 'hw', 'quiz' => 'quiz', 'project' => 'project', 'activity' => 'activity', 'game' => 'game', 'assignment' => 'assignment'];

    // Program Classes: the newer, simpler way to add a course's classes
    // (teacher fills them in directly, no Class/Section/Subject link
    // needed) - meant to eventually replace the Class Content list above.
    $cd_viewer = auth()->user();
    $cd_is_admin = (int) ($cd_viewer->role_id ?? 0) === 1;
    $cd_viewer_staff_id = !$cd_is_admin ? ($cd_viewer->staff->id ?? null) : null;
    $cd_can_manage = $cd_selected && ($cd_is_admin || ($cd_viewer_staff_id && $cd_selected->teachers->contains('id', $cd_viewer_staff_id)));
    $cd_program_classes = $cd_selected ? $cd_selected->programClasses : collect();

    // A Coordinator reviews inline right here instead of a separate page -
    // same format the teacher sees, just with Approve / Request changes
    // instead of Edit / Submit. Scoped to lessons created by teachers this
    // coordinator actually oversees (Staff::coordinatees()).
    $cd_is_coordinator = !$cd_is_admin && $cd_viewer_staff_id && hasPermission('class_content_coordinator_review');
    $cd_coordinatee_ids = $cd_is_coordinator ? $cd_viewer->staff->coordinatees->pluck('id')->all() : [];
@endphp

<div class="cc-cd">
    <div class="cc-cd-card">
        <p class="cc-cd-label">Course</p>
        @if ($cd_options->isEmpty())
            <p class="text-secondary mb-0" style="font-size: 13px;">No courses in this category yet.</p>
        @else
            <form method="GET" class="cc-cd-select-form">
                <select name="program" class="cc-cd-select" onchange="this.form.submit()">
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
                @if ($cd_selected->skill_level_text)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Skill level</p><p class="cc-cd-meta-value">{{ $cd_selected->skill_level_text }}</p></div>
                @endif
                @if ($cd_selected->duration)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Duration</p><p class="cc-cd-meta-value">{{ $cd_selected->duration }}</p></div>
                @endif
                @if ($cd_selected->start_date)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Starts</p><p class="cc-cd-meta-value">{{ $cd_selected->start_date->format('d M Y') }}</p></div>
                @endif
                @if ($cd_selected->end_date)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Ends</p><p class="cc-cd-meta-value">{{ $cd_selected->end_date->format('d M Y') }}</p></div>
                @endif
                @if ($cd_selected->class_days)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Days</p><p class="cc-cd-meta-value">{{ $cd_selected->class_days }}</p></div>
                @endif
                @if ($cd_selected->class_time)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Time</p><p class="cc-cd-meta-value">{{ $cd_selected->class_time }}</p></div>
                @endif
                @if ($cd_selected->lessons)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Classes</p><p class="cc-cd-meta-value">{{ $cd_selected->lessons }}</p></div>
                @endif
                @if ($cd_selected->classes_per_week)
                    <div class="cc-cd-meta-item"><p class="cc-cd-meta-label">Classes per week</p><p class="cc-cd-meta-value">{{ $cd_selected->classes_per_week }}/week</p></div>
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

        <div class="cc-cd-card">
            <div class="cc-cd-head-row">
                <p class="cc-cd-label mb-0">Lessons in this course</p>
                <div class="d-flex align-items-center gap-3">
                    @if ($cd_can_manage)
                        <a class="cc-cd-manage-link" href="{{ route('program-class.create', $cd_selected->id) }}"><i class="fa-solid fa-plus"></i> Add a lesson</a>
                    @endif
                    @if ($cd_is_admin)
                        <a class="cc-cd-manage-link" href="{{ route('program.edit', $cd_selected->id) }}">Manage in Website Setup <i class="fa-solid fa-arrow-right"></i></a>
                    @endif
                </div>
            </div>

            @php $cd_num = 0; @endphp

            @forelse ($cd_program_classes as $pc)
                @php
                    $cd_num++;
                    $cd_chips = [];
                    if ($pc->materials->isNotEmpty()) { $cd_chips[] = ['📎', $pc->materials->count(), 'materials']; }
                    if ($pc->hasQuiz()) { $cd_chips[] = ['❓', 1, 'quiz']; }
                    if ($pc->hasProject()) { $cd_chips[] = ['🏆', 1, 'project']; }
                    if ($pc->hasWorksheet()) { $cd_chips[] = ['📝', 1, 'worksheet']; }
                    if ($pc->hasAssignment()) { $cd_chips[] = ['📋', 1, 'assignment']; }
                    if ($pc->activities->isNotEmpty()) { $cd_chips[] = ['✏️', $pc->activities->count(), 'activities']; }
                    if ($pc->outcomes->isNotEmpty()) { $cd_chips[] = ['🎯', $pc->outcomes->count(), 'outcomes']; }
                @endphp
                <details id="row_{{ $pc->id }}" class="cc-cd-lesson cc-cd-lesson--{{ $pc->review_status }}" {{ $cd_num === 1 ? 'open' : '' }}>
                    <summary class="cc-cd-lesson-summary">
                        <span class="cc-cd-lesson-num cc-cd-lesson-num--{{ $pc->review_status }}">{{ $cd_num }}</span>
                        <span class="cc-cd-lesson-title">
                            {{ $pc->title }}
                            @if ($cd_chips)
                                <span class="cc-cd-chip-row">
                                    @foreach ($cd_chips as [$icon, $count, $target])
                                        <span class="cc-cd-chip" data-target="{{ $target }}" title="Show just this">{{ $icon }}@if($count > 1) {{ $count }}@endif</span>
                                    @endforeach
                                </span>
                            @endif
                        </span>
                        @php
                            $cd_coord_can_decide = $cd_is_coordinator
                                && in_array((int) $pc->created_by, $cd_coordinatee_ids, true)
                                && in_array($pc->review_status, [\App\Models\WebsiteSetup\ProgramClass::SUBMITTED, \App\Models\WebsiteSetup\ProgramClass::CHANGES_REQUESTED], true);
                            // Admin can delete any lesson, any status (same
                            // bypass used everywhere else); the owning
                            // teacher only while it's still theirs to edit.
                            $cd_can_delete = $cd_is_admin
                                || ((int) $pc->created_by === (int) $cd_viewer_staff_id && $pc->isEditableByTeacher());
                        @endphp
                        @if ($cd_can_manage || $cd_is_coordinator)
                            <span class="cc-cd-status-tag cc-cd-status-tag--{{ $pc->review_status }}"><i></i>{{ \App\Models\WebsiteSetup\ProgramClass::REVIEW_STATUSES[$pc->review_status] ?? $pc->review_status }}</span>
                        @endif
                        @if ($cd_can_manage && !empty($pc->gradableItems()))
                            <a href="{{ route('program-class.grade.edit', $pc->id) }}" class="cc-cd-grade-btn" title="Grade marks" onclick="event.stopPropagation();">
                                <i class="fa-solid fa-marker"></i>
                            </a>
                        @endif
                        @if ($cd_can_delete)
                            <button type="button" class="cc-cd-delete-btn" title="Delete this lesson"
                                onclick="event.stopPropagation(); delete_row('program-class/delete', {{ $pc->id }});">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        @endif
                        <span class="cc-cd-lesson-chev">&#9662;</span>
                    </summary>
                    <div class="cc-cd-lesson-body">
                        @php
                            $cd_admin_can_decide = $cd_is_admin && in_array($pc->review_status, [\App\Models\WebsiteSetup\ProgramClass::SUBMITTED, \App\Models\WebsiteSetup\ProgramClass::COORDINATOR_REVIEWED], true);
                            $cd_is_owner_or_admin = $cd_is_admin || (int) $pc->created_by === (int) $cd_viewer_staff_id;
                        @endphp
                        @if ($cd_admin_can_decide)
                            <div class="cc-cd-manage-row">
                                <p class="cc-cd-empty-sub">
                                    Submitted by {{ trim(optional($pc->creator)->first_name . ' ' . optional($pc->creator)->last_name) ?: 'the teacher' }}
                                    @if ($pc->review_status === \App\Models\WebsiteSetup\ProgramClass::COORDINATOR_REVIEWED)
                                        &middot; reviewed by {{ trim(optional($pc->coordinator)->first_name . ' ' . optional($pc->coordinator)->last_name) ?: 'the coordinator' }}
                                    @endif
                                </p>
                                @if ($pc->coordinator_feedback)
                                    <p class="cc-cd-empty-sub" style="color:#6d28d9;"><strong>Coordinator's note:</strong> {{ $pc->coordinator_feedback }}</p>
                                @endif
                                <form action="{{ route('program-class-admin.decide', $pc->id) }}" method="post">
                                    @csrf
                                    <textarea name="feedback" class="form-control form-control-sm mb-2" rows="2" placeholder="Feedback for the teacher (optional, required if sending back)"></textarea>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="submit" name="decision" value="approve" class="btn btn-sm ot-btn-primary">Approve</button>
                                        <button type="submit" name="decision" value="request_changes" class="btn btn-sm btn-outline-danger" onclick="return confirm('Send this back to the teacher with your feedback?');">Request changes</button>
                                    </div>
                                </form>
                            </div>
                        @elseif ($cd_coord_can_decide)
                            <div class="cc-cd-manage-row">
                                <p class="cc-cd-empty-sub">
                                    Submitted by {{ trim(optional($pc->creator)->first_name . ' ' . optional($pc->creator)->last_name) ?: 'the teacher' }}
                                    @if ($pc->submitted_at) &middot; {{ $pc->submitted_at->diffForHumans() }} @endif
                                </p>
                                <form action="{{ route('program-class-coordinator.decide', $pc->id) }}" method="post">
                                    @csrf
                                    <textarea name="feedback" class="form-control form-control-sm mb-2" rows="2" placeholder="Feedback for the teacher (optional, required if sending back)"></textarea>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="submit" name="decision" value="send_to_admin" class="btn btn-sm ot-btn-primary">Accept &amp; send to admin</button>
                                        <button type="submit" name="decision" value="request_changes" class="btn btn-sm btn-outline-danger" onclick="return confirm('Send this back to the teacher with your feedback?');">Request changes</button>
                                    </div>
                                </form>
                            </div>
                        @elseif ($cd_can_manage && $cd_is_owner_or_admin && $pc->isEditableByTeacher())
                            <div class="cc-cd-manage-row">
                                @if ($pc->review_status === \App\Models\WebsiteSetup\ProgramClass::CHANGES_REQUESTED)
                                    @if ($pc->coordinator_feedback)
                                        <p class="cc-cd-empty-sub" style="color:#b45309;"><strong>Coordinator's feedback:</strong> {{ $pc->coordinator_feedback }}</p>
                                    @endif
                                    @if ($pc->admin_feedback)
                                        <p class="cc-cd-empty-sub" style="color:#b45309;"><strong>Admin's feedback:</strong> {{ $pc->admin_feedback }}</p>
                                    @endif
                                @endif
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="{{ route('program-class.edit', $pc->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form action="{{ route('program-class.submit', $pc->id) }}" method="post" class="d-inline" onsubmit="return confirm('Submit this lesson for review?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm ot-btn-primary">Submit for review</button>
                                    </form>
                                </div>
                            </div>
                        @elseif ($cd_can_manage && $cd_is_owner_or_admin)
                            <p class="cc-cd-empty-sub">
                                @if ($pc->review_status === \App\Models\WebsiteSetup\ProgramClass::SUBMITTED)
                                    Submitted — waiting on a coordinator or admin to review it.
                                @elseif ($pc->review_status === \App\Models\WebsiteSetup\ProgramClass::COORDINATOR_REVIEWED)
                                    Reviewed by the coordinator — waiting on admin's final approval.
                                @elseif ($pc->review_status === \App\Models\WebsiteSetup\ProgramClass::APPROVED)
                                    Approved{{ $pc->approved_at ? ' ' . $pc->approved_at->diffForHumans() : '' }} — live on Course Details.
                                @endif
                            </p>
                        @elseif ($cd_is_coordinator && $pc->review_status === \App\Models\WebsiteSetup\ProgramClass::COORDINATOR_REVIEWED)
                            <p class="cc-cd-empty-sub">Sent to admin — awaiting their decision.</p>
                        @elseif ($cd_is_coordinator && $pc->review_status === \App\Models\WebsiteSetup\ProgramClass::APPROVED)
                            <p class="cc-cd-empty-sub">Approved by admin.</p>
                        @endif

                        @if ($pc->description)
                            <p class="cc-cd-desc cc-cd-desc--sm">{{ $pc->description }}</p>
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

                        @if ($pc->hasQuiz())
                            <div class="cc-cd-sub" data-section="quiz">
                                <span class="cc-cd-section-pill cc-cd-section-pill--quiz">Quiz</span>
                                <p class="cc-cd-desc cc-cd-desc--sm cc-cd-sub-title">{{ $pc->quiz_title }}</p>
                                @if ($pc->quiz_description)
                                    <p class="cc-cd-desc cc-cd-desc--sm">{{ $pc->quiz_description }}</p>
                                @endif
                                @if ($pc->quiz_link_url)
                                    <p class="cc-cd-desc cc-cd-desc--sm"><a href="{{ $pc->quiz_link_url }}" target="_blank" rel="noopener">Open quiz &rarr;</a></p>
                                @endif
                            </div>
                        @endif

                        @if ($pc->hasProject())
                            <div class="cc-cd-sub" data-section="project">
                                <span class="cc-cd-section-pill cc-cd-section-pill--project">Project</span>
                                <p class="cc-cd-desc cc-cd-desc--sm cc-cd-sub-title">{{ $pc->project_title }}</p>
                                @if ($pc->project_description)
                                    <p class="cc-cd-desc cc-cd-desc--sm">{{ $pc->project_description }}</p>
                                @endif
                                @if ($pc->project_link_url)
                                    <p class="cc-cd-desc cc-cd-desc--sm"><a href="{{ $pc->project_link_url }}" target="_blank" rel="noopener">Open project &rarr;</a></p>
                                @endif
                            </div>
                        @endif

                        @if ($pc->hasWorksheet())
                            <div class="cc-cd-sub" data-section="worksheet">
                                <span class="cc-cd-section-pill cc-cd-section-pill--worksheet">Worksheet</span>
                                <p class="cc-cd-desc cc-cd-desc--sm cc-cd-sub-title">{{ $pc->worksheet_title }}</p>
                                @if ($pc->worksheet_description)
                                    <p class="cc-cd-desc cc-cd-desc--sm">{{ $pc->worksheet_description }}</p>
                                @endif
                                @if ($pc->worksheet_link_url)
                                    <p class="cc-cd-desc cc-cd-desc--sm"><a href="{{ $pc->worksheet_link_url }}" target="_blank" rel="noopener">Open worksheet &rarr;</a></p>
                                @endif
                            </div>
                        @endif

                        @if ($pc->hasAssignment())
                            <div class="cc-cd-sub" data-section="assignment">
                                <span class="cc-cd-section-pill cc-cd-section-pill--assignment">Assignment</span>
                                <p class="cc-cd-desc cc-cd-desc--sm cc-cd-sub-title">{{ $pc->assignment_title }}</p>
                                @if ($pc->assignment_description)
                                    <p class="cc-cd-desc cc-cd-desc--sm">{{ $pc->assignment_description }}</p>
                                @endif
                                @if ($pc->assignment_link_url)
                                    <p class="cc-cd-desc cc-cd-desc--sm"><a href="{{ $pc->assignment_link_url }}" target="_blank" rel="noopener">Open assignment &rarr;</a></p>
                                @endif
                            </div>
                        @endif

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
            @endforelse

            @if ($cd_program_classes->isEmpty() && (!$cd_selected->isLinkedToClassContent() || $cd_modules->isEmpty()))
                <div class="cc-cd-empty">
                    <p class="mb-1">No lessons added yet for <strong>{{ $cd_selected->title }}</strong>.</p>
                    @if ($cd_can_manage)
                        <p class="text-secondary mb-0" style="font-size: 13px;">Use "Add a lesson" above to get started.</p>
                    @elseif ($cd_is_admin && !$cd_selected->isLinkedToClassContent())
                        <p class="text-secondary mb-0" style="font-size: 13px;">
                            This program also isn't linked to Class Content - open it in <a href="{{ route('program.edit', $cd_selected->id) }}">Website Setup &rarr; Course Catalogue &rarr; Program</a> if you'd rather use that instead.
                        </p>
                    @else
                        <p class="text-secondary mb-0" style="font-size: 13px;">Ask the assigned teacher to add lessons here.</p>
                    @endif
                </div>
            @endif

            @if ($cd_selected->isLinkedToClassContent() && $cd_modules->isNotEmpty())
                @foreach ($cd_modules as $module)
                    @foreach ($module->lessons as $lesson)
                        @php $cd_num++; @endphp
                        <details class="cc-cd-lesson" {{ $cd_num === 1 ? 'open' : '' }}>
                            <summary class="cc-cd-lesson-summary">
                                <span class="cc-cd-lesson-num">{{ $cd_num }}</span>
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
                                        <span class="cc-cd-section-pill cc-cd-section-pill--resources">Resources</span>
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
                                        <span class="cc-cd-section-pill cc-cd-section-pill--quiz">Quiz</span>
                                        <p class="cc-cd-desc cc-cd-desc--sm cc-cd-sub-title">{{ $lesson->quiz_title }}</p>
                                        @if ($lesson->quiz_description)
                                            <p class="cc-cd-desc cc-cd-desc--sm">{{ $lesson->quiz_description }}</p>
                                        @endif
                                        @if ($lesson->quiz_link_url)
                                            <p class="cc-cd-desc cc-cd-desc--sm"><a href="{{ $lesson->quiz_link_url }}" target="_blank" rel="noopener">Open quiz &rarr;</a></p>
                                        @endif
                                    </div>
                                @endif

                                @if ($lesson->hasProject())
                                    <div class="cc-cd-sub">
                                        <span class="cc-cd-section-pill cc-cd-section-pill--project">Project</span>
                                        <p class="cc-cd-desc cc-cd-desc--sm cc-cd-sub-title">{{ $lesson->project_title }}</p>
                                        @if ($lesson->project_description)
                                            <p class="cc-cd-desc cc-cd-desc--sm">{{ $lesson->project_description }}</p>
                                        @endif
                                        @if ($lesson->project_link_url)
                                            <p class="cc-cd-desc cc-cd-desc--sm"><a href="{{ $lesson->project_link_url }}" target="_blank" rel="noopener">Open project &rarr;</a></p>
                                        @endif
                                    </div>
                                @endif

                                @if ($lesson->activities->isNotEmpty())
                                    <div class="cc-cd-sub">
                                        <span class="cc-cd-section-pill cc-cd-section-pill--activities">Activities</span>
                                        <ul class="cc-cd-out-list">
                                            @foreach ($lesson->activities as $a)
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

                                @if ($lesson->outcomes->isNotEmpty())
                                    <div class="cc-cd-sub">
                                        <span class="cc-cd-section-pill cc-cd-section-pill--outcomes">Learning outcomes</span>
                                        <ul class="cc-cd-out-list">
                                            @foreach ($lesson->outcomes as $o)
                                                <li>{{ $o->outcome_text }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="cc-cd-sub hw-gamified">
                                    <h5>📚 Homework &amp; assignments</h5>
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
        .cc-cd-status-tag {
            display: inline-flex; align-items: center; gap: 0.35rem;
            font-size: 0.62rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em;
            padding: 0.25rem 0.65rem; border-radius: 999px; flex-shrink: 0;
        }
        .cc-cd-status-tag i { width: 6px; height: 6px; border-radius: 50%; background: currentColor; display: inline-block; }
        .cc-cd-status-tag--draft { background: #f1f5f9; color: #64748b; }
        .cc-cd-status-tag--submitted { background: #dbeafe; color: #1d4ed8; }
        .cc-cd-status-tag--changes_requested { background: #fef3c7; color: #92400e; }
        .cc-cd-status-tag--coordinator_reviewed { background: #f3e8ff; color: #7e22ce; }
        .cc-cd-status-tag--approved { background: #dcfce7; color: #15803d; }
        .cc-cd-manage-row { background: #f8f9fb; border: 1px dashed var(--ot-border-basic-table, #e4e7ec); border-radius: 10px; padding: 0.65rem 0.75rem; margin-bottom: 0.75rem; }
        .cc-cd-manage-link { font-size: 0.82rem; font-weight: 600; color: #6366f1; text-decoration: none; white-space: nowrap; }
        .cc-cd-empty { border: 1.5px dashed var(--ot-border-basic-table, #e4e7ec); border-radius: 14px; padding: 1.2rem 1.4rem; }
        .cc-cd-lesson {
            border: 1px solid var(--ot-border-basic-table, #e4e7ec);
            border-left: 4px solid #cbd5e1;
            border-radius: 14px; margin-bottom: 0.65rem; overflow: hidden;
            transition: box-shadow .15s ease, border-color .15s ease;
        }
        .cc-cd-lesson:hover { box-shadow: 0 6px 18px rgba(15, 23, 42, .06); }
        .cc-cd-lesson:last-child { margin-bottom: 0; }
        .cc-cd-lesson--submitted { border-left-color: #3b82f6; }
        .cc-cd-lesson--changes_requested { border-left-color: #f59e0b; }
        .cc-cd-lesson--coordinator_reviewed { border-left-color: #a855f7; }
        .cc-cd-lesson--approved { border-left-color: #22c55e; }
        .cc-cd-lesson-summary { display: flex; align-items: center; gap: 0.75rem; padding: 0.8rem 1rem; cursor: pointer; list-style: none; font-size: 0.9rem; font-weight: 600; color: #0f172a; }
        .cc-cd-lesson-summary::-webkit-details-marker { display: none; }
        .cc-cd-lesson-num {
            width: 26px; height: 26px; border-radius: 50%;
            background: #ece4fd; color: #5e17eb;
            font-size: 0.76rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .cc-cd-lesson-num--submitted { background: #dbeafe; color: #1d4ed8; }
        .cc-cd-lesson-num--changes_requested { background: #fef3c7; color: #92400e; }
        .cc-cd-lesson-num--coordinator_reviewed { background: #f3e8ff; color: #7e22ce; }
        .cc-cd-lesson-num--approved { background: #dcfce7; color: #15803d; }
        .cc-cd-lesson-title { flex: 1; min-width: 0; display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
        .cc-cd-lesson-title small { display: block; width: 100%; font-weight: 500; color: #8b93a3; font-size: 0.74rem; }
        .cc-cd-chip-row { display: inline-flex; gap: 0.3rem; }
        .cc-cd-chip { font-size: 0.72rem; background: #f1f5f9; color: #475569; border-radius: 999px; padding: 0.08rem 0.4rem; font-weight: 600; }
        .cc-cd-delete-btn {
            background: transparent; border: 1px solid var(--ot-border-basic-table, #e4e7ec); border-radius: 8px;
            color: #94a3b8; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;
            flex-shrink: 0; cursor: pointer; transition: color .15s ease, border-color .15s ease, background .15s ease;
        }
        .cc-cd-delete-btn:hover { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
        .cc-cd-grade-btn {
            background: transparent; border: 1px solid var(--ot-border-basic-table, #e4e7ec); border-radius: 8px;
            color: #94a3b8; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;
            flex-shrink: 0; cursor: pointer; transition: color .15s ease, border-color .15s ease, background .15s ease;
        }
        .cc-cd-grade-btn:hover { color: #5e17eb; border-color: #ddd6fe; background: #f5f3ff; }
        .cc-cd-lesson-chev { color: #8b93a3; transition: transform .15s ease; flex-shrink: 0; }
        details[open] .cc-cd-lesson-chev { transform: rotate(180deg); }
        .cc-cd-lesson-body { padding: 0 1rem 1rem 3.25rem; border-top: 1px solid var(--ot-border-basic-table, #e4e7ec); }
        .cc-cd-sub { margin-top: 0.9rem; }
        .cc-cd-sub h5 { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #8b93a3; margin: 0 0 0.5rem; }
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
        .cc-cd-pill-row { display: flex; align-items: center; gap: 0.5rem; }
        .cc-cd-hwtitle { font-size: 0.85rem; font-weight: 600; color: #0f172a; }
        .cc-cd-hw-wrap { display: flex; flex-direction: column; gap: 0.5rem; background: #f8f9fb; border: 1px solid var(--ot-border-basic-table, #e4e7ec); border-radius: 12px; padding: 0.55rem; }
        .cc-cd-hwrow { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; padding: 0.5rem 0.6rem; background: #fff; border: 1px solid #edeef1; border-radius: 10px; }
        .cc-cd-hwdue { font-size: 0.74rem; color: #8b93a3; white-space: nowrap; margin-left: auto; }
        .cc-cd-empty-sub { font-size: 0.82rem; color: #8b93a3; font-style: italic; margin: 0; }
    </style>
    @endpush
@endonce
