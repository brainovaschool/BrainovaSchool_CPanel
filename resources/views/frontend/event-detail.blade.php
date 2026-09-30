@extends('frontend.master')
@section('title')
    {{ ___('frontend.event_details') }}
@endsection

@section('main')

<!-- bradcam::start  -->
<div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-8">
                <div class="breadcam_wrap text-center">
                    <h3>{{ ___('frontend.event_details') }}</h3>
                    <div class="custom_breadcam">
                        <a href="{{url('/')}}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                        <a href="#" class="breadcrumb-item">{{ ___('frontend.event_details') }}</a>
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
                <div class="event_page_info_details mb_30">
                    <span class="event_tag">{{ ___('frontend.Overview') }}</span>
                    <h4 class="event_page_title">{{ $data['event']->defaultTranslate->title }}</h4>
                    <p class="description_1 mb_24">{!! $data['event']->defaultTranslate->description,150 !!}</p>

                    <div class="event_wrap_location_time mb_40">
                        <h4>{{ ___('frontend.event_details') }}</h4>
                        <ul>
                            <li>{{ ___('frontend.Start') }} : {{ dateFormat($data['event']->date) }} - {{ timeFormat($data['event']->start_time) }}</li>
                            <li>{{ $data['event']->defaultTranslate->address }}</li>
                            <li>{{ ___('frontend.End') }} : {{ dateFormat($data['event']->date) }} - {{ timeFormat($data['event']->end_time) }}</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="news_page_right_sidebar mb_25">
                    <h4 class="font_24 f_w_400 mb_15">{{ ___('frontend.upcoming_events') }}</h4>
                    <div class="latest_news_list mb_50">


                        @foreach ($data['allEvent'] as $item)
                        <!-- single_latest_single -->
                        <div class="single_latest_news_list">
                            <a href="{{ route('frontend.events-detail',$item->id) }}" class="icon_thumb">
                                <img src="{{ @globalAsset(@$item->upload->path, '40X40.webp') }}" alt="{{ @$item->defaultTranslate->title ?: 'Brainova event' }}" class="img-fluid">
                            </a>
                            <div class="content_text">
                                <h4>
                                    <a href="{{ route('frontend.events-detail',$item->id) }}">{{ Str::limit($item->defaultTranslate->title,50) }}</a>
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
