@extends('frontend.master')
@section('title')
    Watch & Learn
@endsection
@section('meta_description')
    Short videos from Brainova — science facts, hacks, young entrepreneur stories and more, in landscape and portrait.
@endsection

@section('main')

    <div class="breadcrumb_area">
        <div class="container">
            <div class="breadcam_wrap text-center">
                <h3>Watch & Learn</h3>
                <div class="custom_breadcam">
                    <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                    <a href="#" class="breadcrumb-item">Watch & Learn</a>
                </div>
            </div>
        </div>
    </div>

    <div class="section_padding">
        <div class="container">

            @if ($data['tabs']->count())
                <div class="bn-wl-tabs">
                    <a href="{{ route('frontend.watch-and-learn') }}" class="bn-wl-tab {{ !$data['activeTab'] ? 'is-active' : '' }}">All</a>
                    @foreach ($data['tabs'] as $tab)
                        <a href="{{ route('frontend.watch-and-learn', ['tab' => $tab->id]) }}" class="bn-wl-tab {{ (string) $data['activeTab'] === (string) $tab->id ? 'is-active' : '' }}">{{ $tab->title }}</a>
                    @endforeach
                </div>
            @endif

            @if ($data['landscape']->isEmpty() && $data['portrait']->isEmpty())
                <p class="text-center text-secondary">No videos here yet — check back soon.</p>
            @else
                @include('frontend.partials.watch-learn-carousel', ['videos' => $data['landscape'], 'orientation' => 'landscape', 'heading' => 'Landscape videos'])
                @include('frontend.partials.watch-learn-carousel', ['videos' => $data['portrait'], 'orientation' => 'portrait', 'heading' => 'Portrait videos'])
            @endif

        </div>
    </div>

@endsection

@push('css')
<style>
    .bn-wl-tabs{display:flex;flex-wrap:wrap;justify-content:center;gap:10px;margin-bottom:40px;}
    .bn-wl-tab{padding:8px 18px;border-radius:20px;border:1px solid #d8ecf0;color:#334155;font-size:.88rem;font-weight:600;text-decoration:none;transition:all .15s ease;}
    .bn-wl-tab:hover{border-color:#0097b2;color:#0097b2;}
    .bn-wl-tab.is-active{background:#0097b2;border-color:#0097b2;color:#fff;}
</style>
@endpush
