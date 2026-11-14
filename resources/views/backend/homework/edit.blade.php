@extends('backend.master')

@section('title')
    {{ @$data['title'] }}
@endsection

@section('content')
    <div class="page-content">

        {{-- BREADCRUMB --}}
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('homework.index') }}">{{ $data['title'] }}</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">{{ ___('common.edit') }}</li>
                    </ol>
                </div>
            </div>
        </div>
        {{-- END BREADCRUMB --}}

        <div class="card ot-card">
            <div class="card-body">

                <form action="{{ route('homework.update', $data['homework']->id) }}"
                      enctype="multipart/form-data"
                      method="post">
                    @csrf
                    @method('PUT')

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
                                                {{ old('class', $data['homework']->classes_id) == $item->class->id ? 'selected' : '' }}>
                                                {{ $item->class->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('class')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 2. SECTION --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        {{ ___('student_info.section') }} <span class="fillable">*</span>
                                    </label>
                                    <select id="hw_section"
                                            class="nice-select niceSelect sections bordered_style wide section @error('section') is-invalid @enderror"
                                            name="section">
                                        <option value="">{{ ___('student_info.select_section') }}</option>
                                        @foreach ($data['sections'] as $item)
                                            <option value="{{ $item->section_id }}"
                                                {{ old('section', $data['homework']->section_id) == $item->section_id ? 'selected' : '' }}>
                                                {{ $item->section->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('section')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 3. SUBJECT --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        {{ ___('academic.subject') }} <span class="fillable">*</span>
                                    </label>
                                    <select id="subject"
                                            class="nice-select niceSelect subjects bordered_style wide @error('subject') is-invalid @enderror"
                                            name="subject">
                                        <option value="">{{ ___('examination.select_subject') }}</option>
                                        @foreach ($data['subjects'] as $item)
                                            <option value="{{ $item->subject_id }}"
                                                {{ old('subject', $data['homework']->subject_id) == $item->subject_id ? 'selected' : '' }}>
                                                {{ $item->subject->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('subject')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 3b. LINK TO A CLASS (optional) --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        Link to a class <small class="text-muted">(optional)</small>
                                    </label>
                                    <select id="hw_lesson"
                                            class="nice-select niceSelect lessons bordered_style wide @error('class_content_lesson_id') is-invalid @enderror"
                                            name="class_content_lesson_id">
                                        <option value="">Not linked to a specific class</option>
                                        @foreach ($data['lessons'] as $item)
                                            <option value="{{ $item->id }}"
                                                {{ old('class_content_lesson_id', $data['homework']->class_content_lesson_id) == $item->id ? 'selected' : '' }}>
                                                {{ $item->module_title }} — {{ $item->title }}
                                            </option>
                                        @endforeach
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
                                        <option value="homework"   {{ old('task_type', $data['homework']->task_type) == 'homework'   ? 'selected' : '' }}>Standard Homework</option>
                                        <option value="project"    {{ old('task_type', $data['homework']->task_type) == 'project'    ? 'selected' : '' }}>Project</option>
                                        <option value="activity"   {{ old('task_type', $data['homework']->task_type) == 'activity'   ? 'selected' : '' }}>Activity</option>
                                        <option value="game"       {{ old('task_type', $data['homework']->task_type) == 'game'       ? 'selected' : '' }}>Game</option>
                                        <option value="assignment" {{ old('task_type', $data['homework']->task_type) == 'assignment' ? 'selected' : '' }}>Assignment</option>
                                        <option value="quiz"       {{ old('task_type', $data['homework']->task_type) == 'quiz'       ? 'selected' : '' }}>Quiz</option>
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
                                           value="{{ old('date', $data['homework']->date) }}">
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
                                           value="{{ old('submission_date', $data['homework']->submission_date) }}">
                                    @error('submission_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 7. MARKS --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        {{ ___('examination.marks') }} <span class="fillable">*</span>
                                    </label>
                                    <input type="number"
                                           class="form-control ot-input @error('marks') is-invalid @enderror"
                                           name="marks"
                                           id="total-marks"
                                           value="{{ old('marks', $data['homework']->marks) }}"
                                           placeholder="{{ ___('examination.marks') }}"
                                           min="0">
                                    @error('marks')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 8. TOPIC --}}
                                <div class="col-md-4 mb-3">
                                    <label for="hw_topic" class="form-label">
                                        Homework Topic <span class="fillable">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control ot-input @error('topic') is-invalid @enderror"
                                           name="topic"
                                           id="hw_topic"
                                           value="{{ old('topic', $data['homework']->topic ?? '') }}"
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
                                            {{ old('status', $data['homework']->status) == App\Enums\Status::ACTIVE ? 'selected' : '' }}>
                                            {{ ___('common.active') }}
                                        </option>
                                        <option value="{{ App\Enums\Status::INACTIVE }}"
                                            {{ old('status', $data['homework']->status) == App\Enums\Status::INACTIVE ? 'selected' : '' }}>
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
                                    </label>
                                    @if ($data['homework']->upload)
                                        <div class="mb-1">
                                            <small class="text-muted">
                                                Current file:
                                                <a href="{{ globalAsset($data['homework']->upload->path, '') }}"
                                                   target="_blank">View</a>
                                                — upload a new file below to replace it.
                                            </small>
                                        </div>
                                    @endif
                                    <div class="ot_fileUploader left-side mb-1">
                                        <input class="form-control"
                                               type="text"
                                               id="file_placeholder"
                                               placeholder="{{ ___('common.document') }} (optional)"
                                               readonly>
                                        <button class="primary-btn-small-input" type="button">
                                            <label class="btn btn-lg ot-btn-primary" for="fileBrouse">
                                                {{ ___('common.browse') }}
                                            </label>
                                            {{--
                                                CRITICAL: name is toggled by JS between
                                                  - "document"            (non-quiz)
                                                  - "bulk_questions_file" (quiz)
                                                This was the root cause of quiz CSV replacement failing on edit.
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

                                {{-- 11. QUIZ MODE BANNER --}}
                                <div class="col-md-12 mb-3 d-none" id="csv_template_hint">
                                    <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <span>
                                            <i class="fa-solid fa-circle-info"></i>
                                            <strong>Quiz Mode Active:</strong>
                                            Uploading a new CSV will replace all existing questions for this quiz.
                                        </span>
                                        <a href="{{ route('homework.download-sample') }}"
                                           class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-download"></i> Download CSV Template
                                        </a>
                                    </div>
                                </div>

                                {{-- 12. AUTO-GENERATED TITLE (readonly) --}}
                                <div class="col-md-12 mb-3">
                                    <label for="hw_title" class="form-label">
                                        Homework Title <span class="fillable">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control ot-input @error('title') is-invalid @enderror"
                                           name="title"
                                           id="hw_title"
                                           value="{{ old('title', $data['homework']->title ?? '') }}"
                                           placeholder="Fill in the Topic to regenerate the title..."
                                           readonly>
                                    <small class="text-muted">
                                        Auto-generated from your inputs. Not editable — ensures a consistent naming standard.
                                    </small>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- 13. DESCRIPTION --}}
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ ___('account.description') }}</label>
                                    <textarea class="form-control ot-textarea @error('description') is-invalid @enderror"
                                              name="description"
                                              placeholder="{{ ___('account.enter_description') }}">{{ old('description', $data['homework']->description) }}</textarea>
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
@endsection

@push('script')
<script>
$(document).ready(function () {

    var HW_SECTION_PH = @json(___('student_info.select_section'));
    var HW_SUBJECT_PH = @json(___('examination.select_subject'));

    function hwLoadSections(classId) {
        var $sec = $('#hw_section').empty().append('<option value="">' + $('<div/>').text(HW_SECTION_PH).html() + '</option>');
        $('#subject').empty().append('<option value="">' + $('<div/>').text(HW_SUBJECT_PH).html() + '</option>');
        $('#hw_lesson').empty().append('<option value="">Not linked to a specific class</option>');
        $sec.niceSelect('update');
        $('#subject').niceSelect('update');
        $('#hw_lesson').niceSelect('update');

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
            },
            error: function () {
                console.error('Homework: failed to load sections');
            }
        });
    }

    function hwLoadSubjects(classId, sectionId) {
        var $sub = $('#subject').empty().append('<option value="">' + $('<div/>').text(HW_SUBJECT_PH).html() + '</option>');
        $('#hw_lesson').empty().append('<option value="">Not linked to a specific class</option>');
        $sub.niceSelect('update');
        $('#hw_lesson').niceSelect('update');

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
            },
            error: function () {
                console.error('Homework: failed to load Class Content lessons');
            }
        });
    }

    // =========================================================================
    // 1. TASK TYPE TOGGLE — switches file input name/accept between quiz and non-quiz
    // This was the critical missing fix from the original edit.blade.php.
    // Without toggling the name, quiz CSV replacement never reached the Repository.
    // =========================================================================
    $('#task_type').on('change', function () {
        var taskType = $(this).val();

        if (taskType === 'quiz') {
            $('#csv_template_hint').removeClass('d-none');
            $('#file_upload_label').html('Quiz Question File <span class="text-muted">(upload new CSV to replace questions)</span>');
            $('#fileBrouse').attr('name', 'bulk_questions_file');
            $('#fileBrouse').attr('accept', '.csv,text/csv,text/plain');
            $('#file_placeholder').attr('placeholder', 'Upload new Quiz CSV to replace existing questions (optional)');
        } else {
            $('#csv_template_hint').addClass('d-none');
            $('#file_upload_label').html('{{ ___('common.document') }}');
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
    // 2. AUTO-GENERATE TITLE
    // Format: {topic}_{ClassShorthand}_{SubjectAbbr}_{TaskAbbr}_{date}
    // Example: solar_3_IPC_prj_2026-04-22
    // Title is readonly — only topic + dropdowns drive it.
    //
    // IMPORTANT: taskAbbr MUST be declared BEFORE trigger('change') below.
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

        if (!cls  || cls.toLowerCase().includes('select'))  cls  = '';
        if (!subj || subj.toLowerCase().includes('select')) subj = '';

        cls = cls.replace(/grade[\s\-]*/i, '').trim();

        var abbr  = taskAbbr[task] || task;
        var parts = [topic, cls, subj, abbr, date].filter(Boolean);
        $('#hw_title').val(parts.length > 0 ? parts.join('_') : '');
    }

    // Now safe to trigger — taskAbbr and generateTitle are defined above
    $('#task_type').trigger('change');

    $('#hw_class').on('change', function () {
        hwLoadSections($(this).val());
        generateTitle();
    });

    $('#hw_section').on('change', function () {
        hwLoadSubjects($('#hw_class').val(), $(this).val());
        generateTitle();
    });

    // Direct listeners for non-AJAX fields
    $('#hw_topic, #hw_date').on('input change', generateTitle);
    $('#task_type').on('change', generateTitle);

    // Body-level delegation for NiceSelect AJAX dropdowns
    $('body').on('change', '#hw_class, #hw_section, #subject', generateTitle);
    $('body').on('change', '#subject', function () {
        hwLoadLessons($('#hw_class').val(), $('#hw_section').val(), $(this).val());
    });

    // Polling fallback (600 ms) for when NiceSelect AJAX updates value silently.
    // _lastLessonSnap starts at the page's already-rendered Class/Section/Subject
    // (not ''), so the pre-selected lesson from the database isn't wiped out by
    // an unwanted reload on the very first tick.
    var _lastSnap = '';
    var _lastLessonSnap = [$('#hw_class').val(), $('#hw_section').val(), $('#subject').val()].join('|');
    setInterval(function () {
        var snap = [$('#hw_class').val(), $('#hw_section').val(), $('#subject').val(), $('#task_type').val(), $('#hw_date').val(), $('#hw_topic').val()].join('|');
        if (snap !== _lastSnap) { _lastSnap = snap; generateTitle(); }

        var lessonSnap = [$('#hw_class').val(), $('#hw_section').val(), $('#subject').val()].join('|');
        if (lessonSnap !== _lastLessonSnap) {
            _lastLessonSnap = lessonSnap;
            hwLoadLessons($('#hw_class').val(), $('#hw_section').val(), $('#subject').val());
        }
    }, 600);

});
</script>
@endpush
