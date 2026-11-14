@php $p = $data['program'] ?? null; @endphp
@php $accents = ['teal', 'ocean', 'amber', 'indigo', 'violet', 'rose', 'coral', 'slate']; @endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('settings.category') }} <span class="fillable">*</span></label>
        <select class="nice-select niceSelect bordered_style wide @error('program_category_id') is-invalid @enderror"
            name="program_category_id" id="programCategorySelect">
            <option value="">{{ ___('common.select') }}</option>
            @foreach ($data['categories'] as $cat)
                <option value="{{ $cat->id }}" {{ old('program_category_id', $p->program_category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        @error('program_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('settings.focus_area') }}</label>
        <select class="form-control ot-input @error('program_focus_id') is-invalid @enderror" name="program_focus_id" id="programFocusSelect">
            <option value="">— {{ ___('common.none') }} —</option>
            @foreach ($data['focuses'] as $fo)
                <option value="{{ $fo->id }}" data-category="{{ $fo->program_category_id }}"
                    {{ old('program_focus_id', $p->program_focus_id ?? '') == $fo->id ? 'selected' : '' }}>
                    {{ @$fo->category->name }} — {{ $fo->name }}
                </option>
            @endforeach
        </select>
        @error('program_focus_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">Teachers</label>
        <div class="border rounded p-2" style="max-height: 160px; overflow-y: auto;">
            @php $selectedTeacherIds = old('teacher_ids', $p?->teachers->pluck('id')->all() ?? []); @endphp
            @forelse ($data['teachers'] as $teacher)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="teacher_ids[]" value="{{ $teacher->id }}"
                        id="teacher{{ $teacher->id }}" {{ in_array($teacher->id, $selectedTeacherIds) ? 'checked' : '' }}>
                    <label class="form-check-label" for="teacher{{ $teacher->id }}">{{ trim($teacher->first_name . ' ' . $teacher->last_name) }}</label>
                </div>
            @empty
                <p class="text-secondary mb-0" style="font-size: 13px;">No teaching staff found.</p>
            @endforelse
        </div>
        <small class="text-secondary">Who teaches this program — controls which teacher sees it under Beyond Academics.</small>
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">Students</label>
        <input type="text" class="form-control ot-input mb-2" id="programStudentSearch" placeholder="Search students by name...">
        <div class="border rounded p-2" id="programStudentList" style="max-height: 220px; overflow-y: auto;">
            @php $selectedStudentIds = old('student_ids', $p?->students->pluck('id')->all() ?? []); @endphp
            @forelse ($data['students'] as $student)
                <div class="form-check program-student-row" data-name="{{ \Illuminate\Support\Str::lower(trim($student->first_name . ' ' . $student->last_name)) }}">
                    <input class="form-check-input" type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                        id="student{{ $student->id }}" {{ in_array($student->id, $selectedStudentIds) ? 'checked' : '' }}>
                    <label class="form-check-label" for="student{{ $student->id }}">{{ trim($student->first_name . ' ' . $student->last_name) }}</label>
                </div>
            @empty
                <p class="text-secondary mb-0" style="font-size: 13px;">No students found.</p>
            @endforelse
        </div>
        <small class="text-secondary">Who's enrolled in this program — the student list Progress &amp; Report is built on.</small>
    </div>

    <div class="col-md-12 mb-2">
        <label class="form-label mb-0">Link to Class Content <small class="text-muted">(optional)</small></label>
        <small class="text-secondary d-block mb-2">Connects this program to the real Class / Section / Subject where its lessons, materials and homework already live in Class Content — once set, Course Details can show them here. Leave blank if this program isn't being taught in Class Content yet.</small>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Class</label>
        <select id="programClass" class="nice-select niceSelect bordered_style wide" name="classes_id">
            <option value="">— None —</option>
            @foreach ($data['classesList'] as $item)
                <option value="{{ $item->id }}" {{ old('classes_id', $p->classes_id ?? '') == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Section</label>
        <select id="programSection" class="nice-select niceSelect bordered_style wide" name="section_id">
            <option value="">— None —</option>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Subject</label>
        <select id="programSubject" class="nice-select niceSelect bordered_style wide" name="subject_id">
            <option value="">— None —</option>
        </select>
    </div>

    <div class="col-md-8 mb-3">
        <label class="form-label">{{ ___('common.title') }} <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('title') is-invalid @enderror" name="title"
            value="{{ old('title', $p->title ?? '') }}" placeholder="{{ ___('common.enter_title') }}">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('settings.badge') }}</label>
        <input class="form-control ot-input @error('badge') is-invalid @enderror" name="badge"
            value="{{ old('badge', $p->badge ?? '') }}" placeholder="{{ ___('settings.eg_maths') }}">
        @error('badge')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Status</label>
        <select class="nice-select niceSelect bordered_style wide @error('status_label') is-invalid @enderror" name="status_label">
            <option value="">— None —</option>
            @foreach (\App\Models\WebsiteSetup\Program::STATUS_LABELS as $value => $label)
                <option value="{{ $value }}" {{ old('status_label', $p->status_label ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-secondary">Shown as a coloured pill on this course's card.</small>
        @error('status_label')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Level</label>
        <select class="nice-select niceSelect bordered_style wide @error('skill_level') is-invalid @enderror" name="skill_level">
            <option value="">— None —</option>
            @foreach (\App\Models\WebsiteSetup\Program::SKILL_LEVELS as $value => $label)
                <option value="{{ $value }}" {{ old('skill_level', $p->skill_level ?? '') == $value ? 'selected' : '' }}>{{ $value }} — {{ $label }}</option>
            @endforeach
        </select>
        @error('skill_level')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">{{ ___('common.Description') }}</label>
        <textarea class="form-control ot-textarea @error('description') is-invalid @enderror" name="description" rows="2"
            placeholder="{{ ___('settings.card_summary_one_or_two_lines') }}">{{ old('description', $p->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('settings.age_range') }}</label>
        <input class="form-control ot-input" name="age_range" value="{{ old('age_range', $p->age_range ?? '') }}" placeholder="Ages 8–12">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('settings.grade') }}</label>
        <input class="form-control ot-input" name="grade" value="{{ old('grade', $p->grade ?? '') }}" placeholder="Grade 4–6">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('settings.lessons') }}</label>
        <input class="form-control ot-input" name="lessons" value="{{ old('lessons', $p->lessons ?? '') }}" placeholder="12 lessons">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('settings.duration') }}</label>
        <input class="form-control ot-input" name="duration" value="{{ old('duration', $p->duration ?? '') }}" placeholder="6 weeks">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Start date</label>
        <input type="date" class="form-control ot-input @error('start_date') is-invalid @enderror" name="start_date"
            value="{{ old('start_date', $p && $p->start_date ? $p->start_date->format('Y-m-d') : '') }}">
        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">End date</label>
        <input type="date" class="form-control ot-input @error('end_date') is-invalid @enderror" name="end_date"
            value="{{ old('end_date', $p && $p->end_date ? $p->end_date->format('Y-m-d') : '') }}">
        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Classes per week</label>
        <input class="form-control ot-input" name="classes_per_week" type="number" min="1" max="14"
            value="{{ old('classes_per_week', $p->classes_per_week ?? '') }}" placeholder="1">
    </div>
    @php
        $dayDefs = ['sun' => 'Sun', 'mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat'];
        $existingDays = strtolower($p->class_days ?? '');
        $oldDays = old('class_days');
        $checkedDays = $oldDays !== null
            ? (array) $oldDays
            : array_values(array_filter($dayDefs, fn ($key) => str_contains($existingDays, $key), ARRAY_FILTER_USE_KEY));

        // Best-effort parse of a saved display string like "4:00 to 5:00 PM"
        // into two 24h HH:MM values for the time pickers below - existing
        // programs saved before this change may not parse cleanly, in which
        // case the pickers are just left blank to re-set once.
        $parseTimeRange = function (?string $text) {
            if (!$text || !preg_match('/(\d{1,2}):(\d{2})\s*(am|pm)?\s*(?:to|-|–|&)\s*(\d{1,2}):(\d{2})\s*(am|pm)?/i', $text, $m)) {
                return [null, null];
            }
            $period2 = strtolower($m[6] ?? '') ?: strtolower($m[3] ?? '');
            $period1 = strtolower($m[3] ?? '') ?: $period2;
            $to24 = function ($h, $period) {
                $h = (int) $h;
                if ($period === 'pm' && $h < 12) { $h += 12; }
                if ($period === 'am' && $h === 12) { $h = 0; }
                return $h;
            };
            return [sprintf('%02d:%s', $to24($m[1], $period1), $m[2]), sprintf('%02d:%s', $to24($m[4], $period2), $m[5])];
        };
        [$parsedStart, $parsedEnd] = $parseTimeRange($p->class_time ?? null);
    @endphp
    <div class="col-md-7 mb-3">
        <label class="form-label">Class days</label>
        <div class="d-flex flex-wrap gap-3 border rounded p-2">
            @foreach ($dayDefs as $key => $label)
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="class_days[]" value="{{ $label }}" id="day_{{ $key }}"
                        {{ in_array($label, $checkedDays) ? 'checked' : '' }}>
                    <label class="form-check-label" for="day_{{ $key }}">{{ $label }}</label>
                </div>
            @endforeach
        </div>
    </div>
    <div class="col-md-5 mb-3">
        <label class="form-label">Class time</label>
        <div class="d-flex align-items-center gap-2">
            <input type="time" class="form-control ot-input" name="class_time_start" value="{{ old('class_time_start', $parsedStart) }}">
            <span class="text-secondary">to</span>
            <input type="time" class="form-control ot-input" name="class_time_end" value="{{ old('class_time_end', $parsedEnd) }}">
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('settings.enrolment_note') }}</label>
        <input class="form-control ot-input" name="enrolled" value="{{ old('enrolled', $p->enrolled ?? '') }}" placeholder="Open enrollment">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('common.price') }}</label>
        <input class="form-control ot-input" name="price" value="{{ old('price', $p->price ?? '') }}" placeholder="PKR 18,000 / term">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('settings.accent_colour') }}</label>
        <select class="nice-select niceSelect bordered_style wide" name="accent">
            @foreach ($accents as $a)
                <option value="{{ $a }}" {{ old('accent', $p->accent ?? 'teal') === $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('common.image') }} {{ ___('common.(1200 x 800 px)') }}</label>
        <div class="ot_fileUploader left-side mb-2 @error('image') is-invalid @enderror">
            <input class="form-control" type="text" placeholder="{{ ___('common.image') }}" readonly id="placeholder">
            <button class="primary-btn-small-input" type="button">
                <label class="btn btn-lg ot-btn-primary" for="fileBrouse">{{ ___('common.browse') }}</label>
                <input type="file" class="d-none form-control" name="image" accept="image/*" id="fileBrouse">
            </button>
        </div>
        @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @if ($p && $p->upload)
            <img src="{{ globalAsset($p->upload->path, '1200X800.webp') }}" alt="" style="max-height:80px;border-radius:8px;margin-top:6px">
        @endif
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('settings.image_url_optional') }}</label>
        <input class="form-control ot-input @error('image_url') is-invalid @enderror" name="image_url"
            value="{{ old('image_url', $p->image_url ?? '') }}" placeholder="https://…">
        @error('image_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">{{ ___('settings.uploaded_image_wins_note') }}</small>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('settings.overview_paragraphs') }}</label>
        <textarea class="form-control ot-textarea" name="overview" rows="4"
            placeholder="{{ ___('settings.one_paragraph_per_line') }}">{{ old('overview', $p ? implode("\n", (array) $p->overview) : '') }}</textarea>
        <small class="text-secondary">{{ ___('settings.one_item_per_line') }}</small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('settings.highlights') }}</label>
        <textarea class="form-control ot-textarea" name="highlights" rows="4"
            placeholder="{{ ___('settings.one_bullet_per_line') }}">{{ old('highlights', $p ? implode("\n", (array) $p->highlights) : '') }}</textarea>
        <small class="text-secondary">{{ ___('settings.one_item_per_line') }}</small>
    </div>

    <div class="col-md-8 mb-3">
        <label class="form-label">{{ ___('settings.format_line') }}</label>
        <input class="form-control ot-input" name="format" value="{{ old('format', $p->format ?? '') }}"
            placeholder="3 × 55-minute sessions weekly + homework">
    </div>
    <div class="col-md-2 mb-3">
        <label class="form-label">{{ ___('common.Serial') }}</label>
        <input class="form-control ot-input" name="sort_order" type="number" min="0" value="{{ old('sort_order', $p->sort_order ?? 0) }}">
    </div>
    <div class="col-md-2 mb-3">
        <label class="form-label">{{ ___('common.status') }} <span class="fillable">*</span></label>
        <select class="nice-select niceSelect bordered_style wide" name="status">
            <option value="{{ App\Enums\Status::ACTIVE }}" {{ old('status', $p->status ?? 1) == 1 ? 'selected' : '' }}>{{ ___('common.active') }}</option>
            <option value="{{ App\Enums\Status::INACTIVE }}" {{ old('status', $p->status ?? 1) == 0 ? 'selected' : '' }}>{{ ___('common.inactive') }}</option>
        </select>
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">{{ ___('settings.meta_description') }}</label>
        <textarea class="form-control ot-textarea" name="meta_description" rows="2"
            placeholder="{{ ___('settings.for_search_engines') }}">{{ old('meta_description', $p->meta_description ?? '') }}</textarea>
    </div>
</div>

<div class="text-end mt-3">
    <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i> </span>{{ ___('common.submit') }}</button>
</div>

@push('script')
<script>
(function () {
    var cat = document.getElementById('programCategorySelect');
    var foc = document.getElementById('programFocusSelect');
    if (!cat || !foc) return;
    function sync() {
        var v = cat.value;
        Array.prototype.forEach.call(foc.options, function (o) {
            if (!o.value) { o.hidden = false; return; }
            o.hidden = v !== '' && o.getAttribute('data-category') !== v;
        });
        if (foc.selectedOptions.length && foc.selectedOptions[0].hidden) {
            foc.value = '';
        }
    }
    cat.addEventListener('change', sync);
    sync();
})();
</script>
@endpush

@push('script')
<script>
(function () {
    var search = document.getElementById('programStudentSearch');
    var list = document.getElementById('programStudentList');
    if (!search || !list) return;
    search.addEventListener('input', function () {
        var term = search.value.trim().toLowerCase();
        list.querySelectorAll('.program-student-row').forEach(function (row) {
            row.style.display = row.getAttribute('data-name').indexOf(term) !== -1 ? '' : 'none';
        });
    });
})();
</script>
@endpush

{{-- Link to Class Content: Class -> Section -> Subject chain, reusing the
     exact same AJAX endpoints the Homework form already uses (these are
     plain Class/Section/Subject lookups, nothing homework-specific about
     them) instead of building a parallel set. --}}
@push('script')
<script>
(function () {
    var SECTION_URL = '{{ route("homework.ajax.sections-by-class") }}';
    var SUBJECT_URL  = '{{ route("homework.ajax.subjects-by-section") }}';
    var restoreSection = @json(old('section_id', $p->section_id ?? ''));
    var restoreSubject  = @json(old('subject_id', $p->subject_id ?? ''));

    function loadSections(classId) {
        var $sec = $('#programSection').empty().append('<option value="">— None —</option>');
        $('#programSubject').empty().append('<option value="">— None —</option>');
        $sec.niceSelect('update');
        $('#programSubject').niceSelect('update');
        if (!classId) { return; }
        $.getJSON(SECTION_URL, { class_id: classId }, function (response) {
            if (response.success && response.data) {
                response.data.forEach(function (s) {
                    $sec.append('<option value="' + s.id + '">' + $('<div/>').text(s.name).html() + '</option>');
                });
            }
            $sec.niceSelect('update');
            if (restoreSection) {
                var sv = restoreSection;
                restoreSection = null;
                $('#programSection').val(String(sv)).niceSelect('update').trigger('change');
            }
        });
    }

    function loadSubjects(classId, sectionId) {
        var $sub = $('#programSubject').empty().append('<option value="">— None —</option>');
        $sub.niceSelect('update');
        if (!classId || !sectionId) { return; }
        $.getJSON(SUBJECT_URL, { class_id: classId, section_id: sectionId }, function (response) {
            if (response.success && response.data) {
                response.data.forEach(function (s) {
                    $sub.append('<option value="' + s.id + '">' + $('<div/>').text(s.name).html() + '</option>');
                });
            }
            $sub.niceSelect('update');
            if (restoreSubject) {
                var sv = restoreSubject;
                restoreSubject = null;
                $('#programSubject').val(String(sv)).niceSelect('update');
            }
        });
    }

    $('#programClass').on('change', function () { loadSections($(this).val()); });
    $('body').on('change', '#programSection', function () {
        loadSubjects($('#programClass').val(), $(this).val());
    });

    var initialClass = $('#programClass').val();
    if (initialClass) {
        loadSections(initialClass);
    }
})();
</script>
@endpush
