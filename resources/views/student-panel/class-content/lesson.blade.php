@extends('student-panel.partials.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    @php
        $lesson = $data['lesson'];
        $map    = $data['map'];
        $tab    = $data['tab'];
        $lessonDone  = isset($map['lesson:' . $lesson->id]);
        $projectDone = isset($map['project:' . $lesson->id]);
        $quizDone    = isset($map['quiz:' . $lesson->id]);
    @endphp
    <div class="page-content">

        <a href="{{ route('student-panel-class-content.index') }}" class="cc-back-link"><i class="fa-solid fa-arrow-left"></i> Back to Class Content</a>

        <h1 class="cc-lesson-h1">{{ $lesson->title }}</h1>
        <p class="text-secondary mb-3" style="font-size:13px;">
            {{ $lesson->module->title }}
            @if ($lesson->class_date) &middot; {{ \Carbon\Carbon::parse($lesson->class_date)->format('d M Y') }} @endif
        </p>

        @if ($lesson->hasProject() || $lesson->hasQuiz())
            <div class="row g-3 mb-3">
                @if ($lesson->hasProject())
                    <div class="col-md-6">
                        <div class="cc-strip-card">
                            <div class="cc-strip-icon" style="background:#fdf0e1;color:#b45309;"><i class="fa-solid fa-trophy"></i></div>
                            <div class="flex-grow-1">
                                <div class="cc-strip-label">Project</div>
                                <div class="cc-strip-title">{{ $lesson->project_title }}</div>
                                @if ($lesson->project_description)<p class="mb-1" style="font-size:12.5px;color:#5b6472;">{{ $lesson->project_description }}</p>@endif
                                @if ($lesson->project_link_url)<a href="{{ $lesson->project_link_url }}" target="_blank" rel="noopener" style="font-size:12.5px;">Open link &rarr;</a>@endif
                            </div>
                            @if ($projectDone)
                                <span class="badge-basic-success-text">Submitted</span>
                            @else
                                <form action="{{ route('student-panel-class-content.complete') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="type" value="project">
                                    <input type="hidden" name="id" value="{{ $lesson->id }}">
                                    <button type="submit" class="btn btn-sm ot-btn-primary">Submit</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif
                @if ($lesson->hasQuiz())
                    <div class="col-md-6">
                        <div class="cc-strip-card">
                            <div class="cc-strip-icon" style="background:#fae8ff;color:#c026d3;"><i class="fa-solid fa-circle-question"></i></div>
                            <div class="flex-grow-1">
                                <div class="cc-strip-label">Quiz</div>
                                <div class="cc-strip-title">{{ $lesson->quiz_title }}</div>
                                @if ($lesson->quiz_description)<p class="mb-1" style="font-size:12.5px;color:#5b6472;">{{ $lesson->quiz_description }}</p>@endif
                                @if ($lesson->quiz_link_url)<a href="{{ $lesson->quiz_link_url }}" target="_blank" rel="noopener" style="font-size:12.5px;">Open link &rarr;</a>@endif
                            </div>
                            @if ($quizDone)
                                <span class="badge-basic-success-text">Completed</span>
                            @else
                                <form action="{{ route('student-panel-class-content.complete') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="type" value="quiz">
                                    <input type="hidden" name="id" value="{{ $lesson->id }}">
                                    <button type="submit" class="btn btn-sm ot-btn-primary">Complete</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="cc-tabs">
            <a href="{{ route('student-panel-class-content.lesson', ['id' => $lesson->id, 'tab' => 'overview']) }}" class="cc-tab {{ $tab === 'overview' ? 'is-active' : '' }}">Overview</a>
            <a href="{{ route('student-panel-class-content.lesson', ['id' => $lesson->id, 'tab' => 'activities']) }}" class="cc-tab {{ $tab === 'activities' ? 'is-active' : '' }}">Activities</a>
            <a href="{{ route('student-panel-class-content.lesson', ['id' => $lesson->id, 'tab' => 'outcome']) }}" class="cc-tab {{ $tab === 'outcome' ? 'is-active' : '' }}">Outcome</a>
        </div>

        <div class="card ot-card">
            <div class="card-body">

                @if ($tab === 'overview')
                    @if ($lesson->description)
                        <p class="mb-3" style="font-size:14px;line-height:1.6;">{{ $lesson->description }}</p>
                    @endif

                    @if ($lesson->materials->isNotEmpty())
                        <div class="cc-section">
                            <div class="cc-section-label">Materials</div>
                            <ul class="cc-link-list">
                                @foreach ($lesson->materials as $mat)
                                    <li><a href="{{ $mat->url }}" target="_blank" rel="noopener">{{ $mat->label }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($lesson->video_url)
                        <div class="cc-section" style="max-width:560px;">
                            @include('class-content._video-embed', ['video' => $lesson])
                        </div>
                    @endif

                    <div class="mt-3">
                        @if ($lessonDone)
                            <span class="badge-basic-success-text">&#10003; Lesson marked done</span>
                        @else
                            <form action="{{ route('student-panel-class-content.complete') }}" method="post">
                                @csrf
                                <input type="hidden" name="type" value="lesson">
                                <input type="hidden" name="id" value="{{ $lesson->id }}">
                                <button type="submit" class="btn ot-btn-primary">Mark this lesson as done</button>
                            </form>
                        @endif
                    </div>
                @endif

                @if ($tab === 'activities')
                    @forelse ($lesson->activities as $activity)
                        @php $activityDone = isset($map['activity:' . $activity->id]); @endphp
                        <div class="cc-activity-card">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                <strong style="font-size:14.5px;">{{ $activity->title }}</strong>
                                <span class="{{ $activityDone ? 'badge-basic-success-text' : 'badge-basic-secondary-text' }}">{{ $activityDone ? 'Completed' : 'Not submitted' }}</span>
                            </div>
                            @if ($activity->description)
                                <p class="mb-2" style="font-size:13px;color:#5b6472;">{{ $activity->description }}</p>
                            @endif
                            @if ($activity->video_url)
                                <div class="mb-2" style="max-width:420px;">
                                    @include('class-content._video-embed', ['video' => $activity])
                                </div>
                            @endif
                            <div class="d-flex align-items-center gap-3">
                                @if ($activity->link_url)
                                    <a href="{{ $activity->link_url }}" target="_blank" rel="noopener" style="font-size:13px;">Open link &rarr;</a>
                                @endif
                                @unless ($activityDone)
                                    <form action="{{ route('student-panel-class-content.complete') }}" method="post">
                                        @csrf
                                        <input type="hidden" name="type" value="activity">
                                        <input type="hidden" name="id" value="{{ $activity->id }}">
                                        <button type="submit" class="btn btn-sm ot-btn-primary">Submit activity</button>
                                    </form>
                                @endunless
                            </div>
                        </div>
                    @empty
                        <p class="text-secondary mb-0">No activities for this lesson.</p>
                    @endforelse
                @endif

                @if ($tab === 'outcome')
                    @forelse ($lesson->outcomes as $outcome)
                        <div class="cc-outcome-row"><i class="fa-solid fa-circle-check"></i> {{ $outcome->outcome_text }}</div>
                    @empty
                        <p class="text-secondary mb-0">No learning outcomes listed for this lesson.</p>
                    @endforelse
                @endif

            </div>
        </div>
    </div>
@endsection

@push('css')
<style>
    .cc-back-link { font-size: 13px; font-weight: 600; color: #5b6472; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 14px; text-decoration: none; }
    .cc-lesson-h1 { margin: 0 0 4px; font-size: 24px; font-weight: 700; }
    .cc-strip-card { background: #fff; border: 1px solid #e4e7ec; border-radius: 12px; padding: 14px 16px; display: flex; align-items: flex-start; gap: 12px; height: 100%; }
    .cc-strip-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .cc-strip-label { font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #8b93a3; }
    .cc-strip-title { font-size: 13.5px; font-weight: 600; margin-bottom: 2px; }

    .cc-tabs { display: inline-flex; background: #e9ebef; border-radius: 10px; padding: 4px; margin-bottom: 16px; }
    .cc-tab { border: none; border-radius: 8px; padding: 8px 18px; font-size: 13px; font-weight: 600; color: #5b6472; text-decoration: none; }
    .cc-tab.is-active { background: #fff; color: #1f2430; box-shadow: 0 1px 3px rgba(15,27,61,0.12); }

    .cc-section { margin-top: 14px; padding-top: 14px; border-top: 1px dashed #e2e8f0; }
    .cc-section-label { font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #8b93a3; margin-bottom: 8px; }
    .cc-link-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 5px; }
    .cc-link-list a { font-size: 13.5px; }

    .cc-activity-card { border: 1px solid #edeef1; border-radius: 12px; padding: 14px 16px; margin-bottom: 12px; }
    .cc-outcome-row { display: flex; align-items: flex-start; gap: 10px; font-size: 13.5px; color: #334058; line-height: 1.6; }
    .cc-outcome-row i { color: #15803d; margin-top: 3px; font-size: 12px; }
</style>
@endpush
