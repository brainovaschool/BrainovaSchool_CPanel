@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    @php $item = $data['item']; @endphp
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('program-class-coordinator.index') }}">Coordinator Review</a></li>
                        <li class="breadcrumb-item active">{{ $item->title }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                    <div>
                        <h4 class="mb-1">{{ $item->title }}</h4>
                        <p class="text-secondary mb-1">
                            {{ optional($item->program)->title }}
                            &middot; by {{ trim(optional($item->creator)->first_name . ' ' . optional($item->creator)->last_name) ?: '—' }}
                            @if ($item->submitted_at)
                                &middot; submitted {{ $item->submitted_at->diffForHumans() }}
                            @endif
                        </p>
                        @if ($item->description)
                            <p class="mb-0">{{ $item->description }}</p>
                        @endif
                    </div>
                    <div>@include('program-class._status-badge', ['status' => $item->review_status])</div>
                </div>

                @if ($item->coordinator_feedback)
                    <div class="alert alert-warning mt-2 mb-0">
                        <strong>Your previous feedback:</strong>
                        <p class="mb-0">{{ $item->coordinator_feedback }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card ot-card mb-3">
            <div class="card-body">
                @if ($item->video_url)
                    <p><i class="fa-solid fa-video me-1"></i> <a href="{{ $item->video_url }}" target="_blank" rel="noopener">Lesson video</a></p>
                @endif

                @if ($item->materials->isNotEmpty())
                    <h5 class="mt-2">Materials</h5>
                    <ul>
                        @foreach ($item->materials as $m)
                            <li><a href="{{ $m->downloadUrl() }}" target="_blank" rel="noopener">{{ $m->label }}</a></li>
                        @endforeach
                    </ul>
                @endif

                @if ($item->activities->isNotEmpty())
                    <h5 class="mt-2">Activities</h5>
                    <ul>
                        @foreach ($item->activities as $a)
                            <li><strong>{{ $a->title }}.</strong> {{ $a->description }}</li>
                        @endforeach
                    </ul>
                @endif

                @if ($item->outcomes->isNotEmpty())
                    <h5 class="mt-2">Learning outcomes</h5>
                    <ul>
                        @foreach ($item->outcomes as $o)
                            <li>{{ $o->outcome_text }}</li>
                        @endforeach
                    </ul>
                @endif

                @if ($item->hasProject())
                    <h5 class="mt-2">Project — {{ $item->project_title }}</h5>
                    <p>{{ $item->project_description }}</p>
                @endif

                @if ($item->hasQuiz())
                    <h5 class="mt-2">Quiz — {{ $item->quiz_title }}</h5>
                    <p>{{ $item->quiz_description }}</p>
                @endif
            </div>
        </div>

        @if (in_array($item->review_status, [\App\Models\WebsiteSetup\ProgramClass::SUBMITTED, \App\Models\WebsiteSetup\ProgramClass::CHANGES_REQUESTED]))
            <div class="card ot-card cc-decision-card">
                <div class="card-body">
                    <h5 class="mb-1"><i class="fa-solid fa-circle-check text-primary me-1"></i> Your decision</h5>
                    <p class="text-secondary mb-3" style="font-size:.85rem;">Checked everything? Approve it to make it live on Course Details, or kick it back to the teacher with a note on what to fix.</p>
                    <form action="{{ route('program-class-coordinator.decide', $item->id) }}" method="post">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Feedback for the teacher (optional, required if sending back)</label>
                            <textarea class="form-control ot-textarea @error('feedback') is-invalid @enderror" name="feedback" rows="3" maxlength="2000"
                                placeholder="What should be fixed or improved?">{{ old('feedback') }}</textarea>
                            @error('feedback')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" name="decision" value="approve" class="btn btn-lg ot-btn-primary">
                                <i class="fa-solid fa-check"></i> Approve
                            </button>
                            <button type="submit" name="decision" value="request_changes" class="btn btn-lg btn-outline-danger"
                                onclick="return confirm('Send this back to the teacher with your feedback?');">
                                <i class="fa-solid fa-rotate-left"></i> Request changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('css')
<style>
    .cc-decision-card { border: 1px solid #bfdbfe; background: linear-gradient(180deg, #f8fbff 0%, #fff 60px); }
</style>
@endpush
