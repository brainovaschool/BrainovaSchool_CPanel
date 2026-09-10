@extends('frontend.master')
@section('title')
    Testimonials &amp; Reviews
@endsection

@section('main')

<div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-xl-5">
                <div class="breadcam_wrap text-center">
                    <h3>Testimonials &amp; Reviews</h3>
                    <div class="custom_breadcam">
                        <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                        <a href="#" class="breadcrumb-item">Testimonials</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="statement_area section_padding">
    <div class="container">

        <section class="bn-tm-section" id="testimonials">
            <h2>What families say</h2>
            <p class="bn-tm-sub">Stories from parents and students across our programs.</p>

            @if ($data['testimonials']->count())
                <div class="bn-tm-grid">
                    @foreach ($data['testimonials'] as $item)
                        @include('frontend.partials.testimonial-card', ['item' => $item])
                    @endforeach
                </div>
            @else
                <p class="bn-tm-empty">Testimonials will appear here soon.</p>
            @endif
        </section>

        <section class="bn-tm-section" id="reviews" style="margin-top:56px">
            <h2>Reviews</h2>
            <p class="bn-tm-sub">Ratings and feedback shared by our community.</p>

            @if ($data['reviews']->count())
                <div class="bn-tm-grid">
                    @foreach ($data['reviews'] as $item)
                        @include('frontend.partials.testimonial-card', ['item' => $item])
                    @endforeach
                </div>
            @else
                <p class="bn-tm-empty">Reviews will appear here soon.</p>
            @endif
        </section>

    </div>
</div>

@endsection
