@extends('frontend.master')
@section('title')
    {{ $data['title'] }}
@endsection

@section('main')

<div class="breadcrumb_area">
    <div class="container">
        <div class="breadcam_wrap text-center">
            <h3>{{ $data['title'] }}</h3>
            <p>{{ $data['lead'] }}</p>
        </div>
    </div>
</div>

<div class="statement_area section_padding">
    <div class="container">
        <section class="bn-tm-section">
            <div class="d-flex flex-wrap justify-content-end" style="gap:16px;margin-bottom:24px">
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
