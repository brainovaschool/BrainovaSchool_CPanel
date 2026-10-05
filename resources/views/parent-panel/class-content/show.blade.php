@extends('parent-panel.partials.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    @php $item = $data['item']; @endphp
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $item->title }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('parent-panel-class-content.index') }}">Class Content</a></li>
                        <li class="breadcrumb-item active">{{ $item->title }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card mb-3">
            <div class="card-body">
                <p class="text-secondary mb-1">
                    {{ optional($item->class)->name }}{{ $item->section ? ' - ' . optional($item->section)->name : '' }} — {{ optional($item->subject)->name }}
                </p>
                @if ($item->description)
                    <p class="mb-0">{{ $item->description }}</p>
                @endif
            </div>
        </div>

        @include('class-content._lessons-readonly', ['item' => $item])
    </div>
@endsection
