@extends('frontend.master')
@section('title')
    Demo Class
@endsection

@section('main')

    <div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-xl-5">
                    <div class="breadcam_wrap text-center">
                        <h3>Demo Class</h3>
                        <div class="custom_breadcam">
                            <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                            <a href="#" class="breadcrumb-item">Demo Class</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="search_result_area section_padding">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-9">
                    <div class="search_result_box mb_30">
                        @if (session('message'))
                            <div class="section__title mb_40">
                                <h5 class="mb-0 text-success text-center">{{ session('message') }}</h5>
                            </div>
                        @else
                            <div class="section__title mb_40 text-center">
                                <h3 class="mb-2">Welcome to Brainova</h3>
                                <p class="mb-0">Preparing your child for life — not just for tests.</p>
                            </div>
                        @endif

                        <form class="form-area" action="{{ route('frontend.book-free-trial.store') }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-xl-6">
                                    <label class="primary_label2">Student name <span class="text-danger">*</span></label>
                                    <input name="student_name" value="{{ old('student_name') }}" required type="text"
                                        class="form-control ot-input mb_30 @error('student_name') is-invalid @enderror"
                                        placeholder="Student's full name">
                                    @error('student_name')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-xl-6">
                                    <label class="primary_label2">Father's name <span class="text-danger">*</span></label>
                                    <input name="father_name" value="{{ old('father_name') }}" required type="text"
                                        class="form-control ot-input mb_30 @error('father_name') is-invalid @enderror"
                                        placeholder="Father's full name">
                                    @error('father_name')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-xl-6">
                                    <label class="primary_label2">WhatsApp number <span class="text-danger">*</span></label>
                                    <input name="whatsapp_number" value="{{ old('whatsapp_number') }}" required type="text"
                                        class="form-control ot-input mb_30 @error('whatsapp_number') is-invalid @enderror"
                                        placeholder="+923001234567">
                                    @error('whatsapp_number')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-xl-6">
                                    <label class="primary_label2">Email address <span class="text-danger">*</span></label>
                                    <input name="email" value="{{ old('email') }}" required type="email"
                                        class="form-control ot-input mb_30 @error('email') is-invalid @enderror"
                                        placeholder="you@example.com">
                                    @error('email')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-xl-6">
                                    <label class="primary_label2">Student age <span class="text-danger">*</span></label>
                                    <input name="student_age" value="{{ old('student_age') }}" required type="text"
                                        class="form-control ot-input mb_30 @error('student_age') is-invalid @enderror"
                                        placeholder="e.g. 8 years">
                                    @error('student_age')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-xl-12">
                                    <button type="submit" class="theme_btn small_btn3 min_windth_200 text-center">Request my demo class</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
