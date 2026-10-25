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
                            <small class="text-secondary">Kea will always open the first scene with this line, word for word — you don't need to repeat it in any scene's dialogue.</small>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control ot-textarea" placeholder="What should this reel teach or cover?">{{ old('description') }}</textarea>
                        </div>

                        <div class="col-12 col-md-4 mb-3">
                            <label class="form-label">Target total length (seconds)</label>
                            <input type="number" name="target_duration_seconds" min="5" max="600" class="form-control ot-input" value="{{ old('target_duration_seconds', 40) }}">
                            <small class="text-secondary">Used as a guide for AI scene generation. Each scene is still capped at 10 seconds (Google Flow's limit).</small>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Characters involved in this reel</label>
                            <div class="d-flex flex-wrap gap-3">
                                @forelse ($data['characters'] as $character)
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="character_ids[]" value="{{ $character->id }}" id="char{{ $character->id }}"
                                            {{ in_array($character->id, old('character_ids', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="char{{ $character->id }}">{{ $character->name }}</label>
                                    </div>
                                @empty
                                    <p class="text-secondary mb-0">No characters set up yet — add some in Reel Characters first.</p>
                                @endforelse
                            </div>
                            <small class="text-secondary">This is the roster AI can pick from when auto-generating scenes — you can still choose any of these per scene either way.</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-lg ot-btn-primary">Create &amp; Add Scenes</button>
                    <a href="{{ route('portal-social-reel-studio.index') }}" class="btn btn-lg btn-outline-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
@endsection
