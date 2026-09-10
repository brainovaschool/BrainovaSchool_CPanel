@extends('frontend.master')
@section('title')
    {{ ___('frontend.News') }}
@endsection

@section('main')

<!-- bradcam::start  -->
<div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-xl-5">
                <div class="breadcam_wrap text-center">
                    <h3>{{ ___('frontend.Notices') }}</h3>
                    <div class="custom_breadcam">
                        <a href="{{url('/')}}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                        <a href="#" class="breadcrumb-item">{{ ___('frontend.Notices') }}</a>
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


            @foreach ($data['notices'] as $item)
            <div class="col-xl-4 col-lg-4 col-md-4 mb_24 grid-item cat4">
                <div class="blog_page_widget">
                    <a href="{{ route('frontend.notice-detail',$item->id) }}" class="event_thumb">
                        <img src="{{ @globalAsset(@$item->attachmentFile->path, '600X480.webp') }}" alt="Image" class="img-fluid">
                    </a>
                    <div class="blog_page_meta">
                        <h4>
                            <a href="{{ route('frontend.notice-detail',$item->id) }}">{{ @$item->defaultTranslate->title }}</a>
                        </h4>
                        <p>{{ Str::limit(trim(html_entity_decode(strip_tags(@$item->defaultTranslate->description))), 150) }}</p>
                        <div class="blog_page_bottom d-flex align-items-center justify-content-between">
                            <a href="{{ route('frontend.notice-detail',$item->id) }}">{{ ___('frontend.read_more') }} <i class="fas fa-arrow-right"></i></a>
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

                    @if ($data['notices']->currentPage() == 1)
                        <a class="arrow_btns d-inline-flex align-items-center justify-content-center ms-0"
                            href="javascript:void(0)">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    @else
                        <a class="arrow_btns d-inline-flex align-items-center justify-content-center ms-0"
                            href="{{ url('notices?page=') }}{{ $data['notices']->currentPage() - 1 }}">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    @endif


                    @foreach ($data['notices']->links()['elements'][0] as $key => $item)
                        <a class="page_counter {{ $key == $data['notices']->currentPage() ? 'active' : '' }}"
                            href="{{ $item }}">{{ $key }}</a>
                    @endforeach

                    @if ($data['notices']->currentPage() == count($data['notices']->links()['elements'][0]))
                        <a class="arrow_btns d-inline-flex align-items-center justify-content-center"
                            href="javascript:void(0)">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    @else
                        <a class="arrow_btns d-inline-flex align-items-center justify-content-center"
                            href="{{ url('notices?page=') }}{{ $data['notices']->currentPage() + 1 }}">
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
