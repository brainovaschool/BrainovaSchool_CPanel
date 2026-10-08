{{-- Read-only module detail for Coordinator/Admin review screens. Expects $item (ClassContentModule with lessons.materials/activities/outcomes eager loaded). --}}
<div class="card ot-card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <div>
                <h4 class="mb-1">{{ $item->title }}</h4>
                <p class="text-secondary mb-1">
                    {{ optional($item->class)->name }}{{ $item->section ? ' - ' . optional($item->section)->name : '' }} — {{ optional($item->subject)->name }}
                    &middot; by {{ trim(optional($item->creator)->first_name . ' ' . optional($item->creator)->last_name) ?: '—' }}
                    @if ($item->submitted_at)
                        &middot; submitted {{ $item->submitted_at->diffForHumans() }}
                    @endif
                </p>
                @if ($item->description)
                    <p class="mb-0">{{ $item->description }}</p>
                @endif
            </div>
            <div>@include('class-content._status-badge', ['status' => $item->review_status])</div>
        </div>

        @include('class-content._status-stepper', ['status' => $item->review_status])

        @include('class-content._feedback-panel', ['item' => $item])
    </div>
</div>

@include('class-content._lessons-readonly', ['item' => $item])
