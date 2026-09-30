@extends('frontend.master')
@section('title')
    {{ ___('frontend.news_details') }}
@endsection

@section('main')

<!-- bradcam::start  -->
<div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-xl-5">
                <div class="breadcam_wrap text-center">
                    <h3>{{ ___('frontend.Notice Details') }}</h3>
                    <div class="custom_breadcam">
                        <a href="{{url('/')}}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                        <a href="#" class="breadcrumb-item">{{ ___('frontend.Notice Details') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- bradcam::end  -->

<!-- news_page_area::start  -->
<div class="news_page_area section_padding">
    <div class="container">
        <div class="row">
            <div class="col-xl-8">
                <div class="news_page_info mb_25">
                    <div class="news_page_info_banner">
                        <img src="{{ @globalAsset(@$data['notice-board']->upload->path, '800X500.webp') }}" alt="{{ @$data['notice-board']->defaultTranslate->title ?: 'Brainova notice' }}" class="img-fluid">
                    </div>
                    <div class="event_posted_header d-flex align-items-center gap_10 flex-wrap">
                        <div class="event_posted_header_left flex-fill d-flex align-items-center gap_20">
                            <div class="content_info">
                                <p>{{ dateFormat($data['notice-board']->date) }}</p>
                            </div>
                        </div>
                    </div>
                    <h3 class="event_d_title mb_15">{{ $data['notice-board']->defaultTranslate->title }}</h3>
                    <p class="event_lists mb_40">
                        {!! $data['notice-board']->defaultTranslate->description !!}
                    </p>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="news_page_right_sidebar mb_25">
                    <h4 class="font_24 f_w_400 mb_15">{{ ___('frontend.Latest Notices') }}</h4>
                    <div class="latest_news_list mb_50">

                        @foreach ($data['allNotice'] as $item)
                            <!-- single_latest_single -->
                            <div class="single_latest_news_list">
                                <a href="{{ route('frontend.news-detail',$item->id) }}" class="icon_thumb">
                                    <img src="{{ @globalAsset(@$item->upload->path, '90X60.webp') }}" alt="{{ @$item->defaultTranslate->title ?: 'Brainova notice' }}" class="img-fluid">
                                </a>
                                <div class="content_text">
                                    <h4>
                                        <a href="{{ route('frontend.news-detail',$item->id) }}">{{ Str::limit($item->defaultTranslate->title,50) }}</a>
                                    </h4>
                                    <p>{{ dateFormat($item->date) }}</p>
                                </div>
                            </div>
                            <!-- single_latest_single -->
                        @endforeach

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- news_page_area::end  -->


@endsection
