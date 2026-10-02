@extends('backend.master')

@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        {{-- @dump($data['sections']->toArray()) --}}
        {{-- bradecrumb Area S t a r t --}}
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('sections.index') }}">{{ ___('settings.sections') }}</a>
                        </li>
                        <li class="breadcrumb-item">{{ ___('common.edit') }}</li>
                    </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}
        {{-- @dump($data['sections']->toArray()) --}}

        <div class="card ot-card">
            <div class="card-title">
                <h4>{{ ___('website.Key') }}: {{ @$data['sections']->key }}</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('sections.update', @$data['sections']->id) }}" enctype="multipart/form-data"
                    method="post">
                    @csrf
                    @method('PUT')
                    <div class="row mb-3">
                        <div class="col-lg-12">
                            <div class="row">
                                {{-- Name Field --}}
                                @if (@$data['sections']->name)
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">{{ ___('common.name') }}</label>
                                        <input class="form-control ot-input @error('name') is-invalid @enderror"
                                            name="name"
                                            value="{{ old('name', @$data['sections']->defaultTranslate->name ?? @$data['sections']->name) }}"
                                            placeholder="{{ ___('common.enter_name') }}">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif

                                {{-- Description Field --}}
                                @if (@$data['sections']->description)
                                    <div class="col-md-12 mb-3">
                                        <label for="description" class="form-label">{{ ___('common.Description') }}</label>
                                        <textarea class="form-control ot-textarea @error('description') is-invalid @enderror" name="description"
                                            placeholder="{{ ___('common.Enter description') }}">{{ old('description', @$data['sections']->defaultTranslate->description ?? @$data['sections']->description) }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif

                                {{-- Image Upload Field --}}
                                @if (@$data['sections']->upload_id)
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ ___('common.image') }}
                                            @if (@$data['sections']->key == 'statement')
                                                {{ ___('common.(580 x 465 px)') }}
                                            @elseif(@$data['sections']->key == 'study_at')
                                                {{ ___('common.(1920 x 615 px)') }}
                                            @elseif(@$data['sections']->key == 'explore')
                                                {{ ___('common.(700 x 500 px)') }}
                                            @endif
                                        </label>
                                        <div class="ot_fileUploader left-side mb-3">
                                            <input class="form-control" type="text"
                                                placeholder="{{ ___('common.image') }}" readonly>
                                            <button class="primary-btn-small-input" type="button">
                                                <label class="btn btn-lg ot-btn-primary"
                                                    for="fileBrouse">{{ ___('common.browse') }}</label>
                                                <input type="file" class="d-none" name="image" accept="image/*"
                                                    id="fileBrouse">
                                            </button>
                                        </div>
                                    </div>
                                @endif
                                @if (@$data['sections']->key == 'social_links')
                                    {{-- -------------------------------- Social link --------------------------------- --}}
                                    <div class="col-md-12 mt-5">
                                        <div class="text-end">
                                            <button type="button" onclick="addSocialLink()"
                                                class="btn ot-btn-primary"><span><i class="fa-solid fa-add"></i>
                                                </span>{{ ___('common.add') }}</button>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <table class="table" id="social_links">
                                            <thead></thead>
                                            <tbody>
                                                @foreach (@$data['sections']->data as $key => $item)
                                                    <tr>
                                                        <td>
                                                            <label class="form-label">{{ ___('common.name') }}</label>
                                                            <input class="form-control ot-input mb-4"
                                                                value="{{ $item['name'] }}" name="data[name][]"
                                                                placeholder="{{ ___('common.Enter name') }}">
                                                        </td>
                                                        <td>
                                                            <label class="form-label">{{ ___('common.Icon') }}</label>
                                                            <input class="form-control ot-input mb-4"
                                                                value="{{ $item['icon'] }}" name="data[icon][]"
                                                                placeholder="{{ ___('common.Enter icon') }}">
                                                        </td>
                                                        <td>
                                                            <label class="form-label">{{ ___('common.Link') }}</label>
                                                            <div class="d-flex align-items-center mb-4">
                                                                <input class="form-control ot-input mr-2"
                                                                    value="{{ $item['link'] }}" name="data[link][]"
                                                                    placeholder="{{ ___('common.Enter link') }}">
                                                                <button class="drax_close_icon mark_distribution_close"
                                                                    onclick="removeRow(this)">
                                                                    <i class="fa-solid fa-xmark"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                                {{-- Statement Section --}}
                                @if (@$data['sections']->key == 'statement')
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">YouTube video (optional)</label>
                                        <input class="form-control ot-input" name="video_url"
                                            value="{{ old('video_url', @$data['sections']->video_url) }}"
                                            placeholder="https://www.youtube.com/watch?v=...">
                                        <small class="text-secondary">When set, this replaces the image on the right with this video — landscape works best. Leave blank to keep showing the image.</small>
                                    </div>
                                    <div class="col-md-12">
                                        <h3 class="mt-3">{{ ___('common.Details') }}</h3>
                                        <div class="row">
                                            @php
                                                $sectionData =
                                                    @$data['sections']->defaultTranslate->data ??
                                                    @$data['sections']->data;
                                                if (is_string($sectionData)) {
                                                    $sectionData = json_decode($sectionData, true);
                                                }
                                                $sectionData = $sectionData ?: [];
                                            @endphp
                                            @foreach ($sectionData as $key => $item)
                                                <div class="col-md-6 mb-18">
                                                    <div class="mb-18">
                                                        <label class="form-label">{{ ___('common.title') }}</label>
                                                        <input class="form-control ot-input mb-2"
                                                            value="{{ $item['title'] }}" name="data[title][]"
                                                            placeholder="{{ ___('common.Enter title') }}">
                                                    </div>
                                                    <div>
                                                        <label class="form-label">{{ ___('common.Description') }}</label>
                                                        <textarea class="form-control ot-textarea mt-0" name="data[description][]"
                                                            placeholder="{{ ___('common.Enter description') }}">{{ $item['description'] }}</textarea>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Study At Section --}}
                                @if (@$data['sections']->key == 'study_at')
                                    <div class="col-md-12">
                                        <h3 class="mt-3">{{ ___('common.Details') }}</h3>
                                        <div class="row">
                                            @php
                                                $sectionData =
                                                    @$data['sections']->defaultTranslate->data ??
                                                    @$data['sections']->data;
                                                if (is_string($sectionData)) {
                                                    $sectionData = json_decode($sectionData, true);
                                                }
                                                $sectionData = $sectionData ?: [];
                                            @endphp
                                            @foreach ($sectionData as $key => $item)
                                                <div class="col-6 mb-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">{{ ___('common.Icon') }}
                                                            {{ ___('common.(70 x 70 px)') }}</label>
                                                        <div class="ot_fileUploader left-side mb-3">
                                                            <input class="form-control" type="text"
                                                                placeholder="{{ ___('common.Select icon') }}" readonly
                                                                id="placeholder{{ $key + 2 }}">
                                                            <button class="primary-btn-small-input" type="button">
                                                                <label class="btn btn-lg ot-btn-primary"
                                                                    for="fileBrouse{{ $key + 2 }}">{{ ___('common.browse') }}</label>
                                                                <input type="file" class="d-none form-control"
                                                                    name="data[icon][{{ $key }}]"
                                                                    accept="image/*" id="fileBrouse{{ $key + 2 }}">
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">{{ ___('common.title') }}</label>
                                                        <input class="form-control ot-input" value="{{ $item['title'] }}"
                                                            name="data[title][]"
                                                            placeholder="{{ ___('common.Enter title') }}">
                                                    </div>
                                                    <div>
                                                        <label class="form-label">{{ ___('common.Description') }}</label>
                                                        <textarea class="form-control ot-textarea" name="data[description][]"
                                                            placeholder="{{ ___('common.Enter description') }}">{{ $item['description'] }}</textarea>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Explore Section --}}
                                @if (@$data['sections']->key == 'explore')
                                    <div class="col-md-12">
                                        <h3 class="mt-3">{{ ___('common.Details') }}</h3>
                                        <div class="row">
                                            @php
                                                $sectionData =
                                                    @$data['sections']->defaultTranslate->data ??
                                                    @$data['sections']->data;
                                                if (is_string($sectionData)) {
                                                    $sectionData = json_decode($sectionData, true);
                                                }
                                                $sectionData = $sectionData ?: [];
                                            @endphp
                                            @foreach ($sectionData as $key => $item)
                                                <div class="col-6 mb-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">{{ ___('common.Tab') }}</label>
                                                        <input class="form-control ot-input" value="{{ $item['tab'] }}"
                                                            name="data[tab][]"
                                                            placeholder="{{ ___('common.Enter tab') }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">{{ ___('common.title') }}</label>
                                                        <input class="form-control ot-input" value="{{ $item['title'] }}"
                                                            name="data[title][]"
                                                            placeholder="{{ ___('common.Enter title') }}">
                                                    </div>
                                                    <div>
                                                        <label class="form-label">{{ ___('common.Description') }}</label>
                                                        <textarea class="form-control ot-textarea" name="data[description][]"
                                                            placeholder="{{ ___('common.Enter description') }}">{{ $item['description'] }}</textarea>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Why Choose Us Section --}}
                                @if (@$data['sections']->key == 'why_choose_us')
                                    <div class="col-md-12">
                                        <h3 class="mt-3">{{ ___('common.Details') }}</h3>
                                        <div class="text-end mb-3">
                                            <button type="button" onclick="addChooseUs()" class="btn ot-btn-primary">
                                                <span><i class="fa-solid fa-add"></i></span>{{ ___('common.add') }}
                                            </button>
                                        </div>
                                        <div class="row" id="choose_us_container">
                                            @php
                                                $sectionData =
                                                    @$data['sections']->defaultTranslate->data ??
                                                    @$data['sections']->data;
                                                if (is_string($sectionData)) {
                                                    $sectionData = json_decode($sectionData, true);
                                                }
                                                $sectionData = $sectionData ?: [];
                                            @endphp
                                            @foreach ($sectionData as $key => $item)
                                                <div class="col-md-6 mb-3">
                                                    <div class="d-flex">
                                                        <input class="form-control ot-input" value="{{ $item }}"
                                                            name="data[]" placeholder="{{ ___('common.Enter text') }}">
                                                        <button type="button" class="btn btn-danger ms-2"
                                                            onclick="removeChooseUs(this)">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Academic Curriculum Section --}}
                                @if (@$data['sections']->key == 'academic_curriculum')
                                    <div class="col-md-12">
                                        <h3 class="mt-3">{{ ___('common.Details') }}</h3>
                                        <div class="text-end mb-3">
                                            <button type="button" onclick="addAcademicCurriculum()"
                                                class="btn ot-btn-primary">
                                                <span><i class="fa-solid fa-add"></i></span>{{ ___('common.add') }}
                                            </button>
                                        </div>
                                        <div class="row" id="academic_curriculum_container">
                                            @php
                                                $sectionData =
                                                    @$data['sections']->defaultTranslate->data ??
                                                    @$data['sections']->data;
                                                if (is_string($sectionData)) {
                                                    $sectionData = json_decode($sectionData, true);
                                                }
                                                $sectionData = $sectionData ?: [];
                                            @endphp
                                            @foreach ($sectionData as $key => $item)
                                                <div class="col-md-6 mb-3">
                                                    <div class="d-flex">
                                                        <input class="form-control ot-input" value="{{ $item }}"
                                                            name="data[]" placeholder="{{ ___('common.Enter text') }}">
                                                        <button type="button" class="btn btn-danger ms-2"
                                                            onclick="removeAcademicCurriculum(this)">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Submit Button --}}
                                <div class="col-md-12 mt-24">
                                    <div class="text-end">
                                        <button class="btn btn-lg ot-btn-primary">
                                            <span><i class="fa-solid fa-save"></i></span>
                                            {{ ___('common.update') }}
                                        </button>
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
