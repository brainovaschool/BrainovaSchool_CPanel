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
                        <li class="breadcrumb-item"><a href="{{ route('portal-social-reel-characters.index') }}">Reel Characters</a></li>
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-body">
                <p class="text-secondary">These instructions are included in <strong>every</strong> scene prompt, for every reel — style, educational objectives, dialogue standards, scene continuity, and anything else that should always apply.</p>
                <form action="{{ route('portal-social-reel-settings.update') }}" method="post">
                    @csrf
                    <textarea name="reel_global_instructions" rows="12" class="form-control ot-textarea"
                        placeholder="e.g. Keep the tone warm and encouraging. Every reel should teach one clear idea. Scenes should feel like one continuous story, not disconnected clips...">{{ old('reel_global_instructions', $data['globalInstructions']) }}</textarea>
                    <button type="submit" class="btn btn-lg ot-btn-primary mt-3">Save</button>
                </form>
            </div>
        </div>
    </div>
@endsection
