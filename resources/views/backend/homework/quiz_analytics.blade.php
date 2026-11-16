@extends('backend.master')
@section('title') {{ @$data['title'] }} @endsection

@push('css')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ global_asset('backend/assets/css/homework-teacher-gamified.css') }}">
<link rel="stylesheet" href="{{ global_asset('backend/assets/css/quiz-analytics-gamified.css') }}">
@endpush

@section('content')
<div class="page-content homework-index-page hw-gamified qa-portal qa-gamified">
<div class="qa-gam-inner">

@php
    $a               = $data['analytics'];
    $questions       = $a['questions'];
    $homework        = $a['homework'];
    $submitted       = (int) ($a['submitted_students'] ?? 0);
    $maxMarks        = (float) ($a['max_marks'] ?? 0);
    $avgScore        = $a['avg_score'];
    $avgScorePct     = $a['avg_score_pct'];
    $studentScores   = $a['student_scores'] ?? [];
    $histLabels      = $a['histogram_labels'] ?? [];
    $histData        = $a['histogram_data'] ?? [];
    $avgQuestionAcc  = (int) ($a['avg_question_accuracy'] ?? 0);
    $totalQ          = count($questions);
    $hardestQ        = $totalQ ? collect($questions)->sortBy('accuracy_pct')->first() : null;
    $easiestQ        = $totalQ ? collect($questions)->sortByDesc('accuracy_pct')->first() : null;
    // Reused as-is for a Program Class lesson's quiz (see
    // ProgramClassRepository::getQuizAnalytics()) - everything above this
    // is already owner-agnostic (plain arrays), only the breadcrumb/back
    // links below are Homework-specific by default.
    $isHomeworkOwner = ($a['owner_type'] ?? 'homework') === 'homework';
@endphp

<div class="qa-hero">
    <div class="row align-items-center g-3">
        <div class="col-lg-8">
            <h4 class="bradecrumb-title mb-1">Quiz analytics</h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                @if ($isHomeworkOwner)
                    <li class="breadcrumb-item"><a href="{{ route('homework.index') }}">Homework</a></li>
                @else
                    <li class="breadcrumb-item"><a href="{{ $a['back_url'] }}">Lesson</a></li>
                @endif
                <li class="breadcrumb-item active" aria-current="page">Analytics</li>
            </ol>
        </div>
        <div class="col-lg-4">
            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                @if ($isHomeworkOwner)
                    <a href="{{ route('homework.export-results', $homework->id) }}" class="btn btn-sm qa-gam-btn-outline">
                        <i class="fa-solid fa-download me-1"></i>Export CSV
                    </a>
                    <a href="{{ route('homework.index') }}" class="btn btn-sm qa-gam-btn-ice">
                        <i class="fa-solid fa-arrow-left me-1"></i>Back
                    </a>
                @else
                    <a href="{{ $a['back_url'] }}" class="btn btn-sm qa-gam-btn-ice">
                        <i class="fa-solid fa-arrow-left me-1"></i>Back
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Summary --}}
<div class="qa-card qa-summary-card mb-4">
    <div class="row align-items-stretch g-3">
        <div class="col-lg-5">
            <div class="qa-eyebrow">Quiz</div>
            <h5 class="fw-bold mb-2" style="color:var(--bt)">{{ $homework->title ?? 'Untitled quiz' }}</h5>
            <div class="text-muted" style="font-size:13px;line-height:1.5">
                Max marks: <strong>{{ $maxMarks > 0 ? rtrim(rtrim(number_format($maxMarks, 2, '.', ''), '0'), '.') : '—' }}</strong>
                <span class="d-none d-sm-inline">&nbsp;|&nbsp;</span><br class="d-sm-none">
                Questions: <strong>{{ $totalQ }}</strong>
                <span class="d-none d-sm-inline">&nbsp;|&nbsp;</span><br class="d-sm-none">
                Submissions: <strong>{{ $submitted }}</strong>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="row g-2 g-md-3 align-items-stretch">
                <div class="col-6 col-md-3 d-flex">
                    <div class="qa-stat-tile w-100">
                        <div class="qa-stat-val" style="color:var(--bp)">{{ $avgScore !== null ? rtrim(rtrim(number_format($avgScore, 2, '.', ''), '0'), '.') : '—' }}</div>
                        <div class="qa-stat-lbl">Avg score (all)</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 d-flex">
                    <div class="qa-stat-tile w-100">
                        <div class="qa-stat-val" style="color:#7c3aed">{{ $avgScorePct !== null ? $avgScorePct . '%' : '—' }}</div>
                        <div class="qa-stat-lbl">Avg % of max</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 d-flex">
                    <div class="qa-stat-tile w-100">
                        <div class="qa-stat-val" style="color:var(--ba)">{{ $avgQuestionAcc }}%</div>
                        <div class="qa-stat-lbl">Avg item accuracy</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 d-flex">
                    <div class="qa-stat-tile w-100">
                        <div class="qa-stat-val" style="color:var(--bg)">{{ $submitted > 0 ? $submitted : '—' }}</div>
                        <div class="qa-stat-lbl">Students</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($submitted === 0)
<div class="qa-card text-center py-5 text-muted mb-4">
    <i class="fa-solid fa-user-group fs-2 d-block opacity-25 mb-3"></i>
    <p class="mb-1 fw-semibold">No submissions yet</p>
    <p class="mb-0 small">Per-question charts will populate after students submit the quiz. New submissions also store each answer for this report.</p>
</div>
@else

@if(count($histLabels) && array_sum($histData) > 0)
<div class="qa-card mb-4">
    <h6 class="qa-section-title mb-3">
        <i class="fa-solid fa-chart-simple me-1"></i>Score distribution (% of max marks)
    </h6>
    <div class="chart-wrap"><canvas id="histChart"></canvas></div>
</div>
@endif

@if(count($studentScores))
<div class="qa-card mb-4">
    <h6 class="qa-section-title mb-3">
        <i class="fa-solid fa-chart-bar me-1"></i>Each student’s score (% of max)
    </h6>
    <div class="chart-wrap" style="min-height:{{ max(220, min(count($studentScores) * 28, 520)) }}px">
        <canvas id="studentBarChart"></canvas>
    </div>
</div>

<div class="qa-card mb-4">
    <h6 class="qa-section-title mb-3">
        <i class="fa-solid fa-table me-1"></i>Scores by student
    </h6>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0 qa-score-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th class="text-end">Marks</th>
                    <th class="text-end">% of max</th>
                </tr>
            </thead>
            <tbody>
                @foreach($studentScores as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['student_name'] }}</td>
                    <td class="text-end">{{ $row['marks'] !== null ? rtrim(rtrim(number_format($row['marks'], 2, '.', ''), '0'), '.') : '—' }}</td>
                    <td class="text-end">{{ $row['pct'] !== null ? $row['pct'] . '%' : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endif

@if($totalQ > 0)
<div class="qa-card mb-4">
    <div class="row g-3 align-items-stretch">
        <div class="col-md-7 col-lg-8 d-flex flex-column">
            <h6 class="qa-section-title mb-2">
                <i class="fa-solid fa-bullseye me-1"></i>Item accuracy by question
            </h6>
            <div class="chart-wrap flex-grow-1" style="min-height:260px">
                <canvas id="accChart"></canvas>
            </div>
        </div>
        <div class="col-md-5 col-lg-4 d-flex flex-column justify-content-center gap-3">
            <div class="qa-insight-box text-center py-3">
                <div class="text-muted small text-uppercase fw-bold mb-1">Hardest item</div>
                <div class="fs-3 fw-bold text-danger">{{ $hardestQ ? $hardestQ['accuracy_pct'] . '%' : '—' }}</div>
            </div>
            <div class="qa-insight-box text-center py-3">
                <div class="text-muted small text-uppercase fw-bold mb-1">Easiest item</div>
                <div class="fs-3 fw-bold text-success">{{ $easiestQ ? $easiestQ['accuracy_pct'] . '%' : '—' }}</div>
            </div>
        </div>
    </div>
</div>
@endif

@if(empty($questions))
<div class="qa-card text-center py-5 text-muted mb-4">
    <i class="fa-solid fa-circle-question fs-2 d-block opacity-20 mb-2"></i>
    <p class="mb-0">No questions are defined for this quiz.</p>
</div>
@else

@foreach($questions as $i => $q)
@php
    $pct   = $q['accuracy_pct'];
    $cls   = $pct >= 70 ? 'acc-high' : ($pct >= 40 ? 'acc-mid' : 'acc-low');
    $accBg = $pct >= 70 ? 'acc-h'    : ($pct >= 40 ? 'acc-m'   : 'acc-l');
    $wrongCounts = [
        'a' => $q['option_counts']['a'],
        'b' => $q['option_counts']['b'],
        'c' => $q['option_counts']['c'],
        'd' => $q['option_counts']['d'],
    ];
    foreach(['a','b','c','d'] as $letter) {
        $optionText = $q['option_'.$letter] ?? '';
        if(strtolower(trim((string)$optionText)) === strtolower(trim((string)($q['correct_answer'] ?? '')))) {
            $wrongCounts[$letter] = -1;
        }
    }
    $mostWrongLetter = array_search(max($wrongCounts), $wrongCounts);
    $opts = [
        'a' => ['label'=>'A','text'=>$q['option_a'],'count'=>$q['option_counts']['a']],
        'b' => ['label'=>'B','text'=>$q['option_b'],'count'=>$q['option_counts']['b']],
        'c' => ['label'=>'C','text'=>$q['option_c'],'count'=>$q['option_counts']['c']],
        'd' => ['label'=>'D','text'=>$q['option_d'],'count'=>$q['option_counts']['d']],
    ];
    $maxCount = max($q['option_counts']['a'], $q['option_counts']['b'], $q['option_counts']['c'], $q['option_counts']['d'], 1);
@endphp

<div class="q-card {{ $cls }}">
    <div class="d-flex align-items-start gap-3 mb-3">
        <span class="q-num">{{ $i + 1 }}</span>
        <div class="flex-grow-1 min-w-0">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                <p class="mb-0 fw-semibold" style="font-size:14px;color:var(--bt)">{{ $q['question'] }}</p>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <span class="acc-badge {{ $accBg }}">{{ $pct }}% correct</span>
                    <span class="text-muted" style="font-size:12px">{{ $q['correct_count'] }} / {{ max($q['total_attempts'], 1) }} responses</span>
                </div>
            </div>
            @if($q['hint'])
            <div class="text-muted mb-2" style="font-size:12px">
                <i class="fa-solid fa-lightbulb me-1 text-warning"></i>Hint: {{ $q['hint'] }}
            </div>
            @endif
        </div>
    </div>

    <div class="ps-md-5">
        @foreach($opts as $letter => $opt)
        @php
            $isCorrect   = strtolower(trim((string)$opt['text'])) === strtolower(trim((string)($q['correct_answer'] ?? '')));
            $isMostWrong = ($letter === $mostWrongLetter && !$isCorrect && $opt['count'] > 0);
            $barClass    = $isCorrect ? 'opt-correct' : ($isMostWrong ? 'opt-most-wrong' : 'opt-wrong');
            $barWidth    = $maxCount > 0 ? round(($opt['count'] / $maxCount) * 100) : 0;
            $den         = $submitted > 0 ? $submitted : max($q['total_attempts'], 1);
            $countPct    = $den > 0 ? round(($opt['count'] / $den) * 100) : 0;
        @endphp
        <div class="opt-bar-wrap">
            <span class="opt-label" style="color:{{ $isCorrect ? 'var(--bg)' : ($isMostWrong ? 'var(--br)' : 'var(--bs)') }}">
                {{ $opt['label'] }}
                @if($isCorrect)<i class="fa-solid fa-check" style="font-size:9px"></i>@endif
            </span>
            <div class="opt-bar-bg {{ $barClass }}">
                <div class="opt-bar-fill" style="width:{{ $barWidth }}%"></div>
            </div>
            <span class="opt-count">{{ $opt['count'] }}</span>
            <span class="opt-pct text-muted">({{ $countPct }}%)</span>
            @if($isMostWrong)
                <span class="badge bg-danger" style="font-size:9px">Most chosen wrong</span>
            @endif
        </div>
        @endforeach

        @if($q['option_counts']['skipped'] > 0)
        @php $skipPct = $submitted > 0 ? round(($q['option_counts']['skipped'] / $submitted) * 100) : 0; @endphp
        <div class="opt-bar-wrap" style="opacity:.65">
            <span class="opt-label text-muted">—</span>
            <div class="opt-bar-bg opt-wrong" style="flex:1">
                <div class="opt-bar-fill" style="width:{{ min(100, $skipPct) }}%;background:#94a3b8"></div>
            </div>
            <span class="opt-count">{{ $q['option_counts']['skipped'] }}</span>
            <span class="opt-pct text-muted">({{ $skipPct }}%)</span>
            <span class="text-muted" style="font-size:11px">no response / not stored</span>
        </div>
        @endif
    </div>
</div>
@endforeach
@endif

</div>
</div>
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const histEl = document.getElementById('histChart');
    const histLabels = @json($histLabels);
    const histData   = @json($histData);
    if (histEl && histLabels.length && histData.some(n => n > 0)) {
        new Chart(histEl, {
            type: 'bar',
            data: {
                labels: histLabels,
                datasets: [{
                    label: 'Students',
                    data: histData,
                    backgroundColor: '#0284c7',
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(14,165,233,0.12)' } },
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                }
            }
        });
    }

    const studentEl = document.getElementById('studentBarChart');
    const students = @json($studentScores);
    if (studentEl && students.length) {
        const labels = students.map(s => s.student_name);
        const pcts   = students.map(s => s.pct !== null ? s.pct : 0);
        new Chart(studentEl, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '% of max marks',
                    data: pcts,
                    backgroundColor: pcts.map(p => p >= 70 ? '#059669' : (p >= 40 ? '#f59e0b' : '#dc2626')),
                    borderRadius: 6,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: c => ' ' + c.raw + '%' } }
                },
                scales: {
                    x: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' }, grid: { color: 'rgba(14,165,233,0.1)' } },
                    y: { ticks: { font: { size: 11 } }, grid: { display: false } }
                }
            }
        });
    }

    const accEl = document.getElementById('accChart');
    const questions = @json($questions);
    if (accEl && questions.length) {
        const labels = questions.map((_, i) => 'Q' + (i + 1));
        new Chart(accEl, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Accuracy %',
                    data: questions.map(q => q.accuracy_pct),
                    backgroundColor: questions.map(q => q.accuracy_pct >= 70 ? '#059669' : (q.accuracy_pct >= 40 ? '#f59e0b' : '#dc2626')),
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' }, grid: { color: 'rgba(14,165,233,0.12)' } },
                    x: { ticks: { font: { size: 11 } }, grid: { display: false } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ' ' + ctx.raw + '% correct' } }
                }
            }
        });
    }
})();
</script>
@endpush
