{{--
    Shared by all 4 category Overview pages' Progress & Report section.
    Deliberately auto-linked to whichever course Course Details has
    selected (same $data['selectedProgram'], same ?program= query param)
    rather than a second dropdown - "auto linked with the course details"
    was the explicit ask. Expects: $data['selectedProgram'] (Program, with
    students + programClasses.marks eager loaded) and $data['progress']
    (from ProgramClassMarkRepository::progressForProgram()).

    Phase 1: admin/teacher/coordinator view of the whole class. Phase 2
    (later, explicitly deferred) - a student-facing page showing only
    their own score.
--}}
@php
    $pr_selected = $data['selectedProgram'] ?? null;
    $pr_progress = $data['progress'] ?? null;
@endphp

<div class="cc-pr">
    @if (!$pr_selected)
        <div class="cc-cd-card">
            <p class="text-secondary mb-0" style="font-size: 13px;">Pick a course above (under Course Details) to see its progress &amp; report.</p>
        </div>
    @else
        <div class="cc-pr-cards">
            <div class="cc-pr-stat">
                <p class="cc-pr-stat-label">Enrolled Students</p>
                <p class="cc-pr-stat-value">{{ $pr_progress['students']->count() }}</p>
            </div>
            <div class="cc-pr-stat">
                <p class="cc-pr-stat-label">Lessons Completed</p>
                <p class="cc-pr-stat-value">{{ $pr_progress['approvedLessons'] }}/{{ $pr_progress['totalLessons'] }}</p>
            </div>
            <div class="cc-pr-stat">
                <p class="cc-pr-stat-label">Items Graded</p>
                <p class="cc-pr-stat-value">{{ $pr_progress['totalGraded'] }}/{{ $pr_progress['totalSlots'] }}</p>
            </div>
            <div class="cc-pr-stat">
                <p class="cc-pr-stat-label">Class Average</p>
                <p class="cc-pr-stat-value">{{ $pr_progress['classAvgPct'] !== null ? $pr_progress['classAvgPct'] . '%' : '—' }}</p>
            </div>
        </div>

        @if ($pr_progress['students']->isEmpty())
            <div class="cc-cd-card">
                <p class="text-secondary mb-0" style="font-size: 13px;">No students are enrolled in <strong>{{ $pr_selected->title }}</strong> yet — add them from Website Setup &rarr; Programs.</p>
            </div>
        @else
            <div class="cc-cd-card">
                <p class="cc-cd-label">Lesson-wise progress</p>
                @if (empty($pr_progress['lessonRows']))
                    <p class="text-secondary mb-0" style="font-size: 13px;">No lesson in this course has a quiz, project, worksheet, or assignment to grade yet.</p>
                @else
                    <div class="table-responsive">
                        <table class="cc-pr-table">
                            <thead>
                                <tr>
                                    <th>Lesson</th>
                                    <th>Gradable items</th>
                                    <th>Graded</th>
                                    <th>Average</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pr_progress['lessonRows'] as $row)
                                    <tr>
                                        <td><a href="{{ route('program-class.grade.edit', $row['class']->id) }}">{{ $row['class']->title }}</a></td>
                                        <td>{{ implode(', ', array_map(fn ($i) => $i['title'], $row['items'])) }}</td>
                                        <td>{{ $row['graded'] }}/{{ $row['total'] }}</td>
                                        <td>{{ $row['avgPct'] !== null ? $row['avgPct'] . '%' : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="cc-cd-card">
                <p class="cc-cd-label">Student scores</p>
                <div class="table-responsive">
                    <table class="cc-pr-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Items graded</th>
                                <th>Average</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pr_progress['studentRows'] as $row)
                                <tr>
                                    <td><a href="{{ route('program-student-progress.show', [$pr_selected->id, $row['student']->id]) }}">{{ $row['student']->full_name }}</a></td>
                                    <td>{{ $row['graded'] }}</td>
                                    <td>{{ $row['pct'] !== null ? $row['pct'] . '%' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</div>

@once
    @push('css')
    <style>
        .cc-pr-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .cc-pr-stat {
            background: #ffffff;
            border: 1px solid var(--ot-border-basic-table, #e4e7ec);
            border-radius: 16px;
            padding: 1rem 1.1rem;
        }
        .cc-pr-stat-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #8b93a3; margin: 0 0 0.35rem; }
        .cc-pr-stat-value { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
        .cc-pr-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .cc-pr-table th { text-align: left; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; color: #8b93a3; padding: 0.5rem 0.6rem; border-bottom: 1px solid var(--ot-border-basic-table, #e4e7ec); }
        .cc-pr-table td { padding: 0.55rem 0.6rem; border-bottom: 1px solid #f1f3f6; color: #334155; }
        .cc-pr-table tbody tr:last-child td { border-bottom: none; }
    </style>
    @endpush
@endonce
