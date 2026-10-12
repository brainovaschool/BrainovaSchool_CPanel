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
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                            <li class="breadcrumb-item" aria-current="page"><a
                                    href="{{ route('notice-board.index') }}">{{ ___('common.notice_board') }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ $data['title'] }}</li>
                        </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}
        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('notice-board.store') }}" enctype="multipart/form-data" method="post"
                    id="marksheet">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="exampleDataList" class="form-label ">{{ ___('common.title') }} <span
                                            class="fillable">*</span> </label>
                                    <input class="form-control ot-input @error('title') is-invalid @enderror" name="title"
                                        value="{{ old('title') }}" list="datalistOptions" id="exampleDataList"
                                        placeholder="{{ ___('account.Notice Title') }}">
                                    @error('title')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="exampleDataList" class="form-label ">{{ ___('common.publish_date') }} <span
                                            class="fillable">*</span></label>
                                    <input class="form-control ot-input @error('publish_date') is-invalid @enderror"
                                        name="publish_date" type="datetime-local" list="datalistOptions"
                                        id="exampleDataList" type="text" placeholder="{{ ___('common.publish_date') }}"
                                        value="{{ old('publish_date') }}">
                                    @error('publish_date')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="exampleDataList" class="form-label ">{{ ___('common.date') }} <span
                                            class="fillable">*</span></label>
                                    <input class="form-control ot-input @error('date') is-invalid @enderror" name="date"
                                        type="date" list="datalistOptions" id="exampleDataList" type="text"
                                        placeholder="{{ ___('common.date') }}" value="{{ old('date') }}">
                                    @error('date')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="departmentId" class="form-label">{{ ___('common.Department') }}</label>
                                    <select
                                        class="nice-select niceSelect bordered_style wide @error('department_id') is-invalid @enderror"
                                        name="department_id" id="departmentId">
                                        <option value="">{{ ___('student_info.select Department') }}</option>
                                        @foreach ($data['departments'] ?? [] as $id => $name)
                                            <option {{ old('department_id') == $id ? 'selected' : '' }}
                                                value="{{ $id }}">{{ $name }}
                                            </option>
                                        @endforeach
                                        </option>
                                    </select>


                                    @error('department_id')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>


                                <div class="col-md-12 mb-5 mt-2">
                                    <label class="form-label">{{ ___('common.visible_to') }}<span
                                            class="fillable"></span></label>
                                    <div
                                        class="input-check-radio visbleSection d-flex align-items-center flex-wrap @error('sections') is-invalid @enderror">
                                        @foreach ($data['roles'] as $item)
                                            <div class="form-check me-3">
                                                <input class="form-check-input visible-to-checkbox" type="checkbox"
                                                    name="visible_to[]" value="{{ $item->id }}"
                                                    id="flexCheckDefault-{{ $item->id }}" />
                                                <label class="form-check-label ps-2"
                                                    for="flexCheckDefault-{{ $item->id }}">{{ $item->name }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>


                                <div class="col-md-4 mb-5 classDiv d-none">
                                    <label for="exampleDataList" class="form-label ">{{ ___('common.Class') }} <span
                                            class="fillable"></span></label>
                                    <select id="getSections"
                                        class="class nice-select niceSelect bordered_style wide @error('class') is-invalid @enderror"
                                        name="class">
                                        <option value="">{{ ___('student_info.select_class') }} </option>

                                        @foreach ($data['classes'] as $item)
                                            <option
                                                {{ old('class', @$data['student']->session_class_student->class->id) == $item->id ? 'selected' : '' }}
                                                value="{{ $item->class->id }}">{{ $item->class->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('class')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-5 sectionDiv d-none">
                                    <label for="exampleDataList" class="form-label ">{{ ___('common.Section') }} </label>
                                    <select
                                        class="sections section nice-select niceSelect bordered_style wide @error('section') is-invalid @enderror"
                                        name="section">
                                        <option value="">{{ ___('student_info.select_section') }} </option>
                                        <option value="all_section">{{ ___('student_info.All Sections') }}</option>

                                    </select>
                                    @error('section')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-5 studentDiv d-none">
                                    <label for="exampleDataList" class="form-label ">{{ ___('common.Student') }} <span
                                            class="fillable"></span></label>
                                    <select
                                        class="students nice-select niceSelect bordered_style wide @error('student') is-invalid @enderror"
                                        name="student">
                                        <option value="">{{ ___('student_info.select_student') }}</option>

                                    </select>
                                </div>


                                <div class="col-md-4">
                                    <label for="exampleDataList" class="form-label ">{{ ___('common.attachment') }} <span
                                            class="fillable"></span></label>
                                    <div class="ot_fileUploader left-side mb-3">
                                        <input class="form-control" type="text"
                                            placeholder="{{ ___('common.attachment') }}" readonly=""
                                            id="placeholder">
                                        <button class="primary-btn-small-input" type="button">
                                            <label class="btn btn-lg ot-btn-primary"
                                                for="fileBrouse">{{ ___('common.browse') }}</label>
                                            <input type="file" class="d-none form-control" accept="image/*"
                                                name="attachment" id="fileBrouse">
                                        </button>
                                    </div>
                                    <small class="text-muted">Recommended size: 1600×900px (landscape, 16:9). The full image is shown — no cropping.</small>
                                </div>

                                <div class="col-md-4 mb-3">

                                    <label for="validationServer04" class="form-label">{{ ___('common.status') }} <span
                                            class="fillable">*</span></label>
                                    <select
                                        class="nice-select niceSelect bordered_style wide @error('status') is-invalid @enderror"
                                        name="status" id="validationServer04"
                                        aria-describedby="validationServer04Feedback">
                                        <option value="{{ App\Enums\Status::ACTIVE }}">{{ ___('common.active') }}
                                        </option>
                                        <option value="{{ App\Enums\Status::INACTIVE }}">{{ ___('common.inactive') }}
                                        </option>
                                    </select>

                                    @error('status')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ ___('academic.is_visible_web') }} ?<span
                                            class="fillable"></span></label>
                                    <div
                                        class="input-check-radio academic-section @error('sections') is-invalid @enderror">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="is_visible_web"
                                                value="1" id="flexCheckDefault-1" />
                                            <label class="form-check-label ps-2 pe-5"
                                                for="flexCheckDefault-1">{{ ___('academic.yes') }}</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="is_visible_web"
                                                value="0" checked id="flexCheckDefault-0" />
                                            <label class="form-check-label ps-2 pe-5"
                                                for="flexCheckDefault-0">{{ ___('academic.no') }}</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="summernote" class="form-label ">{{ ___('account.description') }} <span
                                            class="fillable">*</span></label>
                                    <textarea class="form-control ot-textarea @error('description') is-invalid @enderror" name="description"
                                        list="datalistOptions" id="summernote" placeholder="{{ ___('account.enter_description') }}">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>



                                <div class="col-md-12 mt-24">
                                    <div class="text-end">
                                        <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i>
                                            </span>{{ ___('common.submit') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            // Listen for changes on checkboxes with the class `.visible-to-checkbox`
            $('.visible-to-checkbox').on('change', function() {
                // Get all selected checkbox values
                let selectedValues = $('.visible-to-checkbox:checked')
                    .map(function() {
                        return $(this).val(); // Extract the value
                    })
                    .get(); // Convert to an array

                // Check if `6` or `7` exists in the selected values
                if (selectedValues.includes('6') || selectedValues.includes('7')) {
                    console.log('Values contain 6 or 7.');
                    $('.classDiv, .sectionDiv, .studentDiv').removeClass('d-none');
                } else {
                    $('.classDiv, .sectionDiv, .studentDiv').addClass('d-none');
                }
            });
        });
    </script>
@endpush
