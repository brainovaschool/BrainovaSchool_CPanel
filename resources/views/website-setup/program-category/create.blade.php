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
                        <li class="breadcrumb-item"><a href="{{ route('program-category.index') }}">{{ ___('settings.program_categories') }}</a></li>
                        <li class="breadcrumb-item active">{{ ___('common.add_new') }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('program-category.store') }}" enctype="multipart/form-data" method="post">
                    @csrf
                    @include('website-setup.program-category._form')
                </form>
            </div>
        </div>
    </div>
@endsection
