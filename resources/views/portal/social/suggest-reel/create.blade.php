@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">Suggest a Reel</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('portal-social-suggest-reel.index') }}">Suggest a Reel</a></li>
                        <li class="breadcrumb-item">Add</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('portal-social-reels.store') }}" method="post">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Series Name</label>
                            <select name="series_name" class="nice-select niceSelect bordered_style wide">
                                <option value="">—</option>
                                @foreach ($data['series'] as $s)
                                    <option value="{{ $s->name }}" {{ old('series_name') === $s->name ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Topic</label>
                            <input type="text" name="title" class="form-control ot-input" value="{{ old('title') }}" required maxlength="150">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Hook</label>
                            <input type="text" name="hook" class="form-control ot-input" value="{{ old('hook') }}">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-lg ot-btn-primary">Add</button>
                        <a href="{{ route('portal-social-suggest-reel.index') }}" class="btn btn-lg btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
