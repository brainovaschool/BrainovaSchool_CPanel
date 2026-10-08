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
                        <li class="breadcrumb-item"><a href="{{ route('class-content-coordinator.index') }}">Coordinator Review</a></li>
                        <li class="breadcrumb-item active">{{ $item->title }}</li>
                    </ol>
                </div>
            </div>
        </div>

        @include('class-content._module-detail', ['item' => $item])

        @if (in_array($item->review_status, [\App\Models\ClassContent\ClassContentModule::SUBMITTED, \App\Models\ClassContent\ClassContentModule::CHANGES_REQUESTED]))
            <div class="card ot-card cc-decision-card">
                <div class="card-body">
                    <h5 class="mb-1"><i class="fa-solid fa-circle-check text-primary me-1"></i> Your decision</h5>
                    <p class="text-secondary mb-3" style="font-size:.85rem;">Checked everything? Send it on to admin, or kick it back to the teacher with a note on what to fix.</p>
                    <form action="{{ route('class-content-coordinator.decide', $item->id) }}" method="post">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Feedback for the teacher (optional, required if sending back)</label>
                            <textarea class="form-control ot-textarea @error('feedback') is-invalid @enderror" name="feedback" rows="3" maxlength="2000"
                                placeholder="What should be fixed or improved before this goes to admin?">{{ old('feedback') }}</textarea>
                            @error('feedback')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" name="decision" value="send_to_admin" class="btn btn-lg ot-btn-primary">
                                <i class="fa-solid fa-paper-plane"></i> Send to admin
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
