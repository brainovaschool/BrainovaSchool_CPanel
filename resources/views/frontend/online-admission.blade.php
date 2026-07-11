@extends('frontend.master')
@section('title')
    {{ ___('frontend.online_admission') }}
@endsection

@push('css')
    <style>
        .ot-btn-common,
        .ot-dropdown-btn,
        .ot-btn-primary,
        .ot-btn-success,
        .ot-btn-danger,
        .ot-btn-warning,
        .ot-btn-info {
            background: linear-gradient(130.57deg, #392C7D -0.48%, #314CAD 71.79%) !important;
            color: #ffffff !important;
            font-weight: 500;
            font-size: 13px;
            text-transform: capitalize;
        }
    </style>
@endpush

@section('main')

    <!-- bradcam::start  -->
    <div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-xl-5">
                    <div class="breadcam_wrap text-center">
                        <h3>{{ ___('frontend.online_admission') }}</h3>
                        <div class="custom_breadcam">
                            <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                            <a href="#" class="breadcrumb-item">{{ ___('frontend.online_admission') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- bradcam::end  -->

    <!-- ADMISSION::START  -->
    <div class="search_result_area section_padding">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-10">
                    <div class="search_result_box mb_30">
                        @if (session('message'))
                            <div class="section__title mb_50">
                                <h5 class="mb-0 text-success text-center">{{ session('message') }}</h5>
                            </div>
                        @elseif (session('error'))
                            <div class="section__title mb_50">
                                <h5 class="mb-0 text-danger text-center">{{ session('error') }}</h5>
                            </div>
                        @else
                            <div class="section__title mb_50">
                                <h5 class="mb-0 text-warning text-center">
                                    {{ ___('frontend.please_fill_out_the_form_for_admission_guidance_and_information') }}.
                                </h5>
                            </div>
                        @endif

                        <form class="form-area contact-form" action="{{ route('frontend.online-admission.store') }}"
                            id="admission" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-xl-6">
                                    <label class="primary_label2">Student Name <span class="text-danger">*</span></label>
                                    <input name="student_name" placeholder="Enter student name"
                                        value="{{ old('student_name') }}"
                                        class="form-control ot-input mb_30 @error('student_name') is-invalid @enderror"
                                        required type="text">
                                    @error('student_name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-xl-6">
                                    <label class="primary_label2">Student Age <span class="text-danger">*</span></label>
                                    <input name="student_age" placeholder="Enter student age"
                                        value="{{ old('student_age') }}"
                                        class="form-control ot-input mb_30 @error('student_age') is-invalid @enderror"
                                        required type="text">
                                    @error('student_age')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-xl-6">
                                    <label class="primary_label2">Student Class <span class="text-danger">*</span></label>
                                    <input name="student_class" placeholder="Enter student class"
                                        value="{{ old('student_class') }}"
                                        class="form-control ot-input mb_30 @error('student_class') is-invalid @enderror"
                                        required type="text">
                                    @error('student_class')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-xl-6">
                                    <label class="primary_label2">Parent Name <span class="text-danger">*</span></label>
                                    <input name="parent_name" placeholder="Enter parent name"
                                        value="{{ old('parent_name') }}"
                                        class="form-control ot-input mb_30 @error('parent_name') is-invalid @enderror"
                                        required type="text">
                                    @error('parent_name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-xl-6">
                                    <label class="primary_label2">Parent Email Address <span class="text-danger">*</span></label>
                                    <input name="parent_email" placeholder="Enter parent email address"
                                        value="{{ old('parent_email') }}"
                                        pattern="[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{1,63}$"
                                        class="form-control ot-input mb_30 @error('parent_email') is-invalid @enderror"
                                        required type="email">
                                    @error('parent_email')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-xl-6">
                                    <label class="primary_label2">Parent Phone Number <span class="text-danger">*</span></label>
                                    <input name="parent_phone" placeholder="Enter parent phone number"
                                        value="{{ old('parent_phone') }}"
                                        class="form-control ot-input mb_30 @error('parent_phone') is-invalid @enderror"
                                        required type="text">
                                    @error('parent_phone')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-xl-6 mb_24">
                                    <label class="primary_label2" for="program">Program <span class="text-danger">*</span></label>
                                    <select class="theme_select wide @error('program') is-invalid @enderror" name="program" id="program" required>
                                        <option value="" data-display="Select">Select program</option>
                                        @foreach (config('online_admission.programs') as $program)
                                            <option value="{{ $program }}" {{ old('program') == $program ? 'selected' : '' }}>
                                                {{ $program }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('program')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-xl-12 text-left d-flex">
                                    <button type="submit"
                                        class="theme_btn2 submit-btn text-center d-flex align-items-center m-0 w-100 justify-content-center text-uppercase large_btn">{{ ___('frontend.Submit') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ADMISSION::END  -->

@endsection
