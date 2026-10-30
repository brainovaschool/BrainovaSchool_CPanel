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
                    <div class="row">
                        <div class="col-12 col-md-8 mb-3">
                            <label class="form-label">Reel title <span class="fillable">*</span></label>
                            <input type="text" name="title" class="form-control ot-input" value="{{ old('title') }}" placeholder="e.g. Why do leaves change color?" required>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <label class="form-label">Orientation <span class="fillable">*</span></label>
                            <select name="format" class="form-control ot-input">
                                @foreach (\App\Models\Portal\ReelProject::FORMATS as $value => $label)
                                    <option value="{{ $value }}" {{ old('format', 'vertical') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Hook <span class="fillable">*</span></label>
                            <textarea name="hook" rows="2" class="form-control ot-textarea" placeholder="The opening line — write it exactly as Kea should say it, e.g. &quot;Brainbot, have you ever wondered why leaves change color?&quot;">{{ old('hook') }}</textarea>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control ot-textarea" placeholder="What should this reel teach or cover?">{{ old('description') }}</textarea>
                        </div>

                        <div class="col-12 col-md-4 mb-3">
                            <label class="form-label">Target total length (seconds)</label>
                            <input type="number" name="target_duration_seconds" min="5" max="600" class="form-control ot-input" value="{{ old('target_duration_seconds', 40) }}">
                            <small class="text-secondary">You'll set a scene length (max 10s) on the next screen — that's what splits this into scene boxes.</small>
                        </div>

                    </div>

                    <button type="submit" class="btn btn-lg ot-btn-primary">Create &amp; Add Scenes</button>
                    <a href="{{ route('portal-social-reel-studio.index') }}" class="btn btn-lg btn-outline-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
@endsection
