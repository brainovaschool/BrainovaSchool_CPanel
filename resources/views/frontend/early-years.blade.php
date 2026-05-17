@extends('frontend.master')

@section('title')
    Early Years
@endsection

@section('main')
    <div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
        <div class="container">
                        <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-8">
                    <div class="breadcam_wrap text-center">
                        <h3>On campus</h3>
                        <div class="custom_breadcam">
                            <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                            <a href="{{ route('frontend.courses') }}" class="breadcrumb-item">Our programs</a>
                            <span class="breadcrumb-item">Early Years</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="section_padding">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9">
                    <h1 class="mb-4">Early Years</h1>
                    <p class="description_1 mb-4">
                        Brainova early years on campus nurtures curiosity, confidence, and foundational skills through
                        play-based learning, caring teachers, and a safe, stimulating classroom environment.
                    </p>
                    <div class="d-flex flex-wrap gap_15">
                        <a href="{{ route('frontend.contact') }}" class="theme_btn small_btn3 min_windth_150 text-center">{{ ___('frontend.contact_us') }}</a>
                        <a href="{{ route('frontend.online-admission') }}" class="theme_btn2 small_btn3 min_windth_150 text-center">{{ ___('frontend.online_admission') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
