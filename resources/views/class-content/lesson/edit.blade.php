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
                        <li class="breadcrumb-item"><a href="{{ route('class-content-module.index') }}">Class Content</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('class-content-module.lessons', $data['module']->id) }}">{{ $data['module']->title }}</a></li>
                        <li class="breadcrumb-item active">{{ ___('common.edit') }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('class-content-module.lessons.update', [$data['module']->id, $data['item']->id]) }}" method="post">
                    @csrf
                    @method('PUT')
                    @include('class-content.lesson._form')
                </form>
            </div>
        </div>
    </div>
@endsection
