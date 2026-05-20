@extends('frontend.master')

@php
    $camp = $data['camp'] ?? [];
    $pageTitle = $camp['title'] ?? 'Summer camp';
@endphp

@section('title')
    {{ $pageTitle }}
@endsection

@push('css')
<link rel="stylesheet" href="{{ global_asset('frontend') }}/css/frontend-courses.css">
@endpush

@section('main')

<!-- bradcam::start (same pattern as events / news detail) -->
<div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-8">
                <div class="breadcam_wrap text-center">
                    <h3>Summer camp</h3>
                    <div class="custom_breadcam">
                        <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                        <a href="{{ route('frontend.courses') }}" class="breadcrumb-item">Our programs</a>
                        <a href="{{ route('frontend.holiday-camps') }}" class="breadcrumb-item">Holiday Clubs</a>
                        <span class="breadcrumb-item">{{ \Illuminate\Support\Str::limit($camp['title'] ?? '', 48) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- bradcam::end -->

<div class="news_page_area section_padding fe-course-detail-page">
    <div class="container">
        <div class="row">
            <div class="col-xl-8">
                <div class="news_page_info mb_25">
                    @if(!empty($camp['image']))
                        <div class="news_page_info_banner fe-course-detail-banner">
                            <img src="{{ $camp['image'] }}" alt="{{ $camp['title'] ?? '' }}" class="img-fluid">
                        </div>
                    @endif

                    <span class="event_tag">{{ $camp['badge'] ?? 'Camp' }}</span>
                    <h4 class="event_page_title">{{ $camp['title'] ?? '' }}</h4>

                    <div class="event_wrap_location_time mb_40">
                        <h4>Camp details</h4>
                        <ul>
                            @if(!empty($camp['age_range']))
                                <li><strong>Age:</strong> {{ $camp['age_range'] }}</li>
                            @endif
                            @if(!empty($camp['grade']))
                                <li><strong>Grade:</strong> {{ $camp['grade'] }}</li>
                            @endif
                            @if(!empty($camp['lessons']))
                                <li><strong>Lessons:</strong> {{ $camp['lessons'] }}</li>
                            @endif
                            @if(!empty($camp['duration']))
                                <li><strong>Duration:</strong> {{ $camp['duration'] }}</li>
                            @endif
                            @if(!empty($camp['enrolled']))
                                <li><strong>Enrollment:</strong> {{ $camp['enrolled'] }}</li>
                            @endif
                            @if(!empty($camp['price']))
                                <li><strong>Fee:</strong> {{ $camp['price'] }}</li>
                            @endif
                        </ul>
                    </div>

                    @if(!empty($camp['description']))
                        <p class="description_1 mb_24">{{ $camp['description'] }}</p>
                    @endif

                    <div class="d-flex flex-wrap gap_15 align-items-center mb_40">
                        <a href="{{ route('frontend.contact') }}" class="theme_btn small_btn3 min_windth_150 text-center">{{ ___('frontend.contact_us') }}</a>
                        <a href="{{ route('frontend.online-admission') }}" class="theme_btn2 small_btn3 min_windth_150 text-center">{{ ___('frontend.online_admission') }}</a>
                        <a href="{{ route('frontend.holiday-camps') }}" class="theme_btn2 small_btn3 min_windth_150 text-center">← {{ 'Summer camp' }}</a>
                    </div>

                    @if(!empty($camp['overview']) && is_array($camp['overview']))
                        <span class="event_tag">{{ ___('frontend.Overview') }}</span>
                        @foreach ($camp['overview'] as $para)
                            <p class="description_1 mb_24">{{ $para }}</p>
                        @endforeach
                    @endif

                    @if(!empty($camp['format']))
                        <div class="event_wrap_location_time mb_30">
                            <h4>Schedule &amp; format</h4>
                            <p class="description_1 mb-0">{{ $camp['format'] }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-xl-4">
                <div class="news_page_right_sidebar mb_25">
                    @if(!empty($camp['price']))
                        <div class="fe-course-sidebar-price mb_30">
                            <h4 class="font_24 f_w_400 mb_15">Summer camp fee</h4>
                            <p class="fe-course-sidebar-price__val mb-0">{{ $camp['price'] }}</p>
                            <p class="small text-muted mt-2 mb-0">Confirm fee and availability with admissions.</p>
                        </div>
                    @endif

                    @if(!empty($camp['highlights']) && is_array($camp['highlights']))
                        <h4 class="font_24 f_w_400 mb_15">Highlights</h4>
                        <div class="event_page_info_details mb_30">
                            <ul class="event_page_lists">
                                @foreach ($camp['highlights'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <p class="description_1 mb_20">{{ $data['trust']['body'] ?? '' }}</p>
                    <a href="{{ route('frontend.contact') }}" class="theme_btn small_btn3 min_windth_150 text-center w-100 d-inline-flex justify-content-center">{{ ___('frontend.contact_us') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
