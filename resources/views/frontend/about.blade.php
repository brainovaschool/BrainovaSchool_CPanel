@extends('frontend.master')
@section('title')
    {{ ___('frontend.about_US') }}
@endsection

@section('main')
    <!-- bradcam::start  -->
    <div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-xl-5">
                    <div class="breadcam_wrap text-center">
                        <h3>{{ ___('frontend.about_US') }}</h3>
                        <div class="custom_breadcam">
                            <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                            <a href="#" class="breadcrumb-item">{{ ___('frontend.about_US') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- bradcam::end  -->

    <!-- STATEMENT_AREA::START  -->
    <div class="statement_area section_padding">
        <div class="container">
            <div class="row">
                <div class="col-xl-7 col-lg-6 col-md-6">
                    <div class="statement_info mb_30">
                        <h3>{{ @$sections['statement']->defaultTranslate->name }}</h3>
                        <ul class="statement_lists">
                            {{-- @dd($sections['statement']->data) --}}
                            @foreach (is_array($sections['statement']->defaultTranslate->data ?? []) ? $sections['statement']->defaultTranslate->data : [] as $item)
                                <li>
                                    <div class="statement_title d-flex align-items-center gap_20">
                                        <div class="icon">
                                            <svg width="25" height="26" viewBox="0 0 25 26" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <rect x="12.1931" y="0.806641" width="17.2437" height="17.2437"
                                                    transform="rotate(45 12.1931 0.806641)" fill="#FF5170" />
                                                <rect x="14.7651" y="3.37891" width="13.6062" height="13.6062"
                                                    transform="rotate(45 14.7651 3.37891)" fill="#392C7D" />
                                            </svg>
                                        </div>
                                        <h4>{{ $item['title'] }}</h4>
                                    </div>
                                    <p>{{ $item['description'] }}</p>
                                </li>
                            @endforeach


                        </ul>
                        {{-- <a href="{{ route('frontend.about') }}" class="theme_btn">{{ ___('frontend.learn_more') }}</a> --}}
                    </div>
                </div>
                <div class="col-xl-5 col-lg-5 col-md-6">
                    <div class="accreditation_wrapper mb_30">
                        <div class="thumb">
                            <img src="{{ @globalAsset(@$sections['statement']->upload->path, '512X512.webp') }}"
                                alt="Image" class="img-fluid">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- STATEMENT_AREA::END  -->

    <!-- SERVICES_AREA::START  -->
    <div class="services_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6">
                    <div class="section__title white_text text-center">
                        <h3>{{ @$sections['study_at']->defaultTranslate->name }}</h3>
                        <p>{{ @$sections['study_at']->defaultTranslate->description }}</p>
                    </div>
                </div>
            </div>
            <div class="row">
                @foreach (is_array($sections['study_at']->defaultTranslate->data) ? $sections['study_at']->defaultTranslate->data : [] as $key => $item)
                    <div class="col-xl-4 col-md-4">
                        <div class="serevice_box mb_30">
                            <div class="icon">
                                @php
                                    $org = $sections['study_at']->data ?? [];
                                @endphp
                                <img src="{{ @globalAsset(uploadPath(@$org[$key]['icon']), '90X60.webp') }}"
                                    alt="Icon">
                            </div>
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['description'] }}</p>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </div>
    <!-- SERVICES_AREA::END  -->
    <!-- about_gallery_wrapper::start  -->
    <div class="about_gallery_wrapper section_padding">
        <div class="container">
            <div class="row mb_30">
                <div class="col-12">

                    @foreach ($data['abouts'] as $key => $item)
                        @if ($key % 2 == 0)
                            <div class="single_about_gallery">
                                <div class="single_about_gallery_thumb">
                                    <img src="{{ @globalAsset(@$item->upload->path, '800X500.webp') }}" alt="Image"
                                        class="img-fluid">
                                </div>
                                <div class="single_about_content">
                                    <div class="iconImg">
                                        <img src="{{ @globalAsset(@$item->icon_upload->path, '90X60.webp') }}"
                                            alt="Image" class="img-fluid">
                                    </div>
                                    <h4>{{ @$item->defaultTranslate->name }}</h4>
                                    <p>{{ @$item->defaultTranslate->description }}</p>
                                </div>
                            </div>
                        @else
                            <div class="single_about_gallery">
                                <div class="single_about_content">
                                    <div class="iconImg">
                                        <img src="{{ @globalAsset(@$item->icon_upload->path, '65X90.webp') }}"
                                            alt="Image" class="img-fluid">
                                    </div>
                                    <h4>{{ @$item->defaultTranslate->name }}</h4>
                                    <p>{{ @$item->defaultTranslate->description }}</p>
                                </div>
                                <div class="single_about_gallery_thumb">
                                    <img src="{{ @globalAsset(@$item->upload->path, '800X500.webp') }}" alt="Image"
                                        class="img-fluid">
                                </div>
                            </div>
                        @endif
                    @endforeach




                </div>
            </div>
        </div>
    </div>
    <!-- about_gallery_wrapper::end  -->

    <!-- instractors_wrapper::start  -->
    <div class="instractors_wrapper gray_bg">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section__title mb_76 text-center">
                        <h3 class="text-capitalize">{{ @$sections['our_teachers']->defaultTranslate->name }}</span></h3>
                            <p>{{ "Our Teachers are highly qualified and dedicated to providing an excellent education to our students." }}</p>
                    </div>
                </div>
            </div>
            <div class="row">


                @foreach ($data['teachers'] as $item)
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="single_instractor mb_30 position-relative">
                            <a href="#" class="thumb">
                                <img src="{{ @globalAsset(@$item->upload->path, '340X340.webp') }}" alt="Image">
                            </a>
                            <div class="instractor_info text-center">
                                <div class="instractor_info_content">
                                    <h4>{{ @$item->first_name }} {{ @$item->last_name }}</h4>
                                    <div class="instractor_social">
                                        <p>{{ @$item->designation->name }}</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                @endforeach


            </div>
        </div>
    </div>
    <!-- instractors_wrapper::end  -->
@endsection
