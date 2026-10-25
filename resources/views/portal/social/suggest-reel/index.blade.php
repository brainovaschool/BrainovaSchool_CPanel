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
                        <li class="breadcrumb-item">Suggest a Reel</li>
                    </ol>
                </div>
                <div class="col-sm-4 text-sm-end">
                    <a href="{{ route('portal-social-suggest-reel.create') }}" class="btn ot-btn-primary">+ Add</a>
                </div>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-body">
                <p class="text-secondary mb-0">Got an idea for a reel or topic? Click "+ Add", pick the series, and give the topic and hook — everything else is filled in for you.</p>
            </div>
        </div>
    </div>
@endsection
