@extends('student-panel.partials.master')

@section('title')
{{ $data['title'] }}
@endsection

@section('content')
<div class="page-content">
    <div class="sp-cat-page">
        <h4>Progress &amp; Report</h4>
        @include('student-panel.course-category._progress-report-block')
    </div>
</div>
@endsection
