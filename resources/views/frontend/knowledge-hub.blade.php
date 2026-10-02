@extends('frontend.master')
@section('title')
    Knowledge Hub
@endsection
@section('meta_description')
    Brainova's Knowledge Hub — short, clear explanations across a range of topics for students and parents.
@endsection

@section('main')

    <div class="breadcrumb_area">
        <div class="container">
            <div class="breadcam_wrap text-center">
                <h3>Knowledge Hub</h3>
                <div class="custom_breadcam">
                    <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                    <a href="#" class="breadcrumb-item">Knowledge Hub</a>
                </div>
            </div>
        </div>
    </div>

    <div class="section_padding">
        <div class="container">
            @if ($data['pages']->isEmpty())
                <p class="text-center text-secondary">Nothing here yet — check back soon.</p>
            @else
                <div class="row">
                    @foreach ($data['pages'] as $page)
                        <div class="col-md-6 col-lg-4 mb_30">
                            <a href="{{ route('frontend.knowledge-hub-page', $page->slug) }}" class="bn-kh-card">
                                <h4>{{ $page->title }}</h4>
                                <p>{{ $page->active_topics_count }} {{ \Illuminate\Support\Str::plural('topic', $page->active_topics_count) }}</p>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

@endsection

@push('css')
<style>
    .bn-kh-card{display:block;background:#f4fbfa;border:1px solid #d8ecf0;border-radius:14px;padding:24px;text-decoration:none;height:100%;transition:transform .15s ease,box-shadow .15s ease;}
    .bn-kh-card:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(15,27,61,.1);}
    .bn-kh-card h4{color:#0f1b3d;margin-bottom:6px;}
    .bn-kh-card p{color:#64748b;margin:0;font-size:.86rem;}
</style>
@endpush
