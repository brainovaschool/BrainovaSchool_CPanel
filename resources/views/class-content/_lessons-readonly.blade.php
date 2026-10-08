{{-- Read-only lessons list — shared by review screens and the student/parent-facing pages. Expects $item (a ClassContentModule with lessons.materials/activities/outcomes eager loaded). No review-workflow info here — that's internal. --}}
@forelse ($item->lessons as $lesson)
    <div class="card ot-card mb-3 cc-lesson-card">
        <div class="card-body">
            <div class="cc-lesson-head">
                <span class="cc-lesson-num">{{ $lesson->sort_order }}</span>
                <div class="cc-lesson-head__text">
                    <h5 class="mb-0">{{ $lesson->title }}</h5>
                    @if ($lesson->class_date)
                        <span class="cc-lesson-date"><i class="fa-regular fa-calendar"></i> {{ \Carbon\Carbon::parse($lesson->class_date)->format('d M Y') }}</span>
                    @endif
                </div>
            </div>

            @if ($lesson->description)
                <p class="mt-2 mb-0">{{ $lesson->description }}</p>
            @endif

            @if ($lesson->video_url)
                <div class="mt-3" style="max-width:480px;">
                    @include('class-content._video-embed', ['video' => $lesson])
                </div>
            @endif

            @if ($lesson->materials->isNotEmpty())
                <div class="cc-section">
                    <div class="cc-section__title"><i class="fa-solid fa-paperclip"></i> Materials</div>
                    <ul class="cc-link-list">
                        @foreach ($lesson->materials as $mat)
                            <li><a href="{{ $mat->url }}" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> {{ $mat->label }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($lesson->activities->isNotEmpty())
                <div class="cc-section">
                    <div class="cc-section__title"><i class="fa-solid fa-pen-ruler"></i> Activities</div>
                    @foreach ($lesson->activities as $act)
                        <div class="cc-activity">
                            <strong>{{ $act->title }}</strong>
                            @if ($act->description)
                                <p class="mb-1">{{ $act->description }}</p>
                            @endif
                            @if ($act->link_url)
                                <a href="{{ $act->link_url }}" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open link</a>
                            @endif
                            @if ($act->video_url)
                                <div class="mt-2" style="max-width:420px;">
                                    @include('class-content._video-embed', ['video' => $act])
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($lesson->outcomes->isNotEmpty())
                <div class="cc-section">
                    <div class="cc-section__title"><i class="fa-solid fa-bullseye"></i> Learning Outcomes</div>
                    <ul class="cc-outcome-list">
                        @foreach ($lesson->outcomes as $outcome)
                            <li><i class="fa-solid fa-check"></i> {{ $outcome->outcome_text }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@empty
    <div class="card ot-card">
        <div class="card-body text-center text-secondary py-4">
            <i class="fa-regular fa-folder-open mb-2" style="font-size:1.6rem;opacity:.4;display:block;"></i>
            No lessons added yet.
        </div>
    </div>
@endforelse

@once
    @push('css')
        <style>
            .cc-lesson-card { border-left: 3px solid #2563eb22; }
            .cc-lesson-head { display: flex; align-items: center; gap: 12px; }
            .cc-lesson-num {
                width: 30px; height: 30px; border-radius: 50%; background: #2563eb; color: #fff; font-weight: 700; font-size: 13px;
                display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            }
            .cc-lesson-head__text { min-width: 0; }
            .cc-lesson-date { font-size: 12px; color: #94a3b8; }
            .cc-section { margin-top: 16px; padding-top: 12px; border-top: 1px dashed #e2e8f0; }
            .cc-section__title { font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #64748b; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
            .cc-link-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 5px; }
            .cc-link-list a { font-size: 13.5px; text-decoration: none; }
            .cc-link-list a:hover { text-decoration: underline; }
            .cc-activity { padding: 10px 12px; background: #f8fafc; border-radius: 8px; margin-bottom: 8px; }
            .cc-activity:last-child { margin-bottom: 0; }
            .cc-activity a { font-size: 12.5px; }
            .cc-outcome-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 6px; }
            .cc-outcome-list li { font-size: 13.5px; display: flex; align-items: flex-start; gap: 8px; }
            .cc-outcome-list li i { color: #10b981; margin-top: 3px; font-size: 11px; }
        </style>
    @endpush
@endonce
