@extends('frontend.master')
@section('title')
    Meeting Confirmed
@endsection

@section('main')

    <div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-xl-5">
                    <div class="breadcam_wrap text-center">
                        <h3>Meeting Confirmed</h3>
                        <div class="custom_breadcam">
                            <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                            <a href="#" class="breadcrumb-item">Meeting Confirmed</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="search_result_area section_padding">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7">
                    <div class="search_result_box mb_30 text-center">
                        <div class="section__title mb_20">
                            <h3>Thanks, {{ $lead->guardian_name }} — you're confirmed!</h3>
                            <p>We've noted that you'll be joining the meeting about {{ $lead->student_name }}'s assessment.</p>
                        </div>
                        @if ($lead->meeting_link)
                            <a href="{{ $lead->meeting_link }}" class="btn btn-primary" target="_blank" rel="noopener">Join the meeting</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
