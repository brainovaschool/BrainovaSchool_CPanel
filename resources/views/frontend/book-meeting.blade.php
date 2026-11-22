@extends('frontend.master')
@section('title')
    Book a Meeting
@endsection

@section('main')

    <div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-xl-5">
                    <div class="breadcam_wrap text-center">
                        <h3>Book a Meeting</h3>
                        <div class="custom_breadcam">
                            <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                            <a href="#" class="breadcrumb-item">Book a Meeting</a>
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
                            </div>
                        @else
                            <div class="section__title mb_40 text-center">
                                <h3 class="mb-2">Book a meeting for {{ $lead->student_name }}</h3>
                                <p class="mb-0">Pick a time and the teacher will walk you through the full results and recommendations.</p>
                            </div>

                            @if (session('error'))
                                <div class="alert alert-danger">{{ session('error') }}</div>
                            @endif

                            <style>
                                .bn-meet-form .ot-input, .bn-meet-form select, .bn-meet-form textarea { border: 1px solid #c7d0db !important; }
                                .bn-meet-form .ot-input:focus, .bn-meet-form select:focus, .bn-meet-form textarea:focus { border-color: #0097b2 !important; }
                            </style>
                            <form class="form-area bn-meet-form" action="{{ request()->fullUrl() }}" method="post">
                                @csrf
                                <div class="row">
                                    <div class="col-xl-12">
                                        <label class="primary_label2">Pick a date &amp; time <span class="text-danger">*</span></label>
                                        @if (!empty($data['availableDates']))
                                            <div class="bn-cal" id="bnMeetCal">
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
                                            <input type="hidden" name="assessment_slot_id" id="bnMeetSlotId" value="{{ old('assessment_slot_id') }}">
                                            <p class="bn-cal-note">Green dates have open times.</p>
                                        @else
                                            <p class="bn-cal-note mb_30">No meeting times are open right now — please check back soon or WhatsApp us at <a href="https://wa.me/923252202555" target="_blank" rel="noopener">0325 2202555</a>.</p>
                                        @endif
                                        @error('assessment_slot_id')<small class="text-danger d-block mb_20">{{ $message }}</small>@enderror
                                    </div>

                                    <div class="col-xl-12">
                                        <label class="primary_label2">Anything else?</label>
                                        <textarea name="message" rows="4" class="form-control ot-textarea mb_30"
                                            placeholder="Anything you'd like the teacher to know">{{ old('message') }}</textarea>
                                    </div>

                                    <div class="col-xl-12">
                                        <button type="submit" id="bnMeetSubmit" class="theme_btn small_btn3 min_windth_200 text-center">Book the meeting</button>
                                        <p id="bnMeetWait" class="mt_10" style="display:none;color:#0097b2;">Submitting your request — please wait a moment…</p>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script')
<script>
window.bnMeetCal = {!! json_encode([
    'dates' => $data['availableDates'] ?? [],
    'slots' => $data['slotsByDate'] ?? (object) [],
], JSON_HEX_TAG | JSON_HEX_AMP) !!};
</script>
<script>
(function () {
    var root = document.getElementById('bnMeetCal');
    if (!root || !window.bnMeetCal) return;

    var slotInput = document.getElementById('bnMeetSlotId');
    var titleEl   = root.querySelector('.bn-cal-title');
    var gridEl    = root.querySelector('.bn-cal-grid');
    var timesWrap = root.querySelector('.bn-cal-times');
    var timesLbl  = root.querySelector('.bn-cal-times-label');
    var timesList = root.querySelector('.bn-cal-times-list');

    var available = {};
    (window.bnMeetCal.dates || []).forEach(function (d) { available[d] = true; });
    var slotsByDate = window.bnMeetCal.slots || {};

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
<script>
(function () {
    var btn = document.getElementById('bnMeetSubmit');
    var form = btn && btn.closest('form');
    if (!form) return;
    form.addEventListener('submit', function () {
        btn.disabled = true; btn.textContent = 'Sending…';
        var wait = document.getElementById('bnMeetWait'); if (wait) wait.style.display = 'block';
    });
})();
</script>
@endpush
