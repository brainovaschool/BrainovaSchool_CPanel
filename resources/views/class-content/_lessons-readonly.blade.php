{{-- Read-only lessons list — shared by review screens and the student/parent-facing pages. Expects $item (a ClassContentModule with lessons.materials/activities/outcomes eager loaded). No review-workflow info here — that's internal. --}}
@forelse ($item->lessons as $lesson)
    @include('class-content._lesson-card', ['lesson' => $lesson])
@empty
    <div class="card ot-card">
        <div class="card-body text-center text-secondary py-4">
            <i class="fa-regular fa-folder-open mb-2" style="font-size:1.6rem;opacity:.4;display:block;"></i>
            No lessons added yet.
        </div>
    </div>
@endforelse
