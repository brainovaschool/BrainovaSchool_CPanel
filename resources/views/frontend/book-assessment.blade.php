@extends('frontend.master')
@section('title')
    Book an Assessment
@endsection

@section('main')

    <div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-xl-5">
                    <div class="breadcam_wrap text-center">
                        <h3>Book an Assessment</h3>
                        <div class="custom_breadcam">
                            <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                            <a href="#" class="breadcrumb-item">Book an Assessment</a>
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
                                <a href="{{ route('frontend.book-assessment') }}" class="theme_btn small_btn3 min_windth_200 text-center mt_30 d-inline-block">Book another</a>
                            </div>
                        @else
                            <div class="section__title mb_40 text-center">
                                <h3 class="mb-2">Book your child's assessment</h3>
                                <p class="mb-0">Tell us a little about your child and pick a time. We'll send an assessment link to this email straight away.</p>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <form class="form-area" action="{{ route('frontend.book-assessment.store') }}" method="post">
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
                                <div class="col-xl-6">
                                    <label class="primary_label2">Subject <span class="text-danger">*</span></label>
                                    <select name="subject" required class="form-control ot-input mb_30 @error('subject') is-invalid @enderror">
                                        <option value="">— Select —</option>
                                        @foreach (['Mathematics', 'English', 'Science', 'Computer Science', 'Graphic Design', 'Urdu', 'Artificial Intelligence', 'Robotics'] as $subj)
                                            <option value="{{ $subj }}" {{ old('subject') === $subj ? 'selected' : '' }}>{{ $subj }}</option>
                                        @endforeach
                                    </select>
                                    @error('subject')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
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
                                    <label class="primary_label2">Pick an assessment date &amp; time <span class="text-danger">*</span></label>
                                    @if (!empty($data['availableDates']))
                                        <div class="bn-cal" id="bnAssessCal">
                                            <div class="bn-cal-head">
                                                <button type="button" class="bn-cal-nav" data-dir="-1" aria-label="Previous month">‹</button>
                                                <span class="bn-cal-title"></span>
                                                <button type="button" class="bn-cal-nav" data-dir="1" aria-label="Next month">›</button>
                                            </div>
                                            <div class="bn-cal-dow"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>
                                            <div class="bn-cal-grid"></div>
                                            <div class="bn-cal-times" hidden>
                                                <p class="bn-cal-times-label"></p>
                                                <div class="bn-cal-times-list"></div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="assessment_slot_id" id="bnAssessSlotId" value="{{ old('assessment_slot_id') }}">
                                        <p class="bn-cal-note">Green dates have open assessment times.</p>
                                    @else
                                        <p class="bn-cal-note mb_30">No assessment times are open right now — please check back soon or contact us directly.</p>
                                    @endif
                                    @error('assessment_slot_id')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
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
                                        <input class="form-check-input @error('consent') is-invalid @enderror" type="checkbox" name="consent" id="assessConsent" value="1" {{ old('consent') ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="assessConsent">
                                            I agree Brainova may contact me about this request, and I've read the <a href="{{ route('frontend.page', 'privacy-policy') }}" target="_blank">Privacy Policy</a>.
                                        </label>
                                        @error('consent')<small class="text-danger d-block">{{ $message }}</small>@enderror
                                    </div>
                                </div>

                                <div class="col-xl-12">
                                    <button type="submit" class="theme_btn small_btn3 min_windth_200 text-center">Book the assessment</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script')
<script>
window.bnAssessCal = {!! json_encode([
    'dates' => $data['availableDates'] ?? [],
    'slots' => $data['slotsByDate'] ?? (object) [],
], JSON_HEX_TAG | JSON_HEX_AMP) !!};
</script>
<script>
(function () {
    var root = document.getElementById('bnAssessCal');
    if (!root || !window.bnAssessCal) return;

    var slotInput = document.getElementById('bnAssessSlotId');
    var titleEl   = root.querySelector('.bn-cal-title');
    var gridEl    = root.querySelector('.bn-cal-grid');
    var timesWrap = root.querySelector('.bn-cal-times');
    var timesLbl  = root.querySelector('.bn-cal-times-label');
    var timesList = root.querySelector('.bn-cal-times-list');

    var available = {};
    (window.bnAssessCal.dates || []).forEach(function (d) { available[d] = true; });
    var slotsByDate = window.bnAssessCal.slots || {};

    var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var today  = new Date(); today.setHours(0,0,0,0);
    var view   = new Date(today.getFullYear(), today.getMonth(), 1);
    var selectedDate = null;

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function key(y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); }

    function render() {
        titleEl.textContent = MONTHS[view.getMonth()] + ' ' + view.getFullYear();
        gridEl.innerHTML = '';

        var first = new Date(view.getFullYear(), view.getMonth(), 1);
        var days  = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();

        for (var i = 0; i < first.getDay(); i++) {
            gridEl.appendChild(document.createElement('span'));
        }
        for (var d = 1; d <= days; d++) {
            var k = key(view.getFullYear(), view.getMonth(), d);
            var cell = document.createElement('button');
            cell.type = 'button';
            cell.textContent = d;
            cell.className = 'bn-cal-day';
            if (available[k]) {
                cell.classList.add('is-open');
                cell.addEventListener('click', (function (kk, el) {
                    return function () { pickDate(kk, el); };
                })(k, cell));
            } else {
                cell.disabled = true;
            }
            if (k === selectedDate) cell.classList.add('is-selected');
            gridEl.appendChild(cell);
        }
    }

    function pickDate(k, el) {
        selectedDate = k;
        slotInput.value = '';
        gridEl.querySelectorAll('.bn-cal-day').forEach(function (b) { b.classList.remove('is-selected'); });
        el.classList.add('is-selected');

        var parts = k.split('-');
        var nice  = new Date(parts[0], parts[1] - 1, parts[2]).toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long' });
        timesLbl.textContent = 'Times on ' + nice;
        timesList.innerHTML = '';

        (slotsByDate[k] || []).forEach(function (s) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'bn-cal-time';
            b.textContent = s.label + (s.remaining <= 2 ? ' · ' + s.remaining + ' left' : '');
            b.addEventListener('click', function () {
                timesList.querySelectorAll('.bn-cal-time').forEach(function (x) { x.classList.remove('is-selected'); });
                b.classList.add('is-selected');
                slotInput.value = s.id;
            });
            timesList.appendChild(b);
        });
        timesWrap.hidden = false;
    }

    root.querySelectorAll('.bn-cal-nav').forEach(function (btn) {
        btn.addEventListener('click', function () {
            view.setMonth(view.getMonth() + parseInt(btn.dataset.dir, 10));
            render();
        });
    });

    render();
})();
</script>
@endpush
