@extends('backend.master')

@section('title')
    {{ @$pt }}
@endsection
@section('content')
    <div class="page-content">

        {{-- bradecrumb Area S t a r t --}}
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $pt }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>

                        <li class="breadcrumb-item active" aria-current="page">{{ $pt }}</li>
                    </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}

        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('attendance.settinngUpdate') }}" enctype="multipart/form-data" method="post" id="markRegister">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-lg-12">
                            <div class="row">

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ ___('settings.Notify To') }}<span class="fillable"></span></label>
                                    <div class="input-check-radio academic-section @error('notify_student') is-invalid @enderror">

                                        <div class="form-check">
                                          <input class="form-check-input" type="checkbox" name="notify_student" value="1"  id="flexCheckDefault-1" @if($setting->notify_student) checked @endif />
                                          <label class="form-check-label ps-2 pe-5" for="flexCheckDefault-1">Student</label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="notify_gurdian" value="1"  id="flexCheckDefault-1" @if($setting->notify_gurdian) checked @endif />
                                            <label class="form-check-label ps-2 pe-5" for="flexCheckDefault-1">Guardian</label>
                                          </div>

                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">

                                    <label for="validationServer04" class="form-label">{{ ___('common.status') }} <span class="fillable">*</span></label>
                                    <select class="nice-select niceSelect bordered_style wide @error('status') is-invalid @enderror"
                                    name="active_status" id="validationServer04"
                                    aria-describedby="validationServer04Feedback">
                                    <option value="1" @if($setting->active_status == 1) selected @endif >{{ ___('common.active') }}</option>
                                    <option value="1"  @if($setting->active_status == 0) selected @endif >{{ ___('common.inactive') }}
                                        </option>
                                    </select>

                                    @error('active_status')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                </div>
                                @php $sending_times =  json_decode($setting->sending_time, true); @endphp
                                @foreach ($shifts as $shift)
                                    <div class="col-md-3 mb-3">
                                        <label for="exampleDataList" class="form-label ">{{$shift->name}} {{___('academic.shift')}} {{ ___('settings.sending_time') }} <span
                                                class="fillable">*</span></label>
                                                <input type="hidden" name="shift_ids[]" value="{{$shift->id}}">
                                        <input class="form-control ot-input @error('sending_time') is-invalid @enderror" name="sending_times[]"
                                            list="datalistOptions" id="exampleDataList" type="time"
                                            placeholder="{{ ___('academic.enter_start_time') }}" value="{{ isset($sending_times[$shift->id]) ? $sending_times[$shift->id] : '' }}">
                                        @error('sending_time')
                                            <div id="validationServer04Feedback" class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                @endforeach




                                <div class="col-md-12 mb-3">
                                    <label for="validationServer04" class="form-label">{{ ___('settings.shortcodes') }} <span class="fillable">*</span></label>
                                    <div class="short-code">
                                        <code> [student_name], [guardian_name], [attendance_date], [attendance_type], [admission_no] , [roll_no] , [class] , [section], [school_name]</code>
                                    </div>
                                </div>


                                <div class="col-md-12 mb-3">
                                    <label for="summernote" class="form-label ">{{ ___('settings.notification_message') }} <span class="fillable">*</span></label>
                                    <textarea class="form-control ot-textarea @error('notification_message') is-invalid @enderror" name="notification_message"
                                    list="datalistOptions" id=""
                                    placeholder="{{ ___('settings.notification_message') }}">{{ $setting->notification_message ?  $setting->notification_message : old('notification_message') }}</textarea>
                                    @error('notification_message')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>


                                <p>
                                    <code>{{ 'cd ' . base_path() . '/ && php artisan attendance:cron >> /dev/null 2>&1' }}
                                    </code>
                                </p>
                                <div class="col-md-12 mt-24">
                                    <div class="text-end">
                                        <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i>
                                            </span>{{ ___('common.submit') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
@endsection
