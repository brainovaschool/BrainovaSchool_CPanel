@extends('frontend.master')
@section('title')
    {{ settingLocale('application_name') }}
@endsection

@section('main')
    <!-- BANNER::START  -->
    <div class="banner_area banner_active owl-carousel">


        {{-- @dd($sections) --}}

        @foreach ($data['sliders'] as $item)
            <!-- SINGLE_CAROUSEL -->
            <div class="banner_item" data-background="{{ @globalAsset(@$item->upload->path, '1920X700.webp') }}">
                <div class="container">
                    <div class="row d-flex align-items-center justify-content-center">
                        <div class="col-lg-12">
                            <div
                                class="banner_text text-center d-flex justify-content-center align-items-center  flex-column">
                                <h3>{{ @$item->defaultTranslate->name }}</span></h3>
                                <p>{{ @$item->defaultTranslate->description }}</p>
                                <div class="d-flex align-items-center gap_24 justify-content-center flex-wrap">
                                    <a href="{{ route('frontend.about') }}"
                                        class="theme_btn min_windth_200">{{ ___('frontend.read_more') }}</a>
                                    <a href="{{ route('frontend.contact') }}"
                                        class="theme_line_btn min_windth_200">{{ ___('frontend.contact_us') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END_SINGLE_CAROUSEL -->
        @endforeach




    </div>
    <!-- BANNER::END  -->

    @include('frontend.partials.home.welcome')

    <!-- FACILITES_AREA::START  -->
    <div class="facilites_area">
        <div class="container">
            <div class="row">
                @foreach ($data['counters'] as $item)
                    <div class="col-xl-3 col-lg-3 col-md-6 ">
                        <div class="facilites_box d-flex align-items-center mb_30">
                            <div class="facilites_box_icon">
                                <img height="75" src="{{ @globalAsset(@$item->upload->path, '90X60.webp') }}"
                                    alt="Icon">
                            </div>
                            <div class="facilites_box_content">
                                <h4>{{ @$item->defaultTranslate->total_count }}+</h4>
                                <p>{{ @$item->defaultTranslate->name }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach



            </div>
        </div>
    </div>
    <!-- FACILITES_AREA::END  -->

    @include('frontend.partials.home.programs')

    @include('frontend.partials.home.how-it-works')

    @include('frontend.partials.home.mascots')

    {{-- Mission/Vision ("statement") and "Why study at" ("services") now live only on the About page. --}}

    <!-- EXPLORER_AREA::START  -->
    <div class="explorer_area section_padding">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-md-6">
                    <div class="explorer_imgs">
                        <div class="explorer_thumb mb_30">
                            <img src="{{ @globalAsset(@$sections['explore']->upload->path, '512X512.webp') }}"
                                alt="Image" class="img-fluid">
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 mb_25">
                    <div class="section__title mb_30">
                        <h3>{{ @$sections['explore']->defaultTranslate->name }}</h3>
                        <p>{{ @$sections['explore']->defaultTranslate->description }}</p>
                    </div>
                    <div class="nav explorer_tabs" id="nav-tab" role="tablist">

                        @foreach ( ( (isset($sections['explore']->defaultTranslate->data)) &&  is_array($sections['explore']->defaultTranslate->data)) ? $sections['explore']->defaultTranslate->data : [] as $key => $item)
                            <a class="nav-item nav-link {{ $key == 0 ? 'active' : '' }}" id="item{{ $key }}-tab"
                                data-toggle="tab" href="#item{{ $key }}" role="tab"
                                aria-controls="item{{ $key }}" aria-selected="true">{{ $item['tab'] }}</a>
                        @endforeach

                    </div>
                    <div class="tab-content explorer_tab_content" id="nav-tabContent">

                        @foreach ( ( (isset($sections['explore']->defaultTranslate->data)) &&  is_array($sections['explore']->defaultTranslate->data)) ? $sections['explore']->defaultTranslate->data : [] as $key => $item)
                            <div class="tab-pane fade {{ $key == 0 ? 'show active' : '' }}" id="item{{ $key }}"
                                role="tabpanel" aria-labelledby="item{{ $key }}-tab">
                                <!-- content ::start  -->
                                <h4 class="explorer_tab_title">{{ $item['title'] }}</h4>
                                <p class="explorer_tab_description">{{ $item['description'] }}</p>
                                <!-- content ::end  -->
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- EXPLORER_AREA::END  -->

    <!-- TEACHING_AREA::START  -->
    <div class="teaching_area gray_bg section_padding2">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7 col-md-8">
                    <div class="section__title text-center mb_50">
                        <h3>{{ @$sections['why_choose_us']->defaultTranslate->name }}</h3>
                        <p>{{ @$sections['why_choose_us']->defaultTranslate->description }}</p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="teaching_grid mb_30">

                        @foreach ( ((isset($sections['why_choose_us']->defaultTranslate->data)) &&  is_array($sections['why_choose_us']->defaultTranslate->data)) ? $sections['why_choose_us']->defaultTranslate->data : [] as $item)
                            <div class="teaching_single d-flex align-items-center">
                                <div class="icon">
                                    <i class="far fa-check-circle"></i>
                                </div>
                                <p>{{ $item }}</p>
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- TEACHING_AREA::END  -->

    <!-- EVENT_AREA::START  -->
    <div class="event_area section_padding">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-8">
                    <div class="section__title text-center mb_50">
                        <h3>{{ @$sections['coming_up']->defaultTranslate->name }}</h3>
                        <p>{{ @$sections['coming_up']->defaultTranslate->description }}</p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-12">

                    <div class="event_wrapper mb_30">
                        <div class="tab-content event_wrapper_content" id="4myTabContent">

                            @foreach ($data['comingEvents'] as $key => $item)
                                <div class="tab-pane fade {{ $key == 0 ? 'show active' : '' }}"
                                    id="event{{ $key }}" role="tabpanel"
                                    aria-labelledby="event{{ $key }}-tab">
                                    <div class="event_wrapper_img">
                                        <img src="{{ @globalAsset(@$item->upload->path, '800X500.webp') }}"
                                            alt="Image" class="img-fluid">
                                    </div>
                                </div>
                            @endforeach

                        </div>
                        <ul class="nav event_tabs" id="4myTab" role="tablist">

                            @foreach ($data['comingEvents'] as $key => $item)
                                <li class="nav-item">
                                    <a class="nav-link {{ $key == 0 ? 'active' : '' }}"
                                        id="event{{ $key }}-tab" data-toggle="tab"
                                        href="#event{{ $key }}" role="tab"
                                        aria-controls="event{{ $key }}"
                                        aria-selected="{{ $key == 0 ? 'true' : 'false' }}">
                                        <div class="icon">
                                            <h3>{{ substr(dateFormat($item->date), 0, 3) }}</h3>
                                            <h5>{{ substr(dateFormat($item->date), 2, 11) }}</h5>
                                        </div>
                                        <div class="event_content">
                                            <span> <i class="far fa-clock"></i>{{ timeFormat($item->start_time) }} -
                                                {{ timeFormat($item->end_time) }}</span>
                                            <p>{{ @$item->defaultTranslate->title }}</p>
                                        </div>
                                    </a>
                                </li>
                            @endforeach

                        </ul>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- EVENT_AREA::END  -->



    <!-- BLOG::START  -->
    <div class="blog_area gray_bg">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="section__title text-center mb_80">
                        <h3>{{ @$sections['news']->defaultTranslate->name }}</h3>
                        <p>{{ @$sections['news']->defaultTranslate->description }}</p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="blog_grid">
                    <!-- blog_widget  -->
                    @foreach ($data['latestNews'] as $key => $item)
                        @if ($key == 0 || $key == 3)
                            <div class="blog_widget">
                                <a href="{{ route('frontend.news-detail', $item->id) }}" class="thumb">
                                    <img src="{{ @globalAsset(@$item->upload->path, '340X410.webp') }}" alt="Image"
                                        class="w-100">
                                </a>
                                <div class="blog_meta">
                                    <h4>
                                        <a
                                            href="{{ route('frontend.news-detail', $item->id) }}">{{ @$item->defaultTranslate->title }}</a>
                                    </h4>
                                    <p>{{ Str::limit(trim(html_entity_decode(strip_tags(@$item->defaultTranslate->description))), 150) }}</p>
                                    <div class="blog_bottom d-flex align-items-center justify-content-between">
                                        <a class="blog_readmore d-inline-flex align-items-center gap_10"
                                            href="{{ route('frontend.news-detail', $item->id) }}">
                                            <span class="blog_readmore_text">{{ ___('frontend.read_more') }} </span>
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                        <span class="blog_post_date">{{ dateFormat($item->date) }}</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="blog_widget style2 ">
                                <a href="{{ route('frontend.news-detail', $item->id) }}" class="thumb">
                                    <img src="{{ @globalAsset(@$item->upload->path, '600X480.webp') }}" alt=""
                                        class="w-100">
                                </a>
                                <div class="blog_meta">
                                    <h4>
                                        <a
                                            href="{{ route('frontend.news-detail', $item->id) }}">{{ @$item->defaultTranslate->title }}</a>
                                    </h4>
                                    <p>{{ Str::limit(trim(html_entity_decode(strip_tags(@$item->defaultTranslate->description))), 150) }}</p>
                                    <div class="blog_bottom d-flex align-items-center justify-content-between">
                                        <a class="blog_readmore d-inline-flex align-items-center gap_10"
                                            href="{{ route('frontend.news-detail', $item->id) }}">
                                            <span class="blog_readmore_text">{{ ___('frontend.read_more') }} </span>
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                        <span class="blog_post_date">{{ dateFormat($item->date) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <!-- BLOG::END  -->

    <!-- gallery_area::start  -->
    <div class="gallery_area section_padding3">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7 col-md-8">
                    <div class="section__title text-center mb_30">
                        <h3>{{ @$sections['our_gallery']->defaultTranslate->name }}</h3>
                        <p>{{ @$sections['our_gallery']->defaultTranslate->description }}</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="portfolio-menu d-flex gap_24 mb_50 justify-content-center flex-wrap">
                        <button class="active" data-filter="*">{{ ___('frontend.All') }}</button>
                        @foreach ($data['galleryCategory'] as $item)
                            <button data-filter=".{{ $item->id }}">{{ @$item->defaultTranslate->name }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="row grid">

                @foreach ($data['gallery'] as $item)
                    <div class="col-lg-3 col-md-4 grid-item {{ $item->gallery_category_id }}">
                        <div class="gallery_box mb_30">
                            <a href="{{ @globalAsset(@$item->upload->path, '340X340.webp') }}"
                                class="thumb overflow-hidden popup-image d-block">
                                <img src="{{ @globalAsset(@$item->upload->path, '340X340.webp') }}" class="img-fluid"
                                    alt="">
                            </a>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </div>
    <!-- gallery_area::end  -->

    @include('frontend.partials.home.handbook-cta')
@endsection
