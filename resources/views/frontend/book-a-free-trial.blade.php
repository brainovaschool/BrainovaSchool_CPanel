@extends('frontend.master')
@section('title')
    Book a Free Trial
@endsection

@section('main')

    <div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-xl-5">
                    <div class="breadcam_wrap text-center">
                        <h3>Book a Free Trial</h3>
                        <div class="custom_breadcam">
                            <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                            <a href="#" class="breadcrumb-item">Book a Free Trial</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="search_result_area section_padding">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-9">
                    <div class="search_result_box mb_30">
                        @if (session('message'))
                            <div class="section__title mb_40">
                                <h5 class="mb-0 text-success text-center">{{ session('message') }}</h5>
                            </div>
                        @else
                            <div class="section__title mb_40 text-center">
                                <h3 class="mb-2">Try a week with us — free</h3>
                                <p class="mb-0">Tell us a little about your child and what you’re looking for. Our admissions team will set up a trial and get back to you.</p>
                            </div>
                        @endif

                        <form class="form-area" action="{{ route('frontend.book-free-trial.store') }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-xl-6">
                                    <label class="primary_label2">Your name <span class="text-danger">*</span></label>
                                    <input name="name" value="{{ old('name') }}" required type="text"
                                        class="form-control ot-input mb_30 @error('name') is-invalid @enderror"
                                        placeholder="Parent / guardian name">
                                    @error('name')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-xl-6">
                                    <label class="primary_label2">Phone <span class="text-danger">*</span></label>
                                    <input name="phone" value="{{ old('phone') }}" required type="text"
                                        class="form-control ot-input mb_30 @error('phone') is-invalid @enderror"
                                        placeholder="Phone number">
                                    @error('phone')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-xl-6">
                                    <label class="primary_label2">Email <span class="text-danger">*</span></label>
                                    <input name="email" value="{{ old('email') }}" required type="email"
                                        class="form-control ot-input mb_30 @error('email') is-invalid @enderror"
                                        placeholder="you@example.com">
                                    @error('email')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-xl-6">
                                    <label class="primary_label2">Child’s age or grade</label>
                                    <input name="child_age" value="{{ old('child_age') }}" type="text"
                                        class="form-control ot-input mb_30" placeholder="e.g. 8 years / Grade 3">
                                </div>
                                <div class="col-xl-6">
                                    <label class="primary_label2">Interested in</label>
                                    <select name="program" class="form-control ot-input mb_30">
                                        <option value="">— Select a program —</option>
                                        @foreach ($data['categories'] as $cat)
                                            <option value="{{ $cat->name }}" {{ old('program') === $cat->name ? 'selected' : '' }}>{{ $cat->name }}</option>
                                        @endforeach
                                        <option value="Not sure yet" {{ old('program') === 'Not sure yet' ? 'selected' : '' }}>Not sure yet</option>
                                    </select>
                                </div>
                                <div class="col-xl-6">
                                    <label class="primary_label2">Preferred days / time</label>
                                    <input name="preferred_time" value="{{ old('preferred_time') }}" type="text"
                                        class="form-control ot-input mb_30" placeholder="e.g. Weekday afternoons">
                                </div>
                                <div class="col-xl-12">
                                    <label class="primary_label2">Anything else?</label>
                                    <textarea name="message" rows="4" class="form-control ot-textarea mb_30"
                                        placeholder="Tell us about your child’s needs or goals">{{ old('message') }}</textarea>
                                </div>
                                <div class="col-xl-12">
                                    <button type="submit" class="theme_btn small_btn3 min_windth_200 text-center">Request my free trial</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
