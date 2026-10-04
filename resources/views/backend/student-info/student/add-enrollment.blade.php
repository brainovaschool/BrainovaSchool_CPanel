@extends('backend.master')

@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('student.index') }}">{{ ___('student_info.student_list') }}</a></li>
                        <li class="breadcrumb-item active">{{ $data['student']->first_name }} {{ $data['student']->last_name }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Currently enrolled in</h5>
                <ul class="list-unstyled mb-0">
                    @foreach ($data['enrollments'] as $row)
                        <li class="mb-2">
                            <span class="badge-basic-success-text">{{ $row->enrollment_type === 'primary' ? 'Main class' : 'Short course' }}</span>
                            {{ optional($row->class)->name }} @if ($row->section) ({{ $row->section->name }}) @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-header">
                <h4 class="mb-0">Enroll {{ $data['student']->first_name }} in another class</h4>
                <p class="text-secondary mb-0" style="font-size:.85rem;">This adds a short-course enrollment alongside their main class — it never changes their main enrollment. Their name, parent, and all other details stay exactly as they are; only this one extra class is added.</p>
            </div>
            <div class="card-body">
                <form action="{{ route('student.enroll.store', $data['student']->id) }}" method="post">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ ___('student_info.class') }} <span class="fillable">*</span></label>
                            <select id="getSections" class="nice-select niceSelect bordered_style wide @error('class') is-invalid @enderror" name="class">
                                <option value="">{{ ___('student_info.select_class') }}</option>
                                @foreach ($data['classes'] as $item)
                                    <option {{ old('class') == $item->class->id ? 'selected' : '' }} value="{{ $item->class->id }}">{{ $item->class->name }}</option>
                                @endforeach
                            </select>
                            @error('class')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ ___('student_info.section') }}</label>
                            <select class="nice-select sections niceSelect bordered_style wide @error('section') is-invalid @enderror" name="section">
                                <option value="">{{ ___('student_info.select_section') }}</option>
                            </select>
                            @error('section')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ ___('student_info.shift') }}</label>
                            <select class="nice-select niceSelect bordered_style wide @error('shift') is-invalid @enderror" name="shift">
                                <option value="">{{ ___('student_info.select_shift') }}</option>
                                @foreach ($data['shifts'] as $item)
                                    <option {{ old('shift') == $item->id ? 'selected' : '' }} value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                            @error('shift')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ ___('student_info.roll_no') }}</label>
                            <input class="form-control ot-input" name="roll_no" type="text" value="{{ old('roll_no') }}">
                            @error('roll_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i> </span>{{ ___('common.submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
