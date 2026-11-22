@extends('frontend.master')
@section('title')
    Register for an Assessment
@endsection

@section('main')

    <div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-xl-5">
                    <div class="breadcam_wrap text-center">
                        <h3>Register for an Assessment</h3>
                        <div class="custom_breadcam">
                            <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                            <a href="#" class="breadcrumb-item">Register for an Assessment</a>
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
                            <div class="section__title mb_40 text-center">
                                <h5 class="mb-0 text-success">{{ session('message') }}</h5>
                                <a href="{{ route('frontend.register-assessment') }}" class="theme_btn small_btn3 min_windth_200 text-center mt_30 d-inline-block">Register another child</a>
                            </div>
                        @else
                            <div class="section__title mb_40 text-center">
                                <h3 class="mb-2">Register your child for an assessment</h3>
                                <p class="mb-0">Fill in a few details and we'll email the assessment link straight away — your child can take it whenever you're ready.</p>
                            </div>
                        @endif

                        <form class="form-area" action="{{ route('frontend.register-assessment.store') }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-xl-6">
                                    <label class="primary_label2">Parent / guardian name <span class="text-danger">*</span></label>
                                    <input name="guardian_name" value="{{ old('guardian_name') }}" required type="text"
                                        class="form-control ot-input mb_30 @error('guardian_name') is-invalid @enderror"
                                        placeholder="Your name">
                                    @error('guardian_name')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-xl-6">
                                    <label class="primary_label2">Child's name <span class="text-danger">*</span></label>
                                    <input name="student_name" value="{{ old('student_name') }}" required type="text"
                                        class="form-control ot-input mb_30 @error('student_name') is-invalid @enderror"
                                        placeholder="Child's full name">
                                    @error('student_name')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-xl-4">
                                    <label class="primary_label2">Child's age</label>
                                    <input name="student_age" value="{{ old('student_age') }}" type="text"
                                        class="form-control ot-input mb_30" placeholder="e.g. 8 years">
                                </div>
                                <div class="col-xl-4">
                                    <label class="primary_label2">Grade <span class="text-danger">*</span></label>
                                    <select name="grade" required class="form-control ot-input mb_30 @error('grade') is-invalid @enderror">
                                        <option value="">— Select —</option>
                                        @for ($g = 1; $g <= 10; $g++)
                                            <option value="{{ $g }}" {{ (string) old('grade') === (string) $g ? 'selected' : '' }}>Grade {{ $g }}</option>
                                        @endfor
                                    </select>
                                    @error('grade')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-xl-4">
                                    <label class="primary_label2">Syllabus <span class="text-danger">*</span></label>
                                    <select name="syllabus" required class="form-control ot-input mb_30 @error('syllabus') is-invalid @enderror">
                                        <option value="">— Select —</option>
                                        @foreach (['SNC', 'Cambridge', 'Oxford', 'Pearson Edexcel', 'Other'] as $syl)
                                            <option value="{{ $syl }}" {{ old('syllabus') === $syl ? 'selected' : '' }}>{{ $syl }}</option>
                                        @endforeach
                                    </select>
                                    @error('syllabus')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-xl-12">
                                    <label class="primary_label2">Subject(s) <span class="text-danger">*</span></label>
                                    <div class="row mb_20">
                                        @foreach (['Mathematics', 'English', 'Science', 'Computer Science', 'Urdu', 'Islamiyat', 'History', 'Geography'] as $subj)
                                            <div class="col-6 col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="subjects[]" id="subj_{{ $loop->index }}" value="{{ $subj }}" {{ in_array($subj, (array) old('subjects', [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="subj_{{ $loop->index }}">{{ $subj }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('subjects')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-xl-6">
                                    <label class="primary_label2">Email <span class="text-danger">*</span></label>
                                    <input name="email" value="{{ old('email') }}" required type="email"
                                        class="form-control ot-input mb_30 @error('email') is-invalid @enderror"
                                        placeholder="you@example.com">
                                    @error('email')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-xl-6">
                                    <label class="primary_label2">WhatsApp number <span class="text-danger">*</span></label>
                                    <input name="whatsapp_number" value="{{ old('whatsapp_number') }}" required type="text"
                                        class="form-control ot-input mb_30 @error('whatsapp_number') is-invalid @enderror"
                                        placeholder="e.g. +92 3xx xxxxxxx">
                                    @error('whatsapp_number')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-xl-6">
                                    <label class="primary_label2">City</label>
                                    <input name="city" value="{{ old('city') }}" type="text" class="form-control ot-input mb_30">
                                </div>

                                <div class="col-xl-12">
                                    <label class="primary_label2">Anything else?</label>
                                    <textarea name="message" rows="4" class="form-control ot-textarea mb_30"
                                        placeholder="Anything you'd like us to know">{{ old('message') }}</textarea>
                                </div>
                                {{-- H10: honeypot — invisible to people, bots fill it in and get silently rejected server-side --}}
                                <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;">

                                <div class="col-xl-12 mb_20">
                                    <div class="form-check">
                                        <input class="form-check-input @error('consent') is-invalid @enderror" type="checkbox" name="consent" id="registerConsent" value="1" {{ old('consent') ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="registerConsent">
                                            I agree Brainova may contact me about this request, and I've read the <a href="{{ route('frontend.page', 'privacy-policy') }}" target="_blank">Privacy Policy</a>.
                                        </label>
                                        @error('consent')<small class="text-danger d-block">{{ $message }}</small>@enderror
                                    </div>
                                </div>

                                <div class="col-xl-12">
                                    <button type="submit" id="bnRegisterSubmit" class="theme_btn small_btn3 min_windth_200 text-center">Send me the assessment link</button>
                                    <p id="bnRegisterWait" class="mt_10" style="display:none;color:#0097b2;">Submitting your request — please wait a moment…</p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('script')
<script>
(function () {
    var form = document.getElementById('bnRegisterSubmit') && document.getElementById('bnRegisterSubmit').closest('form');
    if (!form) return;
    form.addEventListener('submit', function () {
        var btn = document.getElementById('bnRegisterSubmit'), wait = document.getElementById('bnRegisterWait');
        if (btn) { btn.disabled = true; btn.textContent = 'Sending…'; }
        if (wait) wait.style.display = 'block';
    });
})();
</script>
@endpush

@endsection
