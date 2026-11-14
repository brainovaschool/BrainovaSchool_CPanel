@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <h4 class="bradecrumb-title mb-1">Homeschooling — Progress &amp; Report</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('homeschooling.overview') }}">Homeschooling</a></li>
                        <li class="breadcrumb-item">Progress &amp; Report</li>
                    </ol>
                </div>
            </div>
        </div>

        @include('class-content._progress-report-block')

    </div>
@endsection
