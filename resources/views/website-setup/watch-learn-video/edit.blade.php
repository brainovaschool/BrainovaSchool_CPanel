@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('watch-learn-video.index') }}">Watch & Learn — Videos</a></li>
                        <li class="breadcrumb-item active">{{ ___('common.edit') }}</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('watch-learn-video.update', $data['item']->id) }}" method="post">
                    @csrf
                    @method('PUT')
                    @include('website-setup.watch-learn-video._form')
                </form>
            </div>
        </div>
    </div>
@endsection
