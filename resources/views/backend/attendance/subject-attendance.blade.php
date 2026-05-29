@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        {{-- bradecrumb Area S t a r t --}}
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                            <li class="breadcrumb-item">{{ $data['title'] }}</li>
                        </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}

        <div class="row">
            <div class="col-12">
                <div class="card ot-card mb-24 position-relative z_1">
                    <form action="{{ route('attendance.student.search') }}" enctype="multipart/form-data" method="get" id="subject-attendance-filter">
                        <div class="card-header d-flex align-items-center gap-4 flex-wrap">
                            <h3 class="mb-0">{{ ___('common.Filtering') }}</h3>

                            <div class="card_header_right d-flex align-items-center gap-3 flex-fill justify-content-end flex-wrap">
                                <div class="single_large_selectBox">
                                    <select id="getSections" class="class nice-select niceSelect bordered_style wide @error('class') is-invalid @enderror" name="class">
                                        <option value="">{{ ___('student_info.select_class') }}</option>
                                        @foreach ($data['classes'] as $item)
                                            <option {{ old('class', @$data['request']->class) == $item->class->id ? 'selected' : '' }} value="{{ $item->class->id }}">{{ $item->class->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('class')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="single_large_selectBox">
                                    <select id="getSubjects" class="sections section nice-select niceSelect bordered_style wide @error('section') is-invalid @enderror" name="section">
                                        <option value="">{{ ___('student_info.select_section') }}</option>
                                        @foreach ($data['sections'] as $item)
                                            <option {{ old('section', @$data['request']->section) == $item->section->id ? 'selected' : '' }}
                                                    value="{{ $item->section->id }}">{{ $item->section->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('section')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="single_large_selectBox">
                                    <select class="subjects nice-select niceSelect bordered_style wide @error('subject') is-invalid @enderror" name="subject">
                                        <option value="">{{ ___('student_info.select subject') }}</option>
                                        @foreach ($data['subjects'] as $item)
                                            <option {{ old('subject', @$data['request']->subject) == $item->subject->id ? 'selected' : '' }}
                                                    value="{{ $item->subject->id }}">{{ $item->subject->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('subject')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="single_large_selectBox">
                                    <input value="{{ old('date', @$data['request']->date ?? date('Y-m-d')) }}" name="date" class="form-control ot-input @error('date') is-invalid @enderror" type="date">
                                    @error('date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button class="btn btn-lg ot-btn-primary" type="submit">
                                    {{___('common.Search')}}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @isset($data['students'])

            <!--  table content start -->
            <div class="table-content table-basic">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">{{ $data['title'] }}</h4>
                        @if (@$data['status'] == 1)
                            <span class="badge-basic-success-text">{{ ___('attendance.attendance_already_collected_you_can_edit_record') }}</span>
                        @endif

                    </div>
                    <div class="card-body">
                        <form action="{{ route('attendance.subject-attendance.store') }}" method="post" enctype="multipart/form-data">
                            @csrf

                            <input type="hidden" name="status" value="{{ @$data['status'] }}">
                            <input type="hidden" name="class" value="{{ @$data['request']->class }}">
                            <input type="hidden" name="section" value="{{ @$data['request']->section }}">
                            <input type="hidden" name="subject" value="{{ @$data['request']->subject }}">
                            <input type="hidden" name="date" value="{{ @$data['request']->date }}">

                            <div class="input-check-radio mb-3">
                                <div class="form-check d-flex align-items-center">
                                    <input type="checkbox" id="holiday" class="form-check-input mt-0 mr-4 read common-key" name="holiday" {{ (@$data['students'][0]->attendance == 0 && @$data['status'] == 1) ? 'checked':'' }}>
                                    <label class="custom-control-label" >{{ ___('attendance.Holiday') }}</label>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered role-table" id="students_table">
                                    <thead class="thead">
                                    <tr>
                                        <th class="purchase">{{ ___('student_info.student_name') }}</th>
                                        <th class="purchase">{{ ___('student_info.roll_no') }}</th>
                                        <th class="purchase">{{ ___('student_info.admission_no') }}</th>
                                        <th class="purchase">{{ ___('student_info.class') }} ({{ ___('student_info.section') }})</th>
                                        <th class="purchase">{{ ___('attendance.Attendance') }}</th>
                                        <th class="purchase">{{ ___('attendance.Note') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse ($data['students'] as $item)
                                        <tr id="document-file">
                                            <td>{{ @$item->student->first_name }} {{ @$item->student->last_name }}</td>
                                            <td>{{ @$item->roll }}</td>
                                            <td>{{ @$item->student->admission_no }}</td>
                                            <td>{{ @$item->class->name }} ({{ @$item->section->name }})</td>
                                            <td>

                                                <input type="hidden" name="items[]" value="{{ @$item->id }}">
                                                <input type="hidden" name="students[]" value="{{ @$item->student->id }}">
                                                <input type="hidden" name="studentsRoll[]" value="{{ @$item->roll }}">

                                                <div class="remember-me d-flex align-items-center input-check-radio mb-20 gap-4 attendance">
                                                    <div class="form-check d-flex align-items-center mt-6">
                                                        <input class="form-check-input {{ @$item->attendance == App\Enums\AttendanceType::PRESENT ? 'checkedItem':'' }}" type="radio" name="attendance[{{@$item->student->id}}]"
                                                               value="{{ App\Enums\AttendanceType::PRESENT }}" {{ @$item->attendance == App\Enums\AttendanceType::PRESENT ? 'checked':'' }}/>
                                                        <label>{{ ___('attendance.Present') }}</label>
                                                    </div>
                                                    <div class="form-check d-flex align-items-center mt-6 ">
                                                        <input class="form-check-input {{ @$item->attendance == App\Enums\AttendanceType::LATE ? 'checkedItem':'' }}" type="radio" name="attendance[{{@$item->student->id}}]"
                                                               value="{{ App\Enums\AttendanceType::LATE }}" {{ @$item->attendance == App\Enums\AttendanceType::LATE ? 'checked':'' }}/>
                                                        <label>{{ ___('attendance.Late') }}</label>
                                                    </div>
                                                    <div class="form-check d-flex align-items-center mt-6 ">
                                                        <input class="form-check-input {{ @$item->attendance == App\Enums\AttendanceType::ABSENT ? 'checkedItem':'' }}" type="radio" name="attendance[{{@$item->student->id}}]"
                                                               value="{{ App\Enums\AttendanceType::ABSENT }}" {{ @$item->attendance == App\Enums\AttendanceType::ABSENT ? 'checked':'' }}{{ @$data['status'] == 1  && @$item->attendance == null ? 'checked':'' }}{{ @$data['status'] == 0 ? 'checked':'' }}/>
                                                        <label>{{ ___('attendance.Absent') }}</label>
                                                    </div>
                                                    <div class="form-check d-flex align-items-center mt-6 ">
                                                        <input class="form-check-input {{ @$item->attendance == App\Enums\AttendanceType::HALFDAY ? 'checkedItem':'' }}" type="radio" name="attendance[{{@$item->student->id}}]"
                                                               value="{{ App\Enums\AttendanceType::HALFDAY }}" {{ @$item->attendance == App\Enums\AttendanceType::HALFDAY ? 'checked':'' }}/>
                                                        <label>{{ ___('attendance.Half Day') }}</label>
                                                    </div>

                                                    <div class="form-check d-flex align-items-center mt-6 ">
                                                        <input class="form-check-input {{ @$item->attendance == App\Enums\AttendanceType::LEAVE ? 'checkedItem':'' }}" type="radio" name="attendance[{{@$item->student->id}}]"
                                                               value="{{ App\Enums\AttendanceType::LEAVE }}" {{ @$item->attendance == App\Enums\AttendanceType::LEAVE ? 'checked':'' }}/>
                                                        <label>{{ ___('attendance.Leave') }}</label>
                                                    </div>
                                                </div>

                                            </td>
                                            <td>
                                                <input class="form-control ot-input" name="note[]" placeholder="{{ ___('attendance.Note') }}" value="{{ old('note',@$item->note) }}">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="100%" class="text-center gray-color">
                                                <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                                <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if (hasPermission('attendance_create'))
                                <div class="ot-pagination pagination-content d-flex justify-content-end align-content-center py-3">
                                    <button class="btn btn-lg ot-btn-primary" type="submit">
                                        {{___('common.submit')}}
                                    </button>
                                </div>
                            @endif

                        </form>
                    </div>
                </div>
            </div>

        @endif

    </div>
@endsection

@push('script')
    @include('backend.partials.delete-ajax')
    <script>
        (function () {
            var selectedSubject = @json(old('subject', @$data['request']->subject));
            var url = $('#url').val();

            function loadSubjectsForSection(classId, sectionId, keepSubjectId) {
                if (!classId || !sectionId) {
                    return;
                }
                $.ajax({
                    type: 'GET',
                    dataType: 'json',
                    data: { classes_id: classId, section_id: sectionId },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    url: url + '/assign-subject/get-subjects',
                    success: function (items) {
                        var subjectPlaceholder = @json(___('student_info.select subject'));
                        var subjectOptions = '<option value="">' + subjectPlaceholder + '</option>';
                        var subjectLi = '';
                        $.each(items || [], function (i, item) {
                            if (!item.subject) return;
                            var sel = keepSubjectId && String(keepSubjectId) === String(item.subject.id) ? ' selected' : '';
                            subjectOptions += '<option value="' + item.subject.id + '"' + sel + '>' + item.subject.name + '</option>';
                            subjectLi += '<li data-value="' + item.subject.id + '" class="option">' + item.subject.name + '</li>';
                        });
                        $('select.subjects option').remove();
                        $('select.subjects').append(subjectOptions);
                        if ($('div .subjects').length) {
                            $('div .subjects .list li').remove();
                            $('div .subjects .list').append(subjectLi);
                            var label = $('select.subjects option:selected').text() || $('select.subjects option:first').text();
                            $('div .subjects .current').html(label);
                        }
                    }
                });
            }

            $('#getSubjects').on('change', function () {
                loadSubjectsForSection($('#getSections').val(), $(this).val(), null);
            });

            @if(old('class', @$data['request']->class) && old('section', @$data['request']->section))
            loadSubjectsForSection(
                @json(old('class', @$data['request']->class)),
                @json(old('section', @$data['request']->section)),
                selectedSubject
            );
            @endif
        })();
    </script>
@endpush
