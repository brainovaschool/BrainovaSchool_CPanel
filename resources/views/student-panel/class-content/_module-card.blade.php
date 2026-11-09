{{-- Expects $module (ClassContentModule with lessons.activities eager loaded) and $map (the student's completions, see ClassContentCompletionRepository::mapForStudent()) --}}
@php
    $lessonsDone = $module->lessons->filter(fn ($l) => $l->isDoneFor($map))->count();
@endphp
<div class="cc-module-card">
    <div class="cc-module-card-head">
        <h5 class="mb-1">{{ $module->title }}</h5>
        <p class="text-secondary mb-0" style="font-size:.85rem;">
            {{ optional($module->class)->name }}{{ $module->section ? ' - ' . optional($module->section)->name : '' }} — {{ optional($module->subject)->name }}
            &middot; {{ $lessonsDone }}/{{ $module->lessons->count() }} lessons done
        </p>
    </div>

    <div class="cc-lesson-list">
        @foreach ($module->lessons as $lesson)
            @php $done = $lesson->isDoneFor($map); @endphp
            <a href="{{ route('student-panel-class-content.lesson', $lesson->id) }}" class="cc-lesson-row">
                <span class="cc-lesson-dot" style="background:{{ $done ? '#16a34a' : '#9aa2b1' }};"></span>
                <span class="cc-lesson-title">{{ $lesson->title }}</span>
                @if ($lesson->class_date)
                    <span class="cc-lesson-date">{{ \Carbon\Carbon::parse($lesson->class_date)->format('d M Y') }}</span>
                @endif
                @if ($lesson->activities->isNotEmpty())
                    <span class="cc-lesson-badge {{ $lesson->activities->every(fn ($a) => isset($map['activity:' . $a->id])) ? 'is-done' : '' }}">Activities</span>
                @endif
                @if ($lesson->hasProject())
                    <span class="cc-lesson-badge {{ isset($map['project:' . $lesson->id]) ? 'is-done' : '' }}">Project</span>
                @endif
                @if ($lesson->hasQuiz())
                    <span class="cc-lesson-badge {{ isset($map['quiz:' . $lesson->id]) ? 'is-done' : '' }}">Quiz</span>
                @endif
            </a>
        @endforeach
        @if ($module->lessons->isEmpty())
            <p class="text-secondary mb-0" style="font-size:.85rem;">No lessons in this module yet.</p>
        @endif
    </div>
</div>

@once
    @push('css')
    <style>
        .cc-module-card { background: #fff; border: 1px solid #e4e7ec; border-radius: 14px; padding: 16px 18px; margin-bottom: 12px; }
        .cc-lesson-list { margin-top: 10px; padding-top: 10px; border-top: 1px solid #edeef1; display: flex; flex-direction: column; gap: 2px; }
        .cc-lesson-row { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 8px 6px; border-radius: 8px; color: inherit; text-decoration: none; }
        .cc-lesson-row:hover { background: #f5f6f8; }
        .cc-lesson-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
        .cc-lesson-title { flex: 1; min-width: 200px; font-size: 13.5px; font-weight: 600; color: #1f2430; }
        .cc-lesson-date { font-size: 12px; color: #8b93a3; }
        .cc-lesson-badge { font-size: 10.5px; font-weight: 700; padding: 3px 8px; border-radius: 999px; background: #eef0f3; color: #5b6472; }
        .cc-lesson-badge.is-done { background: #dcfce7; color: #15803d; }
    </style>
    @endpush
@endonce
