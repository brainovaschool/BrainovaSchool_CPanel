{{-- Read-only lessons list for student/parent-facing pages. Expects $item (an approved ClassContentModule with lessons.materials/activities/outcomes eager loaded). No review-workflow info here — that's internal. --}}
@forelse ($item->lessons as $lesson)
    <div class="card ot-card mb-3">
        <div class="card-body">
            <h5 class="mb-1">{{ $lesson->sort_order }}. {{ $lesson->title }}</h5>
            @if ($lesson->class_date)
                <p class="text-secondary mb-2" style="font-size:.85rem;">{{ \Carbon\Carbon::parse($lesson->class_date)->format('d M Y') }}</p>
            @endif
            @if ($lesson->description)
                <p>{{ $lesson->description }}</p>
            @endif

            @if ($lesson->video_url)
                <div class="mb-3" style="max-width:480px;">
                    @include('class-content._video-embed', ['video' => $lesson])
                </div>
            @endif

            @if ($lesson->materials->isNotEmpty())
                <h6 class="mt-3">Materials</h6>
                <ul class="mb-2">
                    @foreach ($lesson->materials as $mat)
                        <li><a href="{{ $mat->url }}" target="_blank" rel="noopener">{{ $mat->label }}</a></li>
                    @endforeach
                </ul>
            @endif

            @if ($lesson->activities->isNotEmpty())
                <h6 class="mt-3">Activities</h6>
                @foreach ($lesson->activities as $act)
                    <div class="mb-2">
                        <strong>{{ $act->title }}</strong>
                        @if ($act->description)
                            <p class="mb-1">{{ $act->description }}</p>
                        @endif
                        @if ($act->link_url)
                            <a href="{{ $act->link_url }}" target="_blank" rel="noopener">Open link</a>
                        @endif
                        @if ($act->video_url)
                            <div class="mt-2" style="max-width:420px;">
                                @include('class-content._video-embed', ['video' => $act])
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif

            @if ($lesson->outcomes->isNotEmpty())
                <h6 class="mt-3">Learning Outcomes</h6>
                <ul class="mb-0">
                    @foreach ($lesson->outcomes as $outcome)
                        <li>{{ $outcome->outcome_text }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@empty
    <div class="card ot-card">
        <div class="card-body text-center text-secondary">No lessons added yet.</div>
    </div>
@endforelse
