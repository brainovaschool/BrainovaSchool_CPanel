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

    Two things from the reference dashboard this deliberately leaves out:
    "Skills Development" (no skill-tagging exists for these gradable
    items - would be fabricated numbers) and the Certificates/Portfolio/
    Learning-Report cards (no certificate or portfolio feature exists
    yet to link to). The "Overall Performance" donut is built as a tall
    segmented bar instead of a donut - same part-to-whole story, but a
    donut is explicitly a last resort in this app's own chart guidance.
--}}
@php
    $pr_selected = $data['selectedProgram'] ?? null;
    $pr_progress = $data['progress'] ?? null;

    $pr_statusMeta = [
        'completed'   => ['label' => 'Completed',   'color' => '#0ca30c', 'soft' => '#e3f6e3', 'icon' => 'fa-circle-check'],
        'in_progress' => ['label' => 'In Progress',  'color' => '#c98500', 'soft' => '#fdf0d9', 'icon' => 'fa-hourglass-half'],
        'not_started' => ['label' => 'Not Started',  'color' => '#898781', 'soft' => '#eceae3', 'icon' => 'fa-circle'],
    ];
@endphp

<div class="cc-pr">
    @if (!$pr_selected)
        <div class="cc-cd-card">
            <p class="text-secondary mb-0" style="font-size: 13px;">Pick a course above (under Course Details) to see its progress &amp; report.</p>
        </div>
    @else
        <div class="cc-pr-cards">
            <div class="cc-pr-stat">
                <span class="cc-pr-stat-icon cc-pr-stat-icon--violet"><i class="fa-solid fa-graduation-cap"></i></span>
                <p class="cc-pr-stat-label">Class Average</p>
                <p class="cc-pr-stat-value">{{ $pr_progress['classAvgPct'] !== null ? $pr_progress['classAvgPct'] . '%' : '—' }}</p>
            </div>
            <div class="cc-pr-stat">
                <span class="cc-pr-stat-icon cc-pr-stat-icon--teal"><i class="fa-solid fa-users"></i></span>
                <p class="cc-pr-stat-label">Enrolled Students</p>
                <p class="cc-pr-stat-value">{{ $pr_progress['students']->count() }}</p>
            </div>
            <div class="cc-pr-stat">
                <span class="cc-pr-stat-icon cc-pr-stat-icon--orange"><i class="fa-solid fa-check-double"></i></span>
                <p class="cc-pr-stat-label">Lessons Completed</p>
                <p class="cc-pr-stat-value">{{ $pr_progress['approvedLessons'] }}/{{ $pr_progress['totalLessons'] }}</p>
                @php $pr_lessonPct = $pr_progress['totalLessons'] > 0 ? round($pr_progress['approvedLessons'] / $pr_progress['totalLessons'] * 100) : 0; @endphp
                <div class="cc-pr-meter"><div class="cc-pr-meter-fill" style="width: {{ $pr_lessonPct }}%;"></div></div>
            </div>
            <div class="cc-pr-stat">
                <span class="cc-pr-stat-icon cc-pr-stat-icon--pink"><i class="fa-solid fa-trophy"></i></span>
                <p class="cc-pr-stat-label">Items Graded</p>
                <p class="cc-pr-stat-value">{{ $pr_progress['totalGraded'] }}/{{ $pr_progress['totalSlots'] }}</p>
                @php $pr_itemsPct = $pr_progress['totalSlots'] > 0 ? round($pr_progress['totalGraded'] / $pr_progress['totalSlots'] * 100) : 0; @endphp
                <div class="cc-pr-meter"><div class="cc-pr-meter-fill" style="width: {{ $pr_itemsPct }}%;"></div></div>
            </div>
        </div>

        @if ($pr_progress['students']->isEmpty())
            <div class="cc-cd-card">
                <p class="text-secondary mb-0" style="font-size: 13px;">No students are enrolled in <strong>{{ $pr_selected->title }}</strong> yet — add them from Website Setup &rarr; Programs.</p>
            </div>
        @else
            <div class="cc-pr-charts">
                <div class="cc-cd-card">
                    <p class="cc-cd-label">Overall Performance</p>
                    @if ($pr_progress['lessonsWithItems'] === 0)
                        <p class="text-secondary mb-0" style="font-size: 13px;">No lesson in this course has a quiz, project, worksheet, or assignment to grade yet.</p>
                    @else
                        @php $pr_total = max($pr_progress['lessonsWithItems'], 1); @endphp
                        <div class="cc-pr-segbar">
                            @foreach ($pr_statusMeta as $pr_key => $pr_meta)
                                @php $pr_count = $pr_progress['statusCounts'][$pr_key]; @endphp
                                @if ($pr_count > 0)
                                    <div class="cc-pr-segbar-part" style="width: {{ round($pr_count / $pr_total * 100, 2) }}%; background: {{ $pr_meta['color'] }};" title="{{ $pr_meta['label'] }}: {{ $pr_count }}">
                                        @if ($pr_count / $pr_total >= 0.12)
                                            {{ round($pr_count / $pr_total * 100) }}%
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        <ul class="cc-pr-seglegend">
                            @foreach ($pr_statusMeta as $pr_key => $pr_meta)
                                <li><span class="cc-pr-seglegend-dot" style="background: {{ $pr_meta['color'] }};"></span>{{ $pr_meta['label'] }} <strong>{{ $pr_progress['statusCounts'][$pr_key] }}</strong></li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="cc-cd-card">
                    <p class="cc-cd-label">Course Progress Trend</p>
                    @if (empty($pr_progress['lessonRows']))
                        <p class="text-secondary mb-0" style="font-size: 13px;">Nothing to chart yet.</p>
                    @else
                        <div style="height: 220px;">
                            <canvas id="cc-pr-trend-canvas"></canvas>
                        </div>
                    @endif
                </div>
            </div>

            <div class="cc-cd-card">
                <p class="cc-cd-label">Class-wise progress</p>
                @if (empty($pr_progress['lessonRows']))
                    <p class="text-secondary mb-0" style="font-size: 13px;">No lesson in this course has a quiz, project, worksheet, or assignment to grade yet.</p>
                @else
                    <div class="table-responsive">
                        <table class="cc-pr-table">
                            <thead>
                                <tr>
                                    <th>Lesson</th>
                                    <th>Gradable items</th>
                                    <th>Status</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pr_progress['lessonRows'] as $row)
                                    @php $pr_meta = $pr_statusMeta[$row['status']]; @endphp
                                    <tr>
                                        <td><a href="{{ route('program-class.grade.edit', $row['class']->program_id) }}">{{ $row['class']->title }}</a></td>
                                        <td>{{ implode(', ', array_map(fn ($i) => $i['title'], $row['items'])) }}</td>
                                        <td>
                                            <span class="cc-pr-pill" style="background: {{ $pr_meta['soft'] }}; color: {{ $pr_meta['color'] }};">
                                                <i class="fa-solid {{ $pr_meta['icon'] }}"></i> {{ $pr_meta['label'] }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($row['avgPct'] !== null)
                                                <div class="cc-pr-row-meter">
                                                    <div class="cc-pr-meter"><div class="cc-pr-meter-fill" style="width: {{ $row['avgPct'] }}%;"></div></div>
                                                    <span>{{ $row['avgPct'] }}%</span>
                                                </div>
                                            @else
                                                <span class="text-secondary">{{ $row['graded'] }}/{{ $row['total'] }} graded</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="cc-pr-charts">
                <div class="cc-cd-card">
                    <p class="cc-cd-label">Recent Activity</p>
                    @if (empty($pr_progress['recentActivity']))
                        <p class="text-secondary mb-0" style="font-size: 13px;">Nothing submitted or graded yet.</p>
                    @else
                        <ul class="cc-pr-activity">
                            @foreach ($pr_progress['recentActivity'] as $event)
                                <li>
                                    @if ($event['type'] === 'graded')
                                        <span class="cc-pr-activity-icon cc-pr-activity-icon--good"><i class="fa-solid fa-circle-check"></i></span>
                                        <span class="cc-pr-activity-text">
                                            <strong>{{ $event['student']->full_name }}</strong> was graded {{ $event['marks'] }}{{ $event['maxMarks'] ? '/' . $event['maxMarks'] : '' }} on <em>{{ $event['itemTitle'] }}</em> ({{ $event['class']->title }})
                                        </span>
                                    @else
                                        <span class="cc-pr-activity-icon cc-pr-activity-icon--neutral"><i class="fa-solid fa-paper-plane"></i></span>
                                        <span class="cc-pr-activity-text">
                                            <strong>{{ $event['student']->full_name }}</strong> submitted <em>{{ $event['itemTitle'] }}</em> ({{ $event['class']->title }})
                                            <a href="{{ $event['mark']->downloadUrl() }}" target="_blank" rel="noopener">View &rarr;</a>
                                        </span>
                                    @endif
                                    <span class="cc-pr-activity-time">{{ $event['at']->diffForHumans() }}</span>
                                </li>
                            @endforeach
                        </ul>
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
                                @foreach ($pr_progress['studentRows'] as $i => $row)
                                    <tr>
                                        <td>
                                            <a href="{{ route('program-class.grade.edit', $pr_selected->id) }}?student={{ $row['student']->id }}">
                                                @if ($i === 0 && $row['pct'] !== null)
                                                    <i class="fa-solid fa-trophy" style="color:#c98500;" title="Top performer"></i>
                                                @endif
                                                {{ $row['student']->full_name }}
                                            </a>
                                        </td>
                                        <td>{{ $row['graded'] }}</td>
                                        <td>{{ $row['pct'] !== null ? $row['pct'] . '%' : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

@if ($pr_selected && !empty($pr_progress['lessonRows']))
    @push('script')
    <script>
    (function () {
        var canvas = document.getElementById('cc-pr-trend-canvas');
        if (!canvas || typeof Chart === 'undefined') { return; }

        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode(array_map(fn ($r) => $r['class']->title, $pr_progress['lessonRows'])) !!},
                datasets: [{
                    data: {!! json_encode(array_map(fn ($r) => $r['avgPct'], $pr_progress['lessonRows'])) !!},
                    borderColor: '#0097b2',
                    backgroundColor: 'rgba(0, 151, 178, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: '#0097b2',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    tension: 0.3,
                    fill: true,
                    spanGaps: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, grid: { color: '#e1e0d9' }, ticks: { callback: function (v) { return v + '%'; } } },
                    x: { grid: { display: false } }
                }
            }
        });
    })();
    </script>
    @endpush
@endif

@once
    @push('css')
    <style>
        .cc-pr-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .cc-pr-stat {
            background: #ffffff;
            border: 1px solid var(--ot-border-basic-table, #e4e7ec);
            border-radius: 16px;
            padding: 1rem 1.1rem;
            position: relative;
        }
        .cc-pr-stat-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 36px; height: 36px; border-radius: 10px; font-size: 0.95rem;
            margin-bottom: 0.6rem;
        }
        .cc-pr-stat-icon--violet { background: #ece4fd; color: #6250d6; }
        .cc-pr-stat-icon--teal { background: #e1f6fa; color: #0097b2; }
        .cc-pr-stat-icon--orange { background: #fde8e0; color: #c2410c; }
        .cc-pr-stat-icon--pink { background: #fce7f3; color: #be185d; }
        .cc-pr-stat-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #8b93a3; margin: 0 0 0.35rem; }
        .cc-pr-stat-value { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0 0 0.4rem; }
        .cc-pr-meter { height: 6px; border-radius: 999px; background: #e1f6fa; overflow: hidden; }
        .cc-pr-meter-fill { height: 100%; border-radius: 999px; background: #0097b2; }
        .cc-pr-charts { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1rem; align-items: stretch; }
        .cc-pr-charts .cc-cd-card { margin-bottom: 0; }
        .cc-pr-segbar { display: flex; height: 28px; border-radius: 999px; overflow: hidden; background: #f1f3f6; margin-bottom: 0.75rem; }
        .cc-pr-segbar-part {
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 0.72rem; font-weight: 700;
            border-right: 2px solid #fff;
        }
        .cc-pr-segbar-part:last-child { border-right: none; }
        .cc-pr-seglegend { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: 0.9rem; font-size: 0.82rem; color: #475569; }
        .cc-pr-seglegend-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 0.4rem; }
        .cc-pr-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .cc-pr-table th { text-align: left; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; color: #8b93a3; padding: 0.5rem 0.6rem; border-bottom: 1px solid var(--ot-border-basic-table, #e4e7ec); }
        .cc-pr-table td { padding: 0.55rem 0.6rem; border-bottom: 1px solid #f1f3f6; color: #334155; }
        .cc-pr-table tbody tr:last-child td { border-bottom: none; }
        .cc-pr-pill { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 999px; }
        .cc-pr-row-meter { display: flex; align-items: center; gap: 0.5rem; min-width: 120px; }
        .cc-pr-row-meter .cc-pr-meter { flex: 1; }
        .cc-pr-row-meter span { font-size: 0.78rem; font-weight: 700; color: #334155; white-space: nowrap; }
        .cc-pr-activity { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.7rem; }
        .cc-pr-activity li { display: flex; align-items: flex-start; gap: 0.6rem; }
        .cc-pr-activity-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0; font-size: 0.75rem; margin-top: 0.1rem;
        }
        .cc-pr-activity-icon--good { background: #e3f6e3; color: #0ca30c; }
        .cc-pr-activity-icon--neutral { background: #eceae3; color: #898781; }
        .cc-pr-activity-text { flex: 1; font-size: 0.85rem; color: #334155; line-height: 1.5; }
        .cc-pr-activity-time { font-size: 0.74rem; color: #8b93a3; white-space: nowrap; margin-top: 0.15rem; }
    </style>
    @endpush
@endonce
