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
                            <li class="breadcrumb-item"><a href="{{ route('news.index') }}">{{ $data['title'] }}</a></li>
                            <li class="breadcrumb-item">{{ ___('common.edit') }}</li>
                        </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}

        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('news.update', @$data['news']->id) }}" enctype="multipart/form-data" method="post"
                    id="visitForm">
                    @csrf
                    @method('PUT')
                    <div class="row mb-3">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="exampleDataList" class="form-label ">{{ ___('common.title') }} <span
                                            class="fillable">*</span></label>
                                    <input class="form-control ot-input @error('title') is-invalid @enderror" name="title"
                                        value="{{ old('title', @$data['news']->defaultTranslate->title ?? @$data['news']->title) }}"
                                        list="datalistOptions" id="exampleDataList"
                                        placeholder="{{ ___('common.enter_title') }}">
                                    @error('title')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="exampleDataList" class="form-label">{{ ___('common.image') }}
                                        {{ ___('common.(690 x 460 px)') }}</label>
                                    <div class="ot_fileUploader left-side mb-3">
                                        <input class="form-control" type="text" placeholder="{{ ___('common.image') }}"
                                            readonly="" id="placeholder">
                                        <button class="primary-btn-small-input" type="button">
                                            <label class="btn btn-lg ot-btn-primary"
                                                for="fileBrouse">{{ ___('common.browse') }}</label>
                                            <input type="file" class="d-none form-control" name="image"
                                                accept="image/*" id="fileBrouse">
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="exampleDataList" class="form-label ">{{ ___('common.Date') }} <span
                                            class="fillable">*</span></label>
                                    <input class="form-control ot-input @error('date') is-invalid @enderror" name="date"
                                        value="{{ old('date', @$data['news']->date) }}" list="datalistOptions"
                                        id="exampleDataList" type="date" placeholder="{{ ___('common.enter_date') }}">
                                    @error('date')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="exampleDataList" class="form-label ">{{ ___('common.publish_date') }} <span
                                            class="fillable">*</span></label>
                                    <input class="form-control ot-input @error('publish_date') is-invalid @enderror"
                                        name="publish_date" value="{{ old('publish_date', @$data['news']->publish_date) }}"
                                        list="datalistOptions" id="exampleDataList" type="date"
                                        placeholder="{{ ___('common.enter_publish_date') }}">
                                    @error('publish_date')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="validationServer04" class="form-label">{{ ___('common.status') }} <span
                                            class="fillable">*</span></label>
                                    <select
                                        class="nice-select niceSelect bordered_style wide @error('status') is-invalid @enderror"
                                        name="status" id="validationServer04"
                                        aria-describedby="validationServer04Feedback">
                                        <option value="{{ App\Enums\Status::ACTIVE }}"
                                            {{ @$data['news']->status == App\Enums\Status::ACTIVE ? 'selected' : '' }}>
                                            {{ ___('common.active') }}</option>
                                        <option value="{{ App\Enums\Status::INACTIVE }}"
                                            {{ @$data['news']->status == App\Enums\Status::INACTIVE ? 'selected' : '' }}>
                                            {{ ___('common.inactive') }}</option>
                                    </select>
                                    @error('status')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ ___('settings.post_type') }} <span class="fillable">*</span></label>
                                    @php $newsType = old('type', @$data['news']->type ?: 'news'); @endphp
                                    <select class="nice-select niceSelect bordered_style wide" name="type">
                                        <option value="news" {{ $newsType === 'news' ? 'selected' : '' }}>{{ ___('settings.news') }}</option>
                                        <option value="blog" {{ $newsType === 'blog' ? 'selected' : '' }}>{{ ___('settings.blog') }}</option>
                                    </select>
                                    <small class="text-secondary">{{ ___('settings.post_type_note') }}</small>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="exampleDataList" class="form-label">{{ ___('common.Description') }}</label>
                                    <textarea id="summernote" class="form-control ot-textarea @error('description') is-invalid @enderror" name="description"
                                        list="datalistOptions" id="exampleDataList" placeholder="{{ ___('common.Enter description') }}">{{ old('description', @$data['news']->defaultTranslate->description ?? @$data['news']->description) }}</textarea>
                                    @error('description')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
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
                </form>
            </div>
        </div>
    </div>
@endsection
