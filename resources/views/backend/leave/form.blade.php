@php use App\Enums\RoleEnum; @endphp
@extends('backend.master')
@section('title')
    {{ isset($editLeave) ? ___('common.edit') : ___('common.add') }} {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        {{-- bradecrumb Area S t a r t --}}
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ isset($editLeave) ? ___('common.edit') : ___('common.add') }} {{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('leave-request.index') }}">{{ ___('leave.Leave Log') }}</a></li>
                        <li class="breadcrumb-item">{{ isset($editLeave) ? ___('common.edit') : ___('common.add') }}</li>
                    </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}

        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('leave-request.store') }}" enctype="multipart/form-data" method="post" id="marksheet">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <div class="row">

                                @php
                                    $canActForOthers = $data['canActForOthers'] ?? false;
                                    $isEditingStaffLeave = isset($editLeave) && (int) $editLeave->role_id !== RoleEnum::STUDENT;
                                    $selectedRoleMode = old('role_id', $isEditingStaffLeave ? 'staff' : ($editLeave->role_id ?? ''));
                                @endphp
                                @if ($canActForOthers)
                                    <div class="col-md-12 mb-3">
                                        <label for="leaveRoleSelect"
                                               class="form-label">{{ ___('common.Select Role') }} <span
                                                class="fillable">*</span></label>
                                        <select
                                            class="nice-select niceSelect bordered_style wide @error('role_id') is-invalid @enderror"
                                            name="role_id" id="leaveRoleSelect"
                                            aria-describedby="validationServer04Feedback">
                                            <option value="">{{ ___('common.Select Role') }}</option>
                                            <option value="{{ RoleEnum::STUDENT }}"
                                                {{ (string) $selectedRoleMode === (string) RoleEnum::STUDENT ? 'selected' : '' }}>
                                                {{ ___('common.Student') }}
                                            </option>
                                            <option value="staff"
                                                {{ $selectedRoleMode === 'staff' ? 'selected' : '' }}>
                                                {{ ___('common.Staff') }}
                                            </option>
                                        </select>
                                    </div>
                                @endif

                                <div class="col-md-12 mb-3">
                                    <label for="validationServer04"
                                           class="form-label">{{ ___('leave.Leave type') }} <span
                                            class="fillable">*</span></label>
                                    <select
                                        class="nice-select niceSelect bordered_style wide @error('role_id') is-invalid @enderror"
                                        name="leave_type" id="validationServer04"
                                        aria-describedby="validationServer04Feedback">
                                        <option value="">{{ ___('common.Select leave type') }}</option>
                                        @foreach ($data['types'] as $type)
                                            <option value="{{ $type->id }}"
                                                {{ old('leave_type', $editLeave->leave_type_id ?? '') == $type->id ? 'selected' : '' }}>
                                                {{ $type->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('leave_type')
                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>

                                @if ($canActForOthers)
                                    <div id="studentLeaveFields" style="{{ $isEditingStaffLeave ? 'display:none;' : '' }}">
                                        <div class="col-md-12 mb-3">
                                            <label for="validationServer04"
                                                   class="form-label">{{ ___('common.Select Class') }} <span
                                                    class="fillable">*</span></label>

                                            <select id="getSections"
                                                    class="class nice-select niceSelect bordered_style wide @error('class') is-invalid @enderror"
                                                    name="class">
                                                <option value="">{{ ___('student_info.select_class') }} *</option>
                                                @foreach ($data['classes'] as $item)
                                                    <option value="{{ $item->class->id }}"
                                                        {{ old('class', $data['class_id'] ?? '') == $item->class->id ? 'selected' : '' }}>
                                                        {{ $item->class->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('class')
                                            <div id="validationServer04Feedback" class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label for="validationServer04"
                                                   class="form-label">{{ ___('common.Select Section') }}</label>

                                            <select
                                                class="sections section nice-select niceSelect bordered_style wide @error('section') is-invalid @enderror"
                                                name="section">
                                                <option value="">{{ ___('student_info.select_section') }}</option>
                                                @if(isset($data['section']))
                                                    <option value="{{$data['section']->id}}" selected>{{$data['section']->name}}</option>
                                                @endif
                                            </select>
                                            @error('section')
                                            <div id="validationServer04Feedback" class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label for="validationServer04"
                                                   class="form-label">{{ ___('common.Select Student') }}</label>

                                            <select
                                                class="students nice-select niceSelect bordered_style wide @error('student') is-invalid @enderror"
                                                name="student">
                                                <option value="">{{ ___('student_info.select_student') }}</option>
                                                @if(isset($data['student_id']))
                                                    <option value="{{ $data['student_id']}}" selected>
                                                        {{ $editLeave->user->full_name ?? $editLeave->user->name }}
                                                    </option>
                                                @endif
                                            </select>
                                            @error('student')
                                            <div id="validationServer04Feedback" class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div id="staffLeaveFields" style="{{ $isEditingStaffLeave ? '' : 'display:none;' }}">
                                        <div class="col-md-12 mb-3">
                                            <label for="leaveStaffSelect"
                                                   class="form-label">{{ ___('common.Select Staff') }} <span
                                                    class="fillable">*</span></label>
                                            <select id="leaveStaffSelect"
                                                class="nice-select niceSelect bordered_style wide @error('staff') is-invalid @enderror"
                                                name="staff">
                                                <option value="">{{ ___('common.Select Staff') }}</option>
                                                @foreach ($data['staffs'] as $staffItem)
                                                    <option value="{{ $staffItem->id }}"
                                                        {{ old('staff', $data['staff_id'] ?? '') == $staffItem->id ? 'selected' : '' }}>
                                                        {{ trim($staffItem->first_name . ' ' . $staffItem->last_name) }}
                                                        ({{ optional($staffItem->role)->name }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('staff')
                                            <div id="validationServer04Feedback" class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">{{ ___('common.Substitute Day') }}</label>
                                            <input type="date" class="form-control ot-input @error('replacement_day') is-invalid @enderror"
                                                   name="replacement_day"
                                                   value="{{ old('replacement_day', $editLeave->replacement_day ?? '') }}">
                                            @error('replacement_day')
                                            <div id="validationServer04Feedback" class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                @elseif ($data['selfIsStaff'] ?? false)
                                    {{-- Self-service staff: no role/staff picker needed, the system
                                         already knows who's raising it - just the substitute day. --}}
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">{{ ___('common.Substitute Day') }}</label>
                                        <input type="date" class="form-control ot-input @error('replacement_day') is-invalid @enderror"
                                               name="replacement_day"
                                               value="{{ old('replacement_day', $editLeave->replacement_day ?? '') }}">
                                        @error('replacement_day')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                @endif

                                {{-- Approval decisions happen only on the dedicated Approval page now -
                                     this form is for raising/editing the request's own details. --}}
                                <input type="hidden" name="editLeaveId" value="{{ $editLeave->id ?? null }}">

                                {{-- Start Date --}}
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ ___('common.Start Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control ot-input @error('start_date') is-invalid @enderror"
                                           name="start_date"
                                           value="{{ old('start_date', $editLeave->start_date ?? '') }}">
                                </div>

                                {{-- End Date --}}
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ ___('common.End Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control ot-input @error('end_date') is-invalid @enderror"
                                           name="end_date"
                                           value="{{ old('end_date', $editLeave->end_date ?? '') }}">
                                </div>

                                <div class="col-md-12 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_half_day" value="1"
                                               id="is_half_day" {{ old('is_half_day', $editLeave->is_half_day ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_half_day">{{ ___('common.Half Day') }}</label>
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="exampleDataList"
                                           class="form-label ">{{ ___('common.Attachment') }}<span
                                            class="fillable"></span></label>
                                    <div class="ot_fileUploader left-side mb-3">
                                        <input class="form-control" type="text"
                                               placeholder="{{ ___('common.Documents') }}" readonly=""
                                               id="placeholder">
                                        <button class="primary-btn-small-input" type="button">
                                            <label class="btn btn-lg ot-btn-primary text-nowrap "
                                                   for="fileBrouse">{{ ___('common.browse') }}</label>
                                            <input type="file" class="d-none form-control"
                                                   name="attachment" id="fileBrouse">
                                        </button>
                                    </div>
                                </div>


                                {{-- Description --}}
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ ___('fees.description') }}</label>
                                    <textarea class="form-control ot-textarea mt-0 @error('description') is-invalid @enderror"
                                              name="description">{{ old('description', $editLeave->description ?? '') }}</textarea>
                                </div>

                                <div class="col-md-12 mt-24">
                                    <div class="text-end">
                                        <button class="btn btn-lg ot-btn-primary" type="submit"><span><i
                                                    class="fa-solid fa-save"></i>
                                                </span>{{ ___('common.submit') }}</button>
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
        (function () {
            var roleSelect = document.getElementById('leaveRoleSelect');
            var studentFields = document.getElementById('studentLeaveFields');
            var staffFields = document.getElementById('staffLeaveFields');
            if (!roleSelect || !studentFields || !staffFields) {
                return;
            }

            function toggle() {
                var isStudent = roleSelect.value === '{{ RoleEnum::STUDENT }}';
                studentFields.style.display = isStudent ? '' : 'none';
                staffFields.style.display = isStudent ? 'none' : '';
            }

            {{-- nice-select updates the hidden <select> and fires the change
                 event through jQuery's trigger(), which a plain
                 addEventListener('change', ...) never sees - jQuery's .on()
                 is required to catch it. --}}
            if (window.jQuery) {
                jQuery(roleSelect).on('change', toggle);
            } else {
                roleSelect.addEventListener('change', toggle);
            }

            toggle();
        })();
    </script>
@endpush
