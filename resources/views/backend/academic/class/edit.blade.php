@extends('backend.master')

@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        {{-- bradecrumb Area S t a r t --}}
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h1>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"> {{ ___('common.home') }} </a></li>
                        <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">{{ $data['title'] }}</a></li>
                        <li class="breadcrumb-item">{{ ___('common.edit') }}</li>

                    </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}

        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('classes.update', @$data['class']->id) }}" enctype="multipart/form-data" method="post"
                    id="visitForm">
                    @csrf
                    @method('PUT')
                    <div class="row mb-3">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="exampleDataList" class="form-label ">{{ ___('common.name') }} <span
                                            class="fillable">*</span></label>
                                    <input class="form-control ot-input @error('name') is-invalid @enderror" name="name"
                                        value="{{ old('name',@$data['class']->name) }}" list="datalistOptions" id="exampleDataList"
                                        placeholder="{{ ___('common.enter_name') }}">
                                    @error('name')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    {{-- Status  --}}
                                    <label for="validationServer04" class="form-label">{{ ___('common.status') }} <span class="fillable">*</span></label>

                                    <select class="nice-select niceSelect bordered_style wide @error('status') is-invalid @enderror"
                                    name="status" id="validationServer04"
                                    aria-describedby="validationServer04Feedback">

                                        <option value="{{ App\Enums\Status::ACTIVE }}"
                                            {{ @$data['class']->status == App\Enums\Status::ACTIVE ? 'selected' : '' }}>
                                            {{ ___('common.active') }}</option>
                                        <option value="{{ App\Enums\Status::INACTIVE }}"
                                            {{ @$data['class']->status == App\Enums\Status::INACTIVE ? 'selected' : '' }}>
                                            {{ ___('common.inactive') }}
                                        </option>
                                    </select>
                                </div>
                                @error('status')
                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div class="col-md-6 mt-3">
                                    <label for="sortOrderInput" class="form-label">{{ ___('common.Serial') }}</label>
                                    <input class="form-control ot-input @error('sort_order') is-invalid @enderror" name="sort_order"
                                        id="sortOrderInput" type="number" min="0"
                                        value="{{ old('sort_order', @$data['class']->sort_order ?? 0) }}">
                                    <small class="text-secondary">Controls where this class appears in the list — lower numbers come first.</small>
                                    @error('sort_order')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-12 mt-24">
                                    <div class="text-end">
                                        <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i>
                                            </span>{{ ___('common.update') }}</button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
@endsection
