@extends('frontend.master')
@section('title')
    {{ ___('frontend.Events') }}
@endsection

@section('main')

<!-- bradcam::start  -->
<div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-8">
                <div class="breadcam_wrap text-center">
                    <h3>{{ ___('frontend.Events') }}</h3>
                    <p>Open days, workshops and what’s coming up at Brainova.</p>
                    <div class="custom_breadcam">
                        <a href="{{url('/')}}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                        <a href="#" class="breadcrumb-item">{{ ___('frontend.Events') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- bradcam::end  -->
<!-- eventList_area::start  -->
<div class="eventList_area section_padding ">
    <div class="container">
        <div class="row align-items-center">


            @foreach ($events as $item)
                <div class="col-xl-4 col-lg-4 col-md-4 mb_24">
                    <div class="eventList_widget">
                        <a href="{{route('frontend.events-detail',$item->id)}}" class="event_thumb">
                            <img src="{{ @globalAsset(@$item->upload->path, '600X480px') }}" alt="" class="img-fluid">
                        </a>
                        <div class="eventList_meta">
                            <div class="eventList_title d-flex align-items-center justify-content-between">
                                <h3><span>{{substr(dateFormat($item->date), 0, 3)}}</span> {{substr(dateFormat($item->date), 2, 11)}}</h3>
                            </div>
                            <h4>
                                <a href="{{route('frontend.events-detail',$item->id)}}">{{ @$item->defaultTranslate->title }}</a>
                            </h4>
                            <p>{{ Str::limit(trim(html_entity_decode(strip_tags(@$item->defaultTranslate->description))), 135) }}</p>
                            <ul class="event_date_location">
                                <li><i class="far fa-clock"></i>{{ timeFormat($item->start_time) }} - {{ timeFormat($item->end_time) }}</li>
                                <li><i class="fas fa-map-marker-alt"></i> {{ @$item->defaultTranslate->address }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach



        </div>
        <div class="row">
            <div class="col-12">
                <div class="theme_pagination">

                    @if ($events->currentPage() == 1)
                        <a class="arrow_btns d-inline-flex align-items-center justify-content-center ms-0"
                            href="javascript:void(0)">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    @else
                        <a class="arrow_btns d-inline-flex align-items-center justify-content-center ms-0"
                            href="{{ url('events?page=') }}{{ $events->currentPage() - 1 }}">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    @endif


                    @foreach ($events->links()['elements'][0] as $key => $item)
                        <a class="page_counter {{ $key == $events->currentPage() ? 'active' : '' }}"
                            href="{{ $item }}">{{ $key }}</a>
                    @endforeach

                    @if ($events->currentPage() == count($events->links()['elements'][0]))
                        <a class="arrow_btns d-inline-flex align-items-center justify-content-center"
                            href="javascript:void(0)">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    @else
                        <a class="arrow_btns d-inline-flex align-items-center justify-content-center"
                            href="{{ url('events?page=') }}{{ $events->currentPage() + 1 }}">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
<!-- eventList_area::end  -->

@endsection
