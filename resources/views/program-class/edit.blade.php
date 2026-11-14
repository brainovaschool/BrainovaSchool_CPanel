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
                        <li class="breadcrumb-item active">{{ $data['program']->title }}</li>
                    </ol>
                </div>
                @if ($data['item']->isEditableByTeacher())
                    <div class="col-sm-6 text-sm-end">
                        <form action="{{ route('program-class.submit', $data['item']->id) }}" method="post" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary" onclick="return confirm('Submit this lesson for review? You won\'t be able to edit it until a coordinator reviews it.');">
                                <i class="fa-solid fa-paper-plane"></i> Submit for review
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('program-class.update', $data['item']->id) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('program-class._form')
                </form>
            </div>
        </div>
    </div>
@endsection
