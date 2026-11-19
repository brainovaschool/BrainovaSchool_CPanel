<div class="p-3">
    <div class="row g-4">
        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Assessment &amp; Placement</h5>
                    @if (env('ASSESSMENT_SYSTEM_AUTH_SECRET'))
                        <a href="{{ route('assessment-system.launch') }}" target="_blank" rel="noopener" class="btn btn-sm ot-btn-primary">
                            Open Assessment System
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    @if ($assessmentReport)
                        <h6 class="fw-semibold mb-1">{{ $assessmentReport['assessmentTitle'] }}</h6>
                        @if ($assessmentReport['overallLabel'])
                            <span class="badge bg-success-subtle text-success mb-2">{{ $assessmentReport['overallLabel'] }}</span>
                        @endif
                        @if ($assessmentReport['recommendation'])
                            <p class="mb-2">{{ $assessmentReport['recommendation'] }}</p>
                        @endif
                        @if ($assessmentReport['finished'])
                            <p class="text-muted small mb-0">
                                Released {{ \Illuminate\Support\Carbon::createFromTimestampMs($assessmentReport['finished'])->diffForHumans() }}
                            </p>
                        @endif
                    @else
                        <p class="text-muted mb-0">No released assessment result yet for this student.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
