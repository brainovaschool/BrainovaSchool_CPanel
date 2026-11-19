@extends('backend.master')

@section('title')
    {{ @$data->full_name }} {{ ___('report.details_view') }}
@endsection
@section('content')
    <div class="table-content table-basic mt-20">
        <div class="card shadow-sm rounded-4 mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center p-4 rounded-top-4">
                <h4 class="mb-0"><i
                        class="fa-solid fa-user-graduate me-2 text-primary"></i>{{ ___('student.Student Details') }}</h4>
            </div>
            <div class="card-body p-4">
                <!-- Profile Top Section -->
                <div class="d-flex align-items-center gap-4 mb-4">
                    <div class="rounded-circle overflow-hidden" style="width: 60px; height: 60px;">
                        <img src="{{ @globalAsset(@$data->user->upload->path, '40X40.webp') }}" alt="{{ @$data->full_name }}"
                            class="img-fluid rounded-circle">
                    </div>
                    <div>
                        <h5 class="mb-1 fw-semibold">{{ @$data->full_name }}</h5>
                        <small class="text-muted">{{ ___('student.Admission No') }}: {{ $data->admission_no }}</small>
                    </div>
                </div>

                <!-- Quick Info -->
                <div class="row g-4 mb-4 mt-2">
                    <div class="col-md-3">
                        <div class="bg-light p-3 rounded-3 text-center">
                            <div class="fw-semibold">{{ ___('student.Class Section') }}</div>
                            <div>{{ @$data->session_class_student->class->name }} -
                                ({{ @$data->session_class_student->section->name }})</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-light p-3 rounded-3 text-center">
                            <div class="fw-semibold">{{ ___('student.Attendance') }}</div>
                            <div>{{ @$attendances['avg_present'] }} % ({{ $leave_data['leave_apprvd'] }}
                                {{ ___('student.Leaves') }})</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-light p-3 rounded-3 text-center">
                            <div class="fw-semibold">{{ ___('student.Admission Date') }}</div>
                            <div>{{ $data->admission_date }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-light p-3 rounded-3 text-center">
                            <div class="fw-semibold text-danger">{{ ___('student.Due Fees') }}</div>
                            <div>
                              <span class="text-danger fw-semibold">
                                 {{ setting('currency_symbol') }}  {{ number_format(@$fees['fees_due'] , 2) }}
                              </span>
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $dob = Carbon\Carbon::parse($data->dob);
                    $now = Carbon\Carbon::now();
                    $diff = $dob->diff($now);
                @endphp

                <!-- Info Cards -->
                <div class="row g-4 mt-2">
                    <!-- Personal Info -->
                    <div class="col-md-6 col-xl-3">
                        <div class="border rounded-3 p-4 h-100">
                            <h6 class="fw-bold mb-3">{{ ___('student.Personal Info') }}</h6>
                            <p class="mb-2"><i
                                    class="fa-solid fa-venus-mars me-2 text-secondary"></i>{{ ___('student.Gender') }}:
                                {{ @$data->gender->name }}</p>
                            <p class="mb-2"><i
                                    class="fa-solid fa-hourglass-half me-2 text-secondary"></i>{{ ___('student.Age') }}:
                                {{ $diff->y }} {{ ___('student.Years') }}, {{ $diff->m }}
                                {{ ___('student.Months') }}, {{ $diff->d }} {{ ___('student.Days') }}</p>
                            <p class="mb-0"><i
                                    class="fa-solid fa-phone me-2 text-secondary"></i>{{ ___('student.Phone') }}:
                                {{ @$data->mobile }}</p>
                        </div>
                    </div>

                    <!-- Guardian Info -->
                    <div class="col-md-6 col-xl-3">
                        <div class="border rounded-3 p-4 h-100">
                            <h6 class="fw-bold mb-3">{{ ___('student.Guardian Info') }}</h6>
                            <p class="mb-2"><i
                                    class="fa-solid fa-user-shield me-2 text-secondary"></i>{{ ___('student.Guardian') }}:
                                {{ @$data->parent->guardian_name }}</p>
                            <p class="mb-2"><i
                                    class="fa-solid fa-phone-volume me-2 text-secondary"></i>{{ ___('student.Phone') }}:
                                {{ @$data->parent->guardian_mobile }}</p>
                            <p class="mb-0"><i
                                    class="fa-solid fa-handshake me-2 text-secondary"></i>{{ ___('student.Relation') }}:
                                {{ @$data->parent->guardian_relation }}</p>
                        </div>
                    </div>

                    <!-- Academic Info -->
                    <div class="col-md-6 col-xl-3">
                        <div class="border rounded-3 p-4 h-100">
                            <h6 class="fw-bold mb-3">{{ ___('student.Academic Info') }}</h6>
                            <p class="mb-2"><i
                                    class="fa-solid fa-book-open me-2 text-secondary"></i>{{ ___('student.Class') }}:
                                {{ @$data->session_class_student->class->name }}</p>
                            <p class="mb-2"><i
                                    class="fa-solid fa-layer-group me-2 text-secondary"></i>{{ ___('student.Section') }}:
                                {{ @$data->session_class_student->section->name }}</p>
                            <p class="mb-0"><i
                                    class="fa-solid fa-list-ol me-2 text-secondary"></i>{{ ___('student.Roll') }}:
                                {{ @$data->roll_no }}</p>
                        </div>
                    </div>

                    <!-- Other Info -->
                    <div class="col-md-6 col-xl-3">
                        <div class="border rounded-3 p-4 h-100">
                            <h6 class="fw-bold mb-3">{{ ___('student.Others') }}</h6>
                            <p class="mb-2"><i
                                    class="fa-solid fa-school me-2 text-secondary"></i>{{ ___('school.Prev School') }}:
                                {{ @$data->previous_school_info }}</p>
                            <p class="mb-2"><i
                                    class="fa-solid fa-tags me-2 text-secondary"></i>{{ ___('student.Category') }}:
                                {{ @$data->studentCategory->name }}</p>
                            <p class="mb-0"><i
                                    class="fa-solid fa-triangle-exclamation me-2 text-secondary"></i>{{ ___('student.Emergency') }}:
                                {{ @$data->emergency_contact }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="table-content table-basic mt-20">
        <div class="card shadow-sm rounded-4 mb-4">
        <!-- Tabs -->
        <ul class="nav theme_tabs " id="infoTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button"
                    role="tab">{{ ___('student.Profile') }} </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="guardians-tab" data-bs-toggle="tab" data-bs-target="#guardians" type="button"
                    role="tab">{{ ___('student.Guardian') }} </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link " id="fees-tab" data-bs-toggle="tab" data-bs-target="#fees" type="button"
                    role="tab">{{ ___('student.Fees') }} </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="result-tab" data-bs-toggle="tab" data-bs-target="#result" type="button"
                    role="tab">{{ ___('student.Result') }} </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="assessment-tab" data-bs-toggle="tab" data-bs-target="#assessment"
                    type="button" role="tab">Assessment</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="exam-tab" data-bs-toggle="tab" data-bs-target="#exam" type="button"
                    role="tab">{{ ___('student.Exam') }} </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance"
                    type="button" role="tab">{{ ___('student.Attendance') }} </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="leave-tab" data-bs-toggle="tab" data-bs-target="#leave" type="button"
                    role="tab">{{ ___('student.Leave') }} </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="records-tab" data-bs-toggle="tab" data-bs-target="#siblings"
                    type="button" role="tab">{{ ___('student.Siblings') }} </button>
            </li>
        </ul>
        <div class="tab-content border border-top-0 " id="infoTabsContent">
            <div class="tab-pane fade show active" id="personal" role="tabpanel">
                @include('backend.student-info.student.details_tab_contents.student_profile_details')
            </div>
            <div class="tab-pane fade" id="guardians" role="tabpanel">
                @include('backend.student-info.student.details_tab_contents.parent_profile_details')

            </div>
            <div class="tab-pane fade " id="fees" role="tabpanel">
                @include('backend.student-info.student.details_tab_contents.student_fees_details')
            </div>
            <div class="tab-pane fade" id="result" role="tabpanel">
                @include('backend.student-info.student.details_tab_contents.student_result_details')
            </div>
            <div class="tab-pane fade" id="assessment" role="tabpanel">
                @include('backend.student-info.student.details_tab_contents.student_assessment_details')
            </div>
            <div class="tab-pane fade" id="exam" role="tabpanel">
                @include('backend.student-info.student.details_tab_contents.student_exam_details')
            </div>
            <div class="tab-pane fade" id="attendance" role="tabpanel">
                @include('backend.student-info.student.details_tab_contents.student_attendance_details')
            </div>
            <div class="tab-pane fade" id="leave" role="tabpanel">
                @include('backend.student-info.student.details_tab_contents.student_leave_details')
            </div>
            <div class="tab-pane fade" id="siblings" role="tabpanel">
                @include('backend.student-info.student.details_tab_contents.student_siblings_details')
            </div>
        </div>
    </div>
    </div>
    </div>
    </div>
@endsection
