@extends('backend.master')

@section('title')
    {{ @$data['title'] }}
@endsection

@push('css')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ global_asset('backend/assets/css/homework-teacher-gamified.css') }}">
@endpush

@section('content')
    <div class="page-content homework-index-page hw-gamified">

        <div class="hw-game-hero mt-20">
            <div class="hw-game-hero-inner row align-items-start g-3">
                <div class="col-lg">
                    <p class="hw-game-eyebrow">Mission briefing</p>
                    <h1 class="hw-game-title">{{ ___('common.add_new') }}</h1>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="hw-game-badge"><i class="fa-solid fa-book-open"></i> {{ $data['title'] }}</span>
                    </div>
                    <p class="hw-game-lead">
                        Choose class, section, subject, and dates—upload a document or quiz CSV, then save when the auto-generated title looks right.
                    </p>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('homework.index') }}">{{ $data['title'] }}</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">{{ ___('common.add_new') }}</li>
                        </ol>
                    </nav>
                </div>
                @if(hasPermission('homework_create'))
                <div class="col-lg-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                        <a href="{{ route('homework.download-sample') }}" class="btn btn-lg ot-btn-primary">
                            <span><i class="fa-solid fa-download"></i></span>
                            <span>CSV template</span>
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="table-content table-basic mt-20">
        <div class="card ot-card hw-mission-panel">
            <div class="card-header">
                <span class="hw-section-chip"><i class="fa-solid fa-clipboard-list"></i> Stage 1</span>
                <h4 class="hw-mission-title mb-0">{{ ___('common.add_new') }}</h4>
                <p class="hw-mission-desc">Complete the fields below. For quiz tasks, use the CSV template so questions import correctly.</p>
            </div>
            <div class="card-body">

                {{--
                    IMPORTANT: Form submits to homework.store (HomeworkController@store → HomeworkRepository@store).
                    The rogue homework-quiz.store route (HomeworkQuizController) has been removed.
                --}}
                <form action="{{ route('homework.store') }}" enctype="multipart/form-data" method="post" id="hw-create-form">
                    @csrf

                    <div class="row mb-3">
                        <div class="col-lg-12">
                            <div class="row">

                                {{-- 1. CLASS --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        {{ ___('student_info.class') }} <span class="fillable">*</span>
                                    </label>
                                    <select id="hw_class"
                                            class="nice-select niceSelect bordered_style wide class @error('class') is-invalid @enderror"
                                            name="class">
                                        <option value="">{{ ___('student_info.select_class') }}</option>
                                        @foreach ($data['classes'] as $item)
                                            <option value="{{ $item->class->id }}"
                                                {{ old('class') == $item->class->id ? 'selected' : '' }}>
                                                {{ $item->class->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('class')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 2. SECTION (AJAX populated) --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        {{ ___('student_info.section') }} <span class="fillable">*</span>
                                    </label>
                                    <select id="hw_section"
                                            class="nice-select niceSelect sections bordered_style wide section @error('section') is-invalid @enderror"
                                            name="section">
                                        <option value="">{{ ___('student_info.select_section') }}</option>
                                    </select>
                                    @error('section')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 3. SUBJECT (AJAX populated) --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        {{ ___('academic.subject') }} <span class="fillable">*</span>
                                    </label>
                                    <select id="subject"
                                            class="nice-select niceSelect subjects bordered_style wide @error('subject') is-invalid @enderror"
                                            name="subject">
                                        <option value="">{{ ___('examination.select_subject') }}</option>
                                    </select>
                                    @error('subject')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 3b. LINK TO A CLASS (optional) — ties this task to a
                                     specific Class Content lesson so it shows up on that
                                     class's Course Details card. Populated by AJAX once
                                     Class + Section + Subject are all chosen. --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        Link to a class <small class="text-muted">(optional)</small>
                                    </label>
                                    <select id="hw_lesson"
                                            class="nice-select niceSelect lessons bordered_style wide @error('class_content_lesson_id') is-invalid @enderror"
                                            name="class_content_lesson_id">
                                        <option value="">Not linked to a specific class</option>
                                    </select>
                                    <small class="text-muted">Shows this task on that class's Course Details card.</small>
                                    @error('class_content_lesson_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 4. TASK CATEGORY --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        {{ ___('common.Task Category') }} <span class="fillable">*</span>
                                    </label>
                                    <select id="task_type"
                                            class="nice-select niceSelect bordered_style wide @error('task_type') is-invalid @enderror"
                                            name="task_type">
                                        <option value="homework"    {{ old('task_type', 'homework') == 'homework'   ? 'selected' : '' }}>Standard Homework</option>
                                        <option value="project"     {{ old('task_type') == 'project'    ? 'selected' : '' }}>Project</option>
                                        <option value="activity"    {{ old('task_type') == 'activity'   ? 'selected' : '' }}>Activity</option>
                                        <option value="game"        {{ old('task_type') == 'game'       ? 'selected' : '' }}>Game</option>
                                        <option value="assignment"  {{ old('task_type') == 'assignment' ? 'selected' : '' }}>Assignment</option>
                                        <option value="quiz"        {{ old('task_type') == 'quiz'       ? 'selected' : '' }}>Quiz</option>
                                    </select>
                                    @error('task_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 5. ASSIGNMENT DATE --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        {{ ___('account.date') }} <span class="fillable">*</span>
                                    </label>
                                    <input type="date"
                                           class="form-control ot-input @error('date') is-invalid @enderror"
                                           name="date"
                                           id="hw_date"
                                           value="{{ old('date') }}">
                                    @error('date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 6. SUBMISSION DATE --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ ___('common.submission_date') }} <span class="fillable">*</span></label>
                                    <input type="date"
                                           class="form-control ot-input @error('submission_date') is-invalid @enderror"
                                           name="submission_date"
                                           value="{{ old('submission_date') }}">
                                    @error('submission_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 7. TOTAL MARKS --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        {{ ___('examination.marks') }} <span class="fillable">*</span>
                                    </label>
                                    <input type="number"
                                           class="form-control ot-input @error('marks') is-invalid @enderror"
                                           name="marks"
                                           id="total-marks"
                                           value="{{ old('marks') }}"
                                           placeholder="{{ ___('examination.marks') }}"
                                           min="0">
                                    @error('marks')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 8. TOPIC (teacher-entered heading, drives title generation) --}}
                                <div class="col-md-4 mb-3">
                                    <label for="hw_topic" class="form-label">
                                        Homework Topic <span class="fillable">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control ot-input @error('topic') is-invalid @enderror"
                                           name="topic"
                                           id="hw_topic"
                                           value="{{ old('topic') }}"
                                           placeholder="e.g. Solar System, Fractions, World War II">
                                    <small class="text-muted">This will appear at the start of the title.</small>
                                    @error('topic')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 9. STATUS --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        {{ ___('common.status') }} <span class="fillable">*</span>
                                    </label>
                                    <select class="nice-select niceSelect bordered_style wide @error('status') is-invalid @enderror"
                                            name="status">
                                        <option value="{{ App\Enums\Status::ACTIVE }}"
                                            {{ old('status', App\Enums\Status::ACTIVE) == App\Enums\Status::ACTIVE ? 'selected' : '' }}>
                                            {{ ___('common.active') }}
                                        </option>
                                        <option value="{{ App\Enums\Status::INACTIVE }}"
                                            {{ old('status') == App\Enums\Status::INACTIVE ? 'selected' : '' }}>
                                            {{ ___('common.inactive') }}
                                        </option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 10. FILE UPLOAD (document OR quiz CSV — toggled by JS) --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" id="file_upload_label">
                                        {{ ___('common.document') }}
                                        <span id="file_required_star"></span>
                                    </label>
                                    <div class="ot_fileUploader left-side mb-1">
                                        <input class="form-control"
                                               type="text"
                                               id="file_placeholder"
                                               placeholder="{{ ___('common.document') }}"
                                               readonly>
                                        <button class="primary-btn-small-input" type="button">
                                            <label class="btn btn-lg ot-btn-primary" for="fileBrouse">
                                                {{ ___('common.browse') }}
                                            </label>
                                            {{--
                                                The name attribute is toggled by JS:
                                                  - Non-quiz → name="document"   (optional)
                                                  - Quiz     → name="bulk_questions_file" (required)
                                            --}}
                                            <input type="file"
                                                   class="d-none form-control"
                                                   name="document"
                                                   id="fileBrouse"
                                                   accept="image/*,.pdf,.doc,.docx">
                                        </button>
                                    </div>
                                    @error('document')
                                        <div class="text-danger font-size-12">{{ $message }}</div>
                                    @enderror
                                    @error('bulk_questions_file')
                                        <div class="text-danger font-size-12">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 11. AUTO-GENERATED TITLE (readonly — built by JS from topic + dropdowns) --}}
                                <div class="col-md-12 mb-3">
                                    <label for="hw_title" class="form-label">
                                        Homework Title <span class="fillable">*</span>
                                    </label>
                                    {{--
                                        IMPORTANT: do NOT add the HTML 'required' attribute here.
                                        The title is readonly and auto-generated by JS. If the browser
                                        fires native form validation before JS has run (e.g. slow load),
                                        the required check fires on an empty readonly field and blocks
                                        submission permanently. JS validates this instead (see below).
                                    --}}
                                    <input type="text"
                                           class="form-control ot-input @error('title') is-invalid @enderror"
                                           name="title"
                                           id="hw_title"
                                           value="{{ old('title') }}"
                                           placeholder="Fill in Topic, Class, Subject and Date above..."
                                           readonly>
                                    <small class="text-muted">
                                        Auto-generated from your inputs. Not editable — ensures a consistent naming standard.
                                    </small>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 12. QUIZ MODE BANNER (hidden by default, shown when task_type = quiz) --}}
                                <div class="col-md-12 mb-3 d-none" id="csv_template_hint">
                                    <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <span>
                                            <i class="fa-solid fa-circle-info"></i>
                                            <strong>Quiz Mode Active:</strong>
                                            Upload your questions as a CSV file using the Browse button above.
                                            The file must follow the template format.
                                        </span>
                                        <a href="{{ route('homework.download-sample') }}"
                                           class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-download"></i> Download CSV Template
                                        </a>
                                    </div>
                                </div>

                                {{-- 13. DESCRIPTION --}}
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ ___('account.description') }}</label>
                                    <textarea class="form-control ot-textarea @error('description') is-invalid @enderror"
                                              name="description"
                                              placeholder="{{ ___('account.enter_description') }}">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 14. SUBMIT --}}
                                <div class="col-md-12 mt-4 mb-4">
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-lg ot-btn-primary">
                                            <i class="fa-solid fa-save"></i> {{ ___('common.submit') }}
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
        </div>
    </div>
@endsection

@push('script')
<script>
$(document).ready(function () {

    var HW_SECTION_PH = @json(___('student_info.select_section'));
    var HW_SUBJECT_PH = @json(___('examination.select_subject'));

    var hwRestoreSectionOnce = @json(old('section'));
    var hwRestoreSubjectOnce = @json(old('subject'));
    var hwRestoreLessonOnce  = @json(old('class_content_lesson_id'));

    function hwLoadSections(classId) {
        var $sec = $('#hw_section').empty().append('<option value="">' + $('<div/>').text(HW_SECTION_PH).html() + '</option>');
        $('#subject').empty().append('<option value="">' + $('<div/>').text(HW_SUBJECT_PH).html() + '</option>');
        $sec.niceSelect('update');
        $('#subject').niceSelect('update');

        if (!classId) {
            return;
        }

        $.ajax({
            url:      '{{ route("homework.ajax.sections-by-class") }}',
            method:   'GET',
            data:     { class_id: classId },
            dataType: 'json',
            success: function (response) {
                if (response.success && response.data) {
                    response.data.forEach(function (section) {
                        $sec.append('<option value="' + section.id + '">' + $('<div/>').text(section.name).html() + '</option>');
                    });
                }
                $sec.niceSelect('update');

                if (hwRestoreSectionOnce !== null && hwRestoreSectionOnce !== '') {
                    var rs = hwRestoreSectionOnce;
                    hwRestoreSectionOnce = null;
                    $('#hw_section').val(String(rs)).niceSelect('update').trigger('change');
                }
            },
            error: function () {
                console.error('Homework: failed to load sections');
            }
        });
    }

    function hwLoadSubjects(classId, sectionId) {
        var $sub = $('#subject').empty().append('<option value="">' + $('<div/>').text(HW_SUBJECT_PH).html() + '</option>');
        $sub.niceSelect('update');

        if (!classId || !sectionId) {
            return;
        }

        $.ajax({
            url:      '{{ route("homework.ajax.subjects-by-section") }}',
            method:   'GET',
            data:     { class_id: classId, section_id: sectionId },
            dataType: 'json',
            success: function (response) {
                if (response.success && response.data) {
                    response.data.forEach(function (subject) {
                        $sub.append('<option value="' + subject.id + '">' + $('<div/>').text(subject.name).html() + '</option>');
                    });
                }
                $sub.niceSelect('update');

                if (hwRestoreSubjectOnce !== null && hwRestoreSubjectOnce !== '') {
                    var ru = hwRestoreSubjectOnce;
                    hwRestoreSubjectOnce = null;
                    $('#subject').val(String(ru)).niceSelect('update').trigger('change');
                }
            },
            error: function () {
                console.error('Homework: failed to load subjects');
            }
        });
    }

    function hwLoadLessons(classId, sectionId, subjectId) {
        var $lsn = $('#hw_lesson').empty().append('<option value="">Not linked to a specific class</option>');
        $lsn.niceSelect('update');

        if (!classId || !sectionId || !subjectId) {
            return;
        }

        $.ajax({
            url:      '{{ route("homework.ajax.lessons-by-class-section-subject") }}',
            method:   'GET',
            data:     { class_id: classId, section_id: sectionId, subject_id: subjectId },
            dataType: 'json',
            success: function (response) {
                if (response.success && response.data) {
                    response.data.forEach(function (lesson) {
                        $lsn.append('<option value="' + lesson.id + '">' + $('<div/>').text(lesson.module_title + ' — ' + lesson.title).html() + '</option>');
                    });
                }
                $lsn.niceSelect('update');

                if (hwRestoreLessonOnce !== null && hwRestoreLessonOnce !== '') {
                    var rl = hwRestoreLessonOnce;
                    hwRestoreLessonOnce = null;
                    $('#hw_lesson').val(String(rl)).niceSelect('update');
                }
            },
            error: function () {
                console.error('Homework: failed to load Class Content lessons');
            }
        });
    }

    // =========================================================================
    // 2. TASK TYPE TOGGLE — switches file input between document and CSV mode
    // =========================================================================
    $('#task_type').on('change', function () {
        var taskType = $(this).val();

        if (taskType === 'quiz') {
            // Quiz mode: CSV is mandatory
            $('#csv_template_hint').removeClass('d-none');
            $('#file_upload_label').html('Quiz Question File <span class="fillable">*</span>');
            $('#file_required_star').html(' <span class="fillable">*</span>');
            $('#fileBrouse').attr('name', 'bulk_questions_file');
            $('#fileBrouse').attr('accept', '.csv,text/csv,text/plain');
            $('#file_placeholder').attr('placeholder', 'Upload Quiz CSV File... (required)');
        } else {
            // All other types: document is optional
            $('#csv_template_hint').addClass('d-none');
            $('#file_upload_label').html('{{ ___('common.document') }}');
            $('#file_required_star').html('');
            $('#fileBrouse').attr('name', 'document');
            $('#fileBrouse').attr('accept', 'image/*,.pdf,.doc,.docx');
            $('#file_placeholder').attr('placeholder', '{{ ___('common.document') }} (optional)');
        }

        generateTitle();
    });

    // Show selected filename in the readonly text box
    $('#fileBrouse').on('change', function () {
        var fileName = $(this).val().split('\\').pop();
        $('#file_placeholder').val(fileName);
    });

    // =========================================================================
    // 3. AUTO-GENERATE TITLE
    // Format: {topic}_{ClassShorthand}_{SubjectAbbr}_{TaskAbbr}_{date}
    // Example: solar_3_IPC_prj_2026-04-22
    // Title is readonly — teachers cannot edit it directly.
    //
    // IMPORTANT: taskAbbr and generateTitle MUST be declared BEFORE any
    // trigger('change') call. The trigger fires generateTitle() synchronously.
    // If taskAbbr is not yet defined at that point, taskAbbr[task] throws a
    // TypeError that silently kills the entire script block — event listeners
    // below never bind, so the title never auto-generates. (Bug was here.)
    // =========================================================================
    var taskAbbr = {
        'homework':   'hw',
        'project':    'prj',
        'activity':   'act',
        'game':       'game',
        'assignment': 'assign',
        'quiz':       'quiz'
    };

    function generateTitle() {
        var topic = $('#hw_topic').val().trim();
        var cls   = $('#hw_class option:selected').text().trim();
        var subj  = $('#subject option:selected').text().trim();
        var task  = $('#task_type').val();
        var date  = $('#hw_date').val();

        // Strip placeholder text from dropdowns
        if (!cls  || cls.toLowerCase().includes('select'))  cls  = '';
        if (!subj || subj.toLowerCase().includes('select')) subj = '';

        // Class shorthand: strip "GRADE-" / "Grade " prefix — keep number/code only
        cls = cls.replace(/grade[\s\-]*/i, '').trim();

        var abbr  = taskAbbr[task] || task;
        var parts = [topic, cls, subj, abbr, date].filter(Boolean);
        $('#hw_title').val(parts.length > 0 ? parts.join('_') : '');
    }

    // Now safe to trigger — both taskAbbr and generateTitle are defined above
    $('#task_type').trigger('change');

    $('#hw_class').on('change', function () {
        hwLoadSections($(this).val());
        generateTitle();
    });

    $('#hw_section').on('change', function () {
        hwLoadSubjects($('#hw_class').val(), $(this).val());
        generateTitle();
    });

    var oldClass = "{{ old('class') }}";
    if (oldClass) {
        setTimeout(function () {
            $('#hw_class').val(oldClass).niceSelect('update').trigger('change');
        }, 200);
    }

    // Direct change/input listeners for non-AJAX fields
    $('#hw_topic, #hw_date').on('input change', generateTitle);
    $('#task_type').on('change', generateTitle);

    // Body-level delegation for AJAX-driven NiceSelect dropdowns.
    // NiceSelect rebuilds the dropdown DOM via AJAX and does not always fire
    // a native 'change' event on the underlying <select>. Body delegation
    // catches both native and delegated events reliably.
    $('body').on('change', '#hw_class, #hw_section, #subject', generateTitle);
    $('body').on('change', '#subject', function () {
        hwLoadLessons($('#hw_class').val(), $('#hw_section').val(), $(this).val());
    });

    // Polling fallback (600 ms) — catches cases where NiceSelect AJAX updates
    // the selected value without firing any change event at all.
    var _lastSnap = '';
    var _lastLessonSnap = '';
    setInterval(function () {
        var snap = [$('#hw_class').val(), $('#hw_section').val(), $('#subject').val(), $('#task_type').val(), $('#hw_date').val(), $('#hw_topic').val()].join('|');
        if (snap !== _lastSnap) { _lastSnap = snap; generateTitle(); }

        var lessonSnap = [$('#hw_class').val(), $('#hw_section').val(), $('#subject').val()].join('|');
        if (lessonSnap !== _lastLessonSnap) {
            _lastLessonSnap = lessonSnap;
            hwLoadLessons($('#hw_class').val(), $('#hw_section').val(), $('#subject').val());
        }
    }, 600);

    // =========================================================================
    // 4. SUBMIT GUARD
    // The title field is readonly so HTML 'required' cannot be used safely.
    // Instead intercept the form submit and validate title + topic here.
    // =========================================================================
    $('#hw-create-form').on('submit', function (e) {
        var title = $('#hw_title').val().trim();
        var topic = $('#hw_topic').val().trim();

        if (!topic) {
            e.preventDefault();
            $('#hw_topic').addClass('is-invalid').focus();
            if (!$('#hw_topic').next('.text-danger').length) {
                $('#hw_topic').after('<div class="text-danger small mt-1">Please enter a homework topic.</div>');
            }
            return false;
        }

        if (!title) {
            e.preventDefault();
            // Force-generate once more in case polling hasn't fired yet
            generateTitle();
            title = $('#hw_title').val().trim();

            if (!title) {
                alert('Please fill in Class, Subject, and Date so the title can be generated before submitting.');
                return false;
            }
        }

        // Remove any validation error highlights on proceed
        $('#hw_topic').removeClass('is-invalid');
        $('#hw_topic').next('.text-danger').remove();
    });

});
</script>
@endpush
