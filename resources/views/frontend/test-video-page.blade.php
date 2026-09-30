@extends('frontend.master')
@section('title')
    Video Test Page
@endsection

@section('main')

    <div style="padding:20px 0;background:#fff3d6;text-align:center;">
        <p style="margin:0;font-size:.85rem;color:#8a5b00;">
            Test page only — not linked anywhere on the site. Manage these videos at
            Website Setup &rarr; Home Videos.
        </p>
    </div>

    @include('frontend.partials.home.videos', ['videos' => $data['videos']])

@endsection
