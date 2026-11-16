@extends('student-panel.partials.master')

@section('title')
    {{ $data['isReview'] ? 'Review Quiz' : 'Take Quiz' }}: {{ $data['title'] }}
@endsection

@section('content')
<div class="page-content">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm-8">
                <h4 class="bradecrumb-title mb-0">{{ $data['homework']->title ?? 'Quiz' }}</h4>
                @if ($data['homework']->description)
                    <p class="text-muted mt-1 mb-0">{{ $data['homework']->description }}</p>
                @endif
            </div>
            <div class="col-sm-4 text-end">
                {{-- Timer: counts UP during quiz, hidden in review mode --}}
                @if (!$data['isReview'])
                    <h4 id="quiz-timer" class="text-primary fw-bold">
                        <i class="fa-solid fa-stopwatch"></i> 00:00
                    </h4>
                    <small class="text-muted">Time Elapsed</small>
                @else
                    <span class="badge bg-secondary fs-6">Review Mode — Read Only</span>
                @endif
            </div>
        </div>
    </div>

    {{-- =====================================================================
         REVIEW MODE: student already submitted — show read-only results
    ====================================================================== --}}
    @if ($data['isReview'])
    <div class="card ot-card mt-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">
                    <i class="fa-solid fa-trophy text-warning"></i>
                    Your Score:
                    <span class="text-success fw-bold">{{ $data['submission']->marks }}</span>
                    / {{ $data['homework']->marks }}
                </h4>
                <a href="{{ $data['backUrl'] ?? route('student-panel-homeworks.index') }}"
                   class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> {{ $data['backLabel'] ?? 'Back to Homework' }}
                </a>
            </div>
            <hr>
            @foreach ($data['questions'] as $i => $q)
            <div class="card mb-3 p-4 border-start border-4
                {{ $q->correct_answer ? 'border-success' : 'border-secondary' }}"
                style="background:#fafafa;">
                <h5>{{ $i + 1 }}. {{ $q->question }}</h5>
                <ol type="A" class="mt-2">
                    @foreach (['option_a','option_b','option_c','option_d'] as $opt)
                        <li class="{{ $q->correct_answer === $q->$opt ? 'text-success fw-bold' : '' }}">
                            {{ $q->$opt }}
                            @if ($q->correct_answer === $q->$opt)
                                <i class="fa-solid fa-check-circle text-success ms-1"></i>
                            @endif
                        </li>
                    @endforeach
                </ol>
                <p class="mb-1"><strong>Correct Answer:</strong>
                    <span class="text-success">{{ $q->correct_answer }}</span>
                </p>
                @if ($q->explanation && $q->explanation !== '-')
                    <p class="text-info mb-0 mt-2">
                        <strong>Explanation:</strong> {{ $q->explanation }}
                    </p>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- =====================================================================
         LIVE QUIZ MODE: first attempt
    ====================================================================== --}}
    @else
    <div class="card ot-card mt-4" id="quiz-container">
        <div class="card-body p-5">

            <div class="progress mb-4" style="height: 10px;">
                <div class="progress-bar bg-success" id="quiz-progress"
                     role="progressbar" style="width:0%"></div>
            </div>

            <h5 class="text-muted mb-4">
                Question <span id="current-q-num">1</span>
                of <span id="total-q-num">0</span>
            </h5>

            <h3 class="mb-4 text-dark" id="question-text">Loading...</h3>

            <div class="options-container mb-4" id="options-container">
                {{-- Options rendered by JS using actual option text as values --}}
            </div>

            <div class="alert alert-warning mb-4 d-none" id="hint-box">
                <strong><i class="fa-solid fa-lightbulb"></i> Hint:</strong>
                <span id="hint-text"></span>
                <div class="mt-2 text-danger small">
                    <em>Using this hint deducts 50% of this question's marks.</em>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-5">
                <button class="btn btn-outline-secondary btn-lg"
                        id="btn-prev" onclick="changeQuestion(-1)" disabled>
                    <i class="fa-solid fa-arrow-left"></i> Previous
                </button>

                <button class="btn btn-warning d-none" id="btn-hint" onclick="showHint()">
                    <i class="fa-solid fa-lightbulb"></i> Show Hint (−50%)
                </button>

                <button class="btn btn-primary btn-lg" id="btn-next" onclick="changeQuestion(1)">
                    Next <i class="fa-solid fa-arrow-right"></i>
                </button>
                <button class="btn btn-success btn-lg d-none" id="btn-submit" onclick="submitQuiz()">
                    <i class="fa-solid fa-check-double"></i> Submit Quiz
                </button>
            </div>
        </div>
    </div>

    {{-- Results screen — hidden until AJAX returns success --}}
    <div class="card ot-card mt-4 d-none" id="results-container">
        <div class="card-body p-5 text-center">
            <h1 class="display-4 text-success mb-3">
                <i class="fa-solid fa-trophy"></i> Quiz Complete!
            </h1>
            <h3 class="mb-2">
                Your Score:
                <span id="final-score" class="fw-bold text-primary">—</span>
                / <span id="max-score">{{ $data['homework']->marks }}</span>
            </h3>
            <p class="text-muted mb-1">Time taken: <strong id="final-time">—</strong></p>
            <hr>
            <div id="review-section" class="text-start mt-4"></div>
            <a href="{{ $data['backUrl'] ?? route('student-panel-homeworks.index') }}"
               class="btn btn-primary btn-lg mt-4">
                {{ $data['backLabel'] ?? 'Return to Homework' }}
            </a>
        </div>
    </div>
    @endif

</div>

<style>
    .option-box          { cursor: pointer; transition: all 0.15s; }
    .option-box:hover    { background: #f0f4ff; border-color: #4e73df !important; }
    .option-box.selected { background: #e8f0fe; border-color: #4e73df !important; border-width: 2px !important; }
    .review-correct      { border-left: 5px solid #28a745; background: #f0fbf4; }
    .review-incorrect    { border-left: 5px solid #dc3545; background: #fff5f5; }
    .review-unanswered   { border-left: 5px solid #ffc107; background: #fffdf0; }
</style>
@endsection

@push('script')
@if (!$data['isReview'])
<script>
    // =========================================================================
    // DATA FROM LARAVEL
    // =========================================================================
    const questions   = @json($data['questions']);
    const homeworkId  = {{ $data['homework']->id }};
    const totalMarks  = {{ $data['homework']->marks ?? 0 }};
    const ownerType   = '{{ $data['ownerType'] ?? 'homework' }}';
    const backUrl     = '{{ $data['backUrl'] ?? route('student-panel-homeworks.index') }}';

    // =========================================================================
    // STATE
    // =========================================================================
    let currentIndex = 0;
    // userAnswers stores the ACTUAL OPTION TEXT, not A/B/C/D.
    // This is critical: DB correct_answer stores full text (e.g. "Mercury"),
    // so we must compare text-to-text, never letter-to-text.
    let userAnswers = {};   // { question_id: 'option_text' }
    let hintsUsed   = {};   // { question_id: true }

    // Count-up stopwatch
    let elapsedSeconds = 0;
    let timerInterval;

    // =========================================================================
    // INIT
    // =========================================================================
    $(document).ready(function () {
        $('#total-q-num').text(questions.length);
        startTimer();
        loadQuestion(0);
    });

    // =========================================================================
    // STOPWATCH — counts up from 00:00, stops on submit
    // =========================================================================
    function startTimer() {
        timerInterval = setInterval(function () {
            elapsedSeconds++;
            let m = Math.floor(elapsedSeconds / 60).toString().padStart(2, '0');
            let s = (elapsedSeconds % 60).toString().padStart(2, '0');
            $('#quiz-timer').html(`<i class="fa-solid fa-stopwatch"></i> ${m}:${s}`);
        }, 1000);
    }

    function stopTimer() {
        clearInterval(timerInterval);
        let m = Math.floor(elapsedSeconds / 60).toString().padStart(2, '0');
        let s = (elapsedSeconds % 60).toString().padStart(2, '0');
        $('#final-time').text(`${m}:${s}`);
    }

    // =========================================================================
    // QUESTION RENDERING
    // Radio VALUES are the actual option text — NOT A/B/C/D letters.
    // This ensures comparison with DB correct_answer (which stores full text) works.
    // =========================================================================
    function loadQuestion(index) {
        if (index < 0 || index >= questions.length) return;
        currentIndex = index;

        let q = questions[index];
        $('#current-q-num').text(index + 1);
        $('#question-text').text(q.question);

        // Build option boxes dynamically
        let opts = [
            { label: 'A', text: q.option_a },
            { label: 'B', text: q.option_b },
            { label: 'C', text: q.option_c },
            { label: 'D', text: q.option_d },
        ];

        let html = '';
        opts.forEach(function (opt) {
            let isSelected = userAnswers[q.id] === opt.text;
            html += `
                <div class="form-check mb-3 option-box p-3 border rounded ${isSelected ? 'selected' : ''}"
                     data-value="${escapeHtml(opt.text)}"
                     onclick="selectOption(this, '${escapeHtml(opt.text)}')">
                    <strong>${opt.label}.</strong> ${escapeHtml(opt.text)}
                </div>`;
        });
        $('#options-container').html(html);

        // Hint visibility
        $('#hint-box').addClass('d-none');
        if (q.hint && q.hint.trim() !== '' && q.hint !== '-') {
            if (hintsUsed[q.id]) {
                $('#hint-text').text(q.hint);
                $('#hint-box').removeClass('d-none');
                $('#btn-hint').addClass('d-none');
            } else {
                $('#btn-hint').removeClass('d-none');
            }
        } else {
            $('#btn-hint').addClass('d-none');
        }

        // Progress bar
        $('#quiz-progress').css('width', ((index / questions.length) * 100) + '%');

        // Buttons
        $('#btn-prev').prop('disabled', index === 0);
        if (index === questions.length - 1) {
            $('#btn-next').addClass('d-none');
            $('#btn-submit').removeClass('d-none');
        } else {
            $('#btn-next').removeClass('d-none');
            $('#btn-submit').addClass('d-none');
        }
    }

    function selectOption(el, value) {
        $('.option-box').removeClass('selected');
        $(el).addClass('selected');
        userAnswers[questions[currentIndex].id] = value;
    }

    function changeQuestion(step) {
        loadQuestion(currentIndex + step);
    }

    function showHint() {
        let q = questions[currentIndex];
        hintsUsed[q.id] = true;
        $('#hint-text').text(q.hint);
        $('#hint-box').removeClass('d-none');
        $('#btn-hint').addClass('d-none');
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g,  '&amp;')
            .replace(/</g,  '&lt;')
            .replace(/>/g,  '&gt;')
            .replace(/"/g,  '&quot;')
            .replace(/'/g,  '&#039;');
    }

    // =========================================================================
    // SUBMIT QUIZ
    // Sends answers to server for grading (server is source of truth for score).
    // Client-side review is shown after server responds with earned marks.
    // =========================================================================
    function submitQuiz() {
        stopTimer();
        $('#btn-submit').html('<i class="fa-solid fa-spinner fa-spin"></i> Submitting...').prop('disabled', true);

        // Build client-side review HTML (shown after server confirms)
        let reviewHtml = '';
        questions.forEach(function (q, i) {
            let userAnswer = userAnswers[q.id] || null;
            let isCorrect  = userAnswer &&
                             userAnswer.trim().toLowerCase() === q.correct_answer.trim().toLowerCase();
            let statusClass = !userAnswer ? 'review-unanswered'
                            : isCorrect   ? 'review-correct'
                                          : 'review-incorrect';
            let icon = !userAnswer ? '<i class="fa-solid fa-minus-circle text-warning"></i>'
                     : isCorrect   ? '<i class="fa-solid fa-check-circle text-success"></i>'
                                   : '<i class="fa-solid fa-times-circle text-danger"></i>';

            let optHtml = `
                <ol type="A">
                    <li ${q.correct_answer === q.option_a ? 'class="fw-bold text-success"' : ''}>${escapeHtml(q.option_a)}</li>
                    <li ${q.correct_answer === q.option_b ? 'class="fw-bold text-success"' : ''}>${escapeHtml(q.option_b)}</li>
                    <li ${q.correct_answer === q.option_c ? 'class="fw-bold text-success"' : ''}>${escapeHtml(q.option_c)}</li>
                    <li ${q.correct_answer === q.option_d ? 'class="fw-bold text-success"' : ''}>${escapeHtml(q.option_d)}</li>
                </ol>`;

            reviewHtml += `
                <div class="card p-4 mb-3 ${statusClass}">
                    <h5>${i+1}. ${escapeHtml(q.question)} ${icon}</h5>
                    ${optHtml}
                    <p class="mb-1 mt-2"><strong>Your Answer:</strong>
                        ${userAnswer ? escapeHtml(userAnswer) : '<em class="text-warning">Not answered</em>'}
                    </p>
                    <p class="mb-0"><strong>Correct Answer:</strong>
                        <span class="text-success">${escapeHtml(q.correct_answer)}</span>
                    </p>
                    ${hintsUsed[q.id] ? '<p class="text-warning small mt-1 mb-0"><i class="fa-solid fa-triangle-exclamation"></i> Hint penalty applied (−50%)</p>' : ''}
                    ${q.explanation && q.explanation !== '-' ? `<hr><p class="text-info mb-0"><strong>Explanation:</strong> ${escapeHtml(q.explanation)}</p>` : ''}
                </div>`;
        });

        // POST to server — server grades against DB answers (source of truth).
        // Submit URL/owner differ for a Program-Class-lesson quiz (see
        // ProgramClassQuizController) - the extra, unused field on whichever
        // path doesn't apply is simply ignored server-side.
        $.post(
            ownerType === 'program_class'
                ? "{{ $data['submitUrl'] ?? '' }}"
                : "{{ route('student-panel-homework.submit-interactive-quiz') }}",
            {
                _token:           '{{ csrf_token() }}',
                homework_id:      ownerType === 'homework' ? homeworkId : null,
                program_class_id: ownerType === 'program_class' ? homeworkId : null,
                answers:          userAnswers,
                hints:            hintsUsed,
            },
            function (response) {
                if (response.status === 'already_submitted') {
                    alert('This quiz was already submitted.');
                    window.location.href = backUrl;
                    return;
                }

                // Show results
                $('#quiz-container').addClass('d-none');
                $('#results-container').removeClass('d-none');
                $('#final-score').text(response.earned);
                $('#review-section').html(reviewHtml);
            }
        ).fail(function (xhr) {
            console.error(xhr.responseText);
            alert('Submission failed. Please check your connection and try again.');
            $('#btn-submit')
                .html('<i class="fa-solid fa-check-double"></i> Submit Quiz')
                .prop('disabled', false);
            startTimer(); // Resume timer so they can retry
        });
    }
</script>
@endif
@endpush
