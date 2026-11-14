@extends('student-panel.partials.master')

@section('title')
{{ $data['title'] }}
@endsection

@section('content')
<div class="page-content">
    <div class="sp-cat-page">
        @include('student-panel.course-category._course-details-block')
    </div>
</div>
@endsection
