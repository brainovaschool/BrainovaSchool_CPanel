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
                        <li class="breadcrumb-item"><a href="{{ route('class-content-admin.index') }}">Approve Class Content</a></li>
                        <li class="breadcrumb-item active">{{ $item->title }}</li>
                    </ol>
                </div>
            </div>
        </div>

        @include('class-content._module-detail', ['item' => $item])

        @if ($item->review_status !== \App\Models\ClassContent\ClassContentModule::APPROVED)
            <div class="card ot-card">
                <div class="card-body">
                    <h5 class="mb-3">Your decision</h5>
                    <form action="{{ route('class-content-admin.decide', $item->id) }}" method="post">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Feedback (optional, shown back to the coordinator/teacher)</label>
                            <textarea class="form-control ot-textarea @error('feedback') is-invalid @enderror" name="feedback" rows="3" maxlength="2000"
                                placeholder="Anything that needs fixing before this can go live">{{ old('feedback') }}</textarea>
                            @error('feedback')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" name="decision" value="approve" class="btn btn-lg ot-btn-primary"
                                onclick="return confirm('Approve this module? It will become visible to students and parents right away.');">
                                <i class="fa-solid fa-check"></i> Approve &amp; publish
                            </button>
                            <button type="submit" name="decision" value="back_to_coordinator" class="btn btn-lg btn-outline-secondary">
                                <i class="fa-solid fa-rotate-left"></i> Back to coordinator
                            </button>
                            <button type="submit" name="decision" value="request_changes" class="btn btn-lg btn-outline-danger"
                                onclick="return confirm('Send this all the way back to the teacher with your feedback?');">
                                <i class="fa-solid fa-rotate-left"></i> Send back to teacher
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <div class="alert alert-success">This module is approved and live for students.</div>
        @endif
    </div>
@endsection
