{{-- Read-only module detail for Coordinator/Admin review screens. Expects $item (ClassContentModule with lessons.materials/activities/outcomes eager loaded). --}}
<div class="card ot-card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h4 class="mb-1">{{ $item->title }}</h4>
                <p class="text-secondary mb-1">
                    {{ optional($item->class)->name }}{{ $item->section ? ' - ' . optional($item->section)->name : '' }} — {{ optional($item->subject)->name }}
                    &middot; by {{ trim(optional($item->creator)->first_name . ' ' . optional($item->creator)->last_name) ?: '—' }}
                </p>
                @if ($item->description)
                    <p class="mb-0">{{ $item->description }}</p>
                @endif
            </div>
            <div>@include('class-content._status-badge', ['status' => $item->review_status])</div>
        </div>
        @if ($item->coordinator_feedback)
            <div class="alert alert-warning mt-3 mb-0">
                <strong>Coordinator feedback:</strong> {{ $item->coordinator_feedback }}
                @if ($item->coordinator)
                    <span class="text-secondary">— {{ trim($item->coordinator->first_name . ' ' . $item->coordinator->last_name) }}@if($item->coordinator_reviewed_at), {{ $item->coordinator_reviewed_at->format('d M Y') }}@endif</span>
                @endif
            </div>
        @endif
    </div>
</div>

@include('class-content._lessons-readonly', ['item' => $item])
