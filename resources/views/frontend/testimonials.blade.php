@extends('frontend.master')
@section('title')
    {{ $data['title'] }}
@endsection

@section('main')

<div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <div class="breadcam_wrap text-center">
                    <h3>{{ $data['title'] }}</h3>
                    <div class="custom_breadcam">
                        <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                        <span class="breadcrumb-item">{{ $data['title'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="statement_area section_padding">
    <div class="container">
        <section class="bn-tm-section">
            <div class="d-flex flex-wrap justify-content-between align-items-end" style="gap:16px">
                <div>
                    <h2>{{ $data['title'] }}</h2>
                    <p class="bn-tm-sub">{{ $data['lead'] }}</p>
                </div>
                <a href="{{ $data['other']['url'] }}" class="bn-tm-switch">{{ $data['other']['label'] }} →</a>
            </div>

            @if ($data['items']->count())
                <div class="bn-tm-grid">
                    @foreach ($data['items'] as $item)
                        @include('frontend.partials.testimonial-card', ['item' => $item])
                    @endforeach
                </div>
            @else
                <p class="bn-tm-empty">
                    {{ $data['type'] === 'review' ? 'Reviews' : 'Testimonials' }} will appear here soon.
                    <a href="{{ route('frontend.contact') }}">Share yours</a>.
                </p>
            @endif
        </section>
    </div>
</div>

@endsection
