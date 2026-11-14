@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item active">{{ $data['program']->title }}</li>
                    </ol>
                </div>
                <div class="col-sm-4 text-sm-end">
                    <a href="{{ app(\App\Repositories\WebsiteSetup\ProgramClassRepository::class)->courseDetailsUrl($data['program']) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Back to Course Details
                    </a>
                </div>
            </div>
        </div>

        <div class="card ot-card mb-3">
            <div class="card-body">
                @if ($data['students']->isEmpty())
                    <p class="text-secondary mb-0">No students are enrolled in this program yet — add them from Website Setup &rarr; Programs before grading.</p>
                @else
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label">Student</label>
                            <select name="student" class="form-select" onchange="this.form.submit()">
                                @foreach ($data['students'] as $s)
                                    <option value="{{ $s->id }}" {{ $data['selectedStudent'] && (int) $data['selectedStudent']->id === (int) $s->id ? 'selected' : '' }}>{{ $s->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        @if ($data['selectedStudent'])
            <div class="card ot-card">
                <div class="card-body">
                    @if (empty($data['breakdown']['lessonRows']))
                        <p class="text-secondary mb-0">No lesson in this course has a quiz, project, worksheet, or assignment to grade yet.</p>
                    @else
                        <form action="{{ route('program-class.grade.update', $data['program']->id) }}" method="post">
                            @csrf
                            <input type="hidden" name="student_id" value="{{ $data['selectedStudent']->id }}">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead>
                                        <tr>
                                            <th>Lesson</th>
                                            <th>Item</th>
                                            <th>Submission</th>
                                            <th style="min-width:150px;">Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($data['breakdown']['lessonRows'] as $classId => $row)
                                            @foreach ($row['items'] as $type => $item)
                                                <tr>
                                                    <td>{{ $row['class']->title }}</td>
                                                    <td>{{ $item['title'] }}</td>
                                                    <td style="min-width:160px;">
                                                        @if ($item['mark'] && $item['mark']->isSubmitted())
                                                            <a href="{{ $item['mark']->downloadUrl() }}" target="_blank" rel="noopener" class="d-block mb-1" style="font-size:12px;">
                                                                <i class="fa-solid fa-paperclip"></i> {{ $item['mark']->submission_original_filename ?: 'View submission' }}
                                                            </a>
                                                            <span class="text-secondary d-block" style="font-size:11px;">{{ $item['mark']->submitted_at->diffForHumans() }}</span>
                                                        @else
                                                            <span class="text-secondary" style="font-size:12px;">Not submitted</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01" min="0" @if ($item['max_marks']) max="{{ $item['max_marks'] }}" @endif
                                                            class="form-control ot-input" style="width:110px; display:inline-block;"
                                                            name="marks[{{ $classId }}][{{ $type }}]"
                                                            value="{{ old("marks.$classId.$type", $item['marks']) }}"
                                                            placeholder="—">
                                                        @if ($item['max_marks'])
                                                            <span class="text-secondary"> / {{ $item['max_marks'] }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button class="btn btn-lg ot-btn-primary mt-2"><span><i class="fa-solid fa-save"></i> </span> Save Marks</button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
