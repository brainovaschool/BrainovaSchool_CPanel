{{--
    Homework Evaluation Partial — loaded via AJAX into the evaluation modal.

    Variables available:
      $data['homework']  → Homework model
      $data['students']  → SessionClassStudent collection (with homeworkStudent relation)

    Tier 2 additions:
      • Feedback textarea per student (saved alongside marks)
      • Export CSV button in the header bar
--}}

{{-- Header: homework title, total marks, export button --}}
@php
    $hwIsActive = (int) ($data['homework']->status ?? 0) === \App\Enums\Status::ACTIVE;
    $hwIsQuiz = ($data['homework']->task_type ?? '') === 'quiz';
    $students = collect($data['students'] ?? []);
    $evSubmitted = $students->filter(fn ($r) => !empty($r->homeworkStudent))->count();
    $evNotSubmitted = max(0, $students->count() - $evSubmitted);
    $evNeedsMarks = $hwIsQuiz ? 0 : $students->filter(function ($r) {
        if (empty($r->homeworkStudent)) {
            return false;
        }

        return $r->homeworkStudent->marks === null;
    })->count();
    $evGraded = max(0, $evSubmitted - $evNeedsMarks);
@endphp

<div class="hw-ev-summary row g-2 mb-4" role="group" aria-label="Submission summary">
    <div class="col-6 col-md-3">
        <div class="hw-ev-summary-card hw-ev-summary-card--wait">
            <span class="hw-ev-summary-card__value">{{ $evNeedsMarks }}</span>
            <span class="hw-ev-summary-card__label">Need marks</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="hw-ev-summary-card hw-ev-summary-card--done">
            <span class="hw-ev-summary-card__value">{{ $evGraded }}</span>
            <span class="hw-ev-summary-card__label">Graded</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="hw-ev-summary-card hw-ev-summary-card--in">
            <span class="hw-ev-summary-card__value">{{ $evSubmitted }}</span>
            <span class="hw-ev-summary-card__label">Submitted</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="hw-ev-summary-card hw-ev-summary-card--out">
            <span class="hw-ev-summary-card__value">{{ $evNotSubmitted }}</span>
            <span class="hw-ev-summary-card__label">Not submitted</span>
        </div>
    </div>
</div>

@if($hwIsQuiz && $evSubmitted > 0)
<p class="hw-ev-action-hint alert alert-info py-2 px-3 small mb-3">
    <i class="fa-solid fa-robot me-1"></i>This quiz is <strong>auto-scored</strong> when students submit. Review scores below or export results — no manual grading needed.
</p>
@elseif($evNeedsMarks > 0)
<p class="hw-ev-action-hint alert alert-warning py-2 px-3 small mb-3">
    <i class="fa-solid fa-pen-to-square me-1"></i>
    <strong>{{ $evNeedsMarks }}</strong> student{{ $evNeedsMarks === 1 ? '' : 's' }} submitted work — enter marks in the orange rows below, then click <strong>Save grades</strong>.
</p>
@elseif($evSubmitted > 0)
<p class="hw-ev-action-hint alert alert-success py-2 px-3 small mb-3">
    <i class="fa-solid fa-circle-check me-1"></i>All submitted work has marks. You can still update feedback or marks, then save.
</p>
@else
<p class="hw-ev-action-hint alert alert-secondary py-2 px-3 small mb-3">
    <i class="fa-solid fa-inbox me-1"></i>No submissions yet. This screen will show students here when they hand in work.
</p>
@endif

<div class="d-flex align-items-start justify-content-between mb-4 pb-3 border-bottom flex-wrap gap-2">
    <div>
        <h6 class="mb-1 text-muted text-uppercase fw-bold" style="font-size:11px;letter-spacing:.08em">
            {{ ucfirst($data['homework']->task_type ?? 'homework') }}
        </h6>
        <h5 class="mb-1 fw-bold">{{ $data['homework']->title ?? 'Evaluation' }}</h5>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <small class="text-muted">
                {{ $data['homework']->subject->name ?? '' }} —
                Due: {{ $data['homework']->submission_date ?? '—' }}
            </small>
            @if($hwIsActive)
                <span class="badge bg-success ms-1">Active</span>
            @else
                <span class="badge bg-secondary ms-1">Inactive</span>
            @endif
        </div>
    </div>
    <div class="d-flex align-items-center gap-3 flex-wrap">
        {{-- Total marks badge so teacher always knows the ceiling while grading --}}
        <div class="text-end">
            <div class="text-muted" style="font-size:11px">Total Marks</div>
            <div class="fw-bold fs-4 text-primary">{{ $data['homework']->marks ?? '—' }}</div>
        </div>

        {{-- Tier 2 Feature C: Export all results as CSV --}}
        <a href="{{ route('homework.export-results', $data['homework']->id) }}"
           class="btn btn-sm btn-outline-secondary"
           title="Download results as CSV">
            <i class="fa-solid fa-download me-1"></i>Export CSV
        </a>

        {{-- Tier 2 Feature A: Quiz analytics (only for quizzes) --}}
        @if($data['homework']->task_type === 'quiz')
        <a href="{{ route('homework.quiz-analytics', $data['homework']->id) }}"
           class="btn btn-sm btn-outline-info"
           target="_blank"
           title="View per-question accuracy analytics">
            <i class="fa-solid fa-chart-bar me-1"></i>Analytics
        </a>
        @endif
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered role-table" style="border-collapse:collapse;border:2px solid #c7d2e0">
        <thead class="thead" style="background:#f0f4fa">
            <tr>
                <th style="border:1px solid #b8c5d6;padding:10px 12px;font-size:10.5px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;font-weight:700;white-space:nowrap">{{ ___('academic.admission_no') }}</th>
                <th style="border:1px solid #b8c5d6;padding:10px 12px;font-size:10.5px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;font-weight:700">{{ ___('academic.student_name') }}</th>
                <th style="border:1px solid #b8c5d6;padding:10px 12px;font-size:10.5px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;font-weight:700;white-space:nowrap">{{ ___('academic.roll_no') }}</th>
                <th style="border:1px solid #b8c5d6;padding:10px 12px;font-size:10.5px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;font-weight:700">Submission</th>
                <th style="border:1px solid #b8c5d6;padding:10px 12px;font-size:10.5px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;font-weight:700;white-space:nowrap">
                    Marks
                    <small class="text-muted fw-normal" style="font-size:10px">/ {{ $data['homework']->marks ?? '?' }}</small>
                </th>
                {{-- Tier 2 Feature F: Feedback column --}}
                <th style="border:1px solid #b8c5d6;padding:10px 12px;font-size:10.5px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;font-weight:700">
                    Feedback <small class="text-muted fw-normal" style="font-size:9px">(optional)</small>
                </th>
            </tr>
        </thead>
        <tbody class="tbody">
            @forelse($data['students'] as $row)
            @php
                $hasSubmission = !empty($row->homeworkStudent);
                $marksVal = $hasSubmission ? $row->homeworkStudent->marks : null;
                $needsMark = !$hwIsQuiz && $hasSubmission && $marksVal === null;
                $rowClass = $needsMark ? 'hw-ev-row--needs-mark' : ($hasSubmission ? 'hw-ev-row--graded' : 'hw-ev-row--missing');
            @endphp
            <tr class="{{ $rowClass }}" style="background:{{ $loop->even ? '#fafbff' : '#fff' }}">
                <td style="border:1px solid #e2e8f0;padding:10px 12px;font-size:13px;vertical-align:middle">{{ @$row->student->admission_no }}</td>
                <td style="border:1px solid #e2e8f0;padding:10px 12px;font-size:13px;vertical-align:middle;font-weight:600">{{ @$row->student->first_name }} {{ @$row->student->last_name }}</td>
                <td style="border:1px solid #e2e8f0;padding:10px 12px;font-size:13px;vertical-align:middle;text-align:center">{{ @$row->roll }}</td>

                <td style="border:1px solid #e2e8f0;padding:10px 12px;font-size:13px;vertical-align:middle">
                    @if($row->homeworkStudent)
                        @if($needsMark)
                            <span class="hw-ev-status hw-ev-status--wait">
                                <i class="fa-solid fa-pen-to-square"></i> Grade me
                            </span>
                        @else
                            <span class="hw-ev-status hw-ev-status--done">
                                <i class="fa-solid fa-check"></i> Graded
                            </span>
                        @endif
                        <div class="small text-muted mt-1">
                            <i class="fa-solid fa-calendar-check me-1"></i>
                            {{ $row->homeworkStudent->date ?? '—' }}
                        </div>

                        @if($data['homework']->task_type === 'quiz')
                            <span class="badge bg-info text-white mt-1">
                                <i class="fa-solid fa-robot me-1"></i>Quiz (auto-scored)
                            </span>
                        @elseif($row->homeworkStudent->homeworkUpload)
                            <a class="btn btn-sm ot-btn-primary radius_30px mt-1"
                               href="{{ url($row->homeworkStudent->homeworkUpload->path) }}"
                               target="_blank">
                                <i class="fa-solid fa-eye me-1"></i>View work
                            </a>
                        @else
                            <span class="badge bg-warning text-dark mt-1">No file attached</span>
                        @endif
                    @else
                        <span class="hw-ev-status hw-ev-status--missing">
                            <i class="fa-solid fa-clock"></i> Not submitted
                        </span>
                    @endif
                </td>

                <td style="border:1px solid #e2e8f0;padding:10px 12px;vertical-align:middle">
                    @if($row->homeworkStudent)
                        <div class="d-flex align-items-center gap-2">
                            <input type="number"
                                   class="form-control ot-input {{ $needsMark ? 'hw-mark-input--pending' : '' }}"
                                   style="max-width:85px"
                                   step="0.5"
                                   min="0"
                                   max="{{ $data['homework']->marks ?? 9999 }}"
                                   name="marks[]"
                                   value="{{ $row->homeworkStudent->marks ?? '' }}"
                                   placeholder="{{ $needsMark ? 'Enter' : '0' }}"
                                   @if($needsMark) aria-label="Marks required for {{ @$row->student->first_name }}" @endif
                                   required />
                            <input type="hidden" name="students[]" value="{{ $row->student_id }}" />
                            <small class="text-muted">/ {{ $data['homework']->marks ?? '?' }}</small>
                        </div>
                    @else
                        <span class="text-muted small">No submission</span>
                    @endif
                </td>

                {{-- Tier 2 Feature F: Teacher feedback stored in homework_students.feedback --}}
                <td style="border:1px solid #e2e8f0;padding:10px 12px;vertical-align:middle">
                    @if($row->homeworkStudent)
                        <textarea name="feedback[]"
                                  class="form-control"
                                  rows="2"
                                  style="min-width:180px;font-size:12.5px"
                                  placeholder="Optional feedback for this student...">{{ $row->homeworkStudent->feedback ?? '' }}</textarea>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center gray-color py-4" style="border:1px solid #e2e8f0">
                    <img src="{{ asset('images/no_data.svg') }}" alt="" width="80" class="mb-2 d-block mx-auto">
                    <p class="mb-0">{{ ___('common.no_data_available') }}</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
