@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <h4 class="bradecrumb-title mb-1">Academics Boost — Course Details</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('core-academics.overview') }}">Academics Boost</a></li>
                        <li class="breadcrumb-item">Course Details</li>
                    </ol>
                </div>
            </div>
        </div>

        @include('class-content._course-details-block')

    </div>
@endsection
