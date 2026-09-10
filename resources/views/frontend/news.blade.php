@php $bnHeading = $data['pageTitle'] ?? ___('frontend.News'); @endphp
@extends('frontend.master')
@section('title')
    {{ $bnHeading }}
@endsection

@section('main')

<!-- bradcam::start  -->
<div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-xl-5">
                <div class="breadcam_wrap text-center">
                    <h3>{{ $bnHeading }}</h3>
                    <div class="custom_breadcam">
                        <a href="{{url('/')}}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                        <a href="#" class="breadcrumb-item">{{ $bnHeading }}</a>
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

        <div class="row align-items-center mb_30">


            @foreach ($data['news'] as $item)
            <div class="col-xl-4 col-lg-4 col-md-4 mb_24 grid-item cat4">
                <div class="blog_page_widget">
                    <a href="{{ route('frontend.news-detail',$item->id) }}" class="event_thumb">
                        <img src="{{ @globalAsset(@$item->upload->path, '600X480.webp') }}" alt="Image" class="img-fluid">
                    </a>
                    <div class="blog_page_meta">
                        <h4>
                            <a href="{{ route('frontend.news-detail',$item->id) }}">{{ @$item->defaultTranslate->title }}</a>
                        </h4>
                        <p>{!! Str::limit(@$item->defaultTranslate->description,150) !!}</p>
                        <div class="blog_page_bottom d-flex align-items-center justify-content-between">
                            <a href="{{ route('frontend.news-detail',$item->id) }}">{{ ___('frontend.read_more') }} <i class="fas fa-arrow-right"></i></a>
                            <span class="blog_date"> <i class="far fa-calendar"></i>{{ dateFormat($item->date) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach


        </div>
        <div class="row">
            <div class="col-12">
                <div class="theme_pagination">

                    @if ($data['news']->onFirstPage())
                        <a class="arrow_btns d-inline-flex align-items-center justify-content-center ms-0" href="javascript:void(0)">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    @else
                        <a class="arrow_btns d-inline-flex align-items-center justify-content-center ms-0"
                            href="{{ $data['news']->previousPageUrl() }}">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    @endif


                    @foreach ($data['news']->getUrlRange(1, $data['news']->lastPage()) as $key => $item)
                        <a class="page_counter {{ $key == $data['news']->currentPage() ? 'active' : '' }}"
                            href="{{ $item }}">{{ $key }}</a>
                    @endforeach

                    @if ($data['news']->hasMorePages())
                        <a class="arrow_btns d-inline-flex align-items-center justify-content-center"
                            href="{{ $data['news']->nextPageUrl() }}">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    @else
                        <a class="arrow_btns d-inline-flex align-items-center justify-content-center" href="javascript:void(0)">
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
