@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('portal-social-reel-studio.index') }}">Reel Studio</a></li>
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('portal-social-reel-studio.store') }}" method="post">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Reel title <span class="fillable">*</span></label>
                        <input type="text" name="title" class="form-control ot-input" value="{{ old('title') }}" placeholder="e.g. Why do leaves change color?" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Topic</label>
                        <textarea name="topic" rows="3" class="form-control ot-textarea" placeholder="What should this reel teach?">{{ old('topic') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-lg ot-btn-primary">Create &amp; Add Scenes</button>
                    <a href="{{ route('portal-social-reel-studio.index') }}" class="btn btn-lg btn-outline-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
@endsection
