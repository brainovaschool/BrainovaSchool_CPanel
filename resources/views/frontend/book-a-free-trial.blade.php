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
                                    <label class="primary_label2">Program <span class="text-danger">*</span></label>
                                    <select name="program" required class="form-control ot-input mb_30 @error('program') is-invalid @enderror">
                                        <option value="">— Select a program —</option>
                                        @foreach (online_admission_programs() as $bnProgram)
                                            <option value="{{ $bnProgram }}" {{ old('program') === $bnProgram ? 'selected' : '' }}>{{ $bnProgram }}</option>
                                        @endforeach
                                        <option value="Not sure yet" {{ old('program') === 'Not sure yet' ? 'selected' : '' }}>Not sure yet</option>
                                    </select>
                                    @error('program')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-xl-12">
                                    <label class="primary_label2">Pick a trial date &amp; time</label>
                                    @if (!empty($data['availableDates']))
                                        <div class="bn-cal" id="bnCal">
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
                                        <input type="hidden" name="trial_slot_id" id="bnSlotId" value="{{ old('trial_slot_id') }}">
                                        <p class="bn-cal-note">Green dates have open trial times. Prefer a different time? Leave it blank and mention it below.</p>
                                    @else
                                        <p class="bn-cal-note mb_30">No trial times are open right now — submit the form and our team will arrange one with you.</p>
                                    @endif
                                    @error('trial_slot_id')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
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

@push('script')
<script>
window.bnTrialCal = {!! json_encode([
    'dates' => $data['availableDates'] ?? [],
    'slots' => $data['slotsByDate'] ?? (object) [],
], JSON_HEX_TAG | JSON_HEX_AMP) !!};
</script>
<script>
(function () {
    var root = document.getElementById('bnCal');
    if (!root || !window.bnTrialCal) return;

    var slotInput = document.getElementById('bnSlotId');
    var titleEl   = root.querySelector('.bn-cal-title');
    var gridEl    = root.querySelector('.bn-cal-grid');
    var timesWrap = root.querySelector('.bn-cal-times');
    var timesLbl  = root.querySelector('.bn-cal-times-label');
    var timesList = root.querySelector('.bn-cal-times-list');

    var available = {};
    (window.bnTrialCal.dates || []).forEach(function (d) { available[d] = true; });
    var slotsByDate = window.bnTrialCal.slots || {};

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
