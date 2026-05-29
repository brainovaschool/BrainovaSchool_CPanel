@php
  $pendingMarkIds = collect($homeworkIdsPendingMarks ?? [])->flip()->all();
  $perHomeworkStats = $perHomeworkStats ?? [];
@endphp
@forelse($homeworks as $row)
@php
  $hwId = (int) $row->id;
  $stats = $perHomeworkStats[$hwId] ?? ['submitted' => 0, 'awaiting_marks' => 0, 'graded' => 0];
  $submittedCount = (int) ($stats['submitted'] ?? 0);
  $awaitingCount = (int) ($stats['awaiting_marks'] ?? 0);
  $gradedCount = (int) ($stats['graded'] ?? 0);
  $needsMarking = $awaitingCount > 0 || isset($pendingMarkIds[$hwId]);
  $typeMap = ['homework'=>'hw','quiz'=>'quiz','project'=>'project','activity'=>'activity','game'=>'game','assignment'=>'assignment'];
  $typeKey = $typeMap[$row->task_type ?? 'homework'] ?? 'hw';
  $pillClass = match ($typeKey) {
    'quiz' => 'hw-type-pill--quiz',
    'hw' => 'hw-type-pill--hw',
    'project' => 'hw-type-pill--project',
    'activity' => 'hw-type-pill--activity',
    'game' => 'hw-type-pill--game',
    'assignment' => 'hw-type-pill--assignment',
    default => 'hw-type-pill--default',
  };
  $isOverdue = $row->submission_date && \Carbon\Carbon::parse($row->submission_date)->isPast();
  $dueSort = ($row->submission_date ?? null)
    ? \Carbon\Carbon::parse($row->submission_date)->format('Y-m-d')
    : '';
  $marksRaw = isset($row->marks) ? trim((string) $row->marks) : '';
  $marksSort = preg_match('/^-?\d+(?:\.\d+)?$/', $marksRaw) ? $marksRaw : '';
  $sortTitle = \Illuminate\Support\Str::lower(trim(strip_tags((string) ($row->title ?? ''))));
  $sortClassSection = \Illuminate\Support\Str::lower(trim((string) (($row->class->name ?? '').' '.($row->section->name ?? ''))));
  $sortSubject = \Illuminate\Support\Str::lower(trim((string) ($row->subject->name ?? '')));
  $sortType = (string) ($row->task_type ?? '');
@endphp
<tr data-hw-row-id="{{ $row->id }}" @if($needsMarking) class="hw-row-needs-marking" @endif>
  <td class="serial hw-quest-num">{{ $loop->iteration }}</td>
  <td data-sort="{{ e($sortTitle) }}">
    <strong>{{ $row->title ?? '—' }}</strong>
    @if($needsMarking)
      <span class="hw-awaiting-pill" title="{{ $awaitingCount }} submission(s) need marks">{{ $awaitingCount }} to grade</span>
    @endif
  </td>
  <td data-sort="{{ e($sortClassSection) }}">
    {{ $row->class->name ?? '—' }} / {{ $row->section->name ?? '—' }}
  </td>
  <td data-sort="{{ e($sortSubject) }}">{{ $row->subject->name ?? '—' }}</td>
  <td data-sort="{{ e($sortType) }}">
    <span class="hw-type-pill {{ $pillClass }}">{{ $row->task_type ?? '—' }}</span>
  </td>
  <td data-sort="{{ e($dueSort) }}">
    {{ $row->submission_date ?? '—' }}
    @if((int) ($row->status ?? 0) === \App\Enums\Status::ACTIVE)
      <span class="badge bg-success ms-1">Active</span>
    @else
      <span class="badge bg-secondary ms-1">Inactive</span>
    @endif
    @if($isOverdue)
      <span class="badge bg-warning text-dark ms-1">Late</span>
    @endif
  </td>
  <td class="text-end" data-sort="{{ e($marksSort) }}">{{ $row->marks ?? '—' }}</td>
  <td class="hw-submission-cell">
    @if($submittedCount === 0)
      <span class="hw-sub-mini hw-sub-mini--none" title="No student has submitted yet">
        <i class="fa-solid fa-inbox"></i> None yet
      </span>
    @else
      <div class="hw-sub-mini-stack">
        <span class="hw-sub-mini hw-sub-mini--in" title="Students who submitted">
          <i class="fa-solid fa-paper-plane"></i> {{ $submittedCount }} in
        </span>
        @if($awaitingCount > 0)
          <span class="hw-sub-mini hw-sub-mini--wait" title="Submitted but marks missing">
            <i class="fa-solid fa-hourglass-half"></i> {{ $awaitingCount }} waiting
          </span>
        @endif
        @if($gradedCount > 0)
          <span class="hw-sub-mini hw-sub-mini--done" title="Marks already saved">
            <i class="fa-solid fa-check"></i> {{ $gradedCount }} done
          </span>
        @endif
      </div>
    @endif
  </td>
  <td class="action">
    @if(hasPermission('homework_update'))
      <div class="d-flex flex-wrap gap-1 justify-content-end align-items-center">
        <a href="{{ route('homework.edit', $row->id) }}"
           class="btn btn-sm btn-outline-primary"
           title="{{ ___('common.edit') }}">
          <i class="fa-solid fa-pencil"></i>
        </a>
        <button type="button"
                class="btn btn-sm {{ $awaitingCount > 0 ? 'hw-btn-grade hw-btn-grade--pending' : 'btn-outline-secondary hw-btn-grade' }}"
                title="{{ $awaitingCount > 0 ? 'Grade '.$awaitingCount.' submission(s)' : 'View or grade submissions' }}"
                onclick="openEval({{ $row->id }})">
          <i class="fa-solid fa-pen-to-square me-1"></i>Grade
          @if($awaitingCount > 0)
            <span class="hw-grade-count">{{ $awaitingCount }}</span>
          @endif
        </button>
      </div>
    @endif
  </td>
</tr>
@empty
<tr>
  <td colspan="9" class="text-center text-muted py-4">
    <i class="fa-solid fa-inbox d-block mb-2 fs-3 opacity-25"></i>
    No homework found for the selected filters.
  </td>
</tr>
@endforelse
