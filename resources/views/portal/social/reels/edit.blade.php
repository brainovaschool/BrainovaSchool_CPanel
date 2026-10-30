@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">
                        {{ $data['title'] }}
                        @if ($data['reel']->code)
                            <span class="badge-basic-info-text">{{ $data['reel']->code }}</span>
                        @endif
                    </h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('portal-social-reels.index') }}">Reels</a></li>
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('portal-social-reels.update', $data['reel']->id) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('portal.social.reels._form', ['settings' => $data['settings'], 'reel' => $data['reel'], 'customFields' => $data['customFields'], 'isManager' => $data['isManager'], 'series' => $data['series']])
                    <div class="mt-3">
                        <button type="submit" class="btn btn-lg ot-btn-primary">Save</button>
                        <a href="{{ route('portal-social-reels.index') }}" class="btn btn-lg btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
