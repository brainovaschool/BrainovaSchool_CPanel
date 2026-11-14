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

        <div class="card ot-card">
            <div class="card-body">
                @if ($data['grid']['students']->isEmpty())
                    <p class="text-secondary mb-0">No students are enrolled in this program yet — add them from Website Setup &rarr; Programs before grading.</p>
                @else
                    <form action="{{ route('program-class.grade.update', $data['class']->id) }}" method="post">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th style="min-width:180px;">Student</th>
                                        @foreach ($data['grid']['items'] as $type => $item)
                                            <th style="min-width:140px;">
                                                {{ $item['title'] }}
                                                @if ($item['max_marks'])
                                                    <span class="text-secondary">/ {{ $item['max_marks'] }}</span>
                                                @endif
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data['grid']['students'] as $student)
                                        <tr>
                                            <td>{{ $student->full_name }}</td>
                                            @foreach ($data['grid']['items'] as $type => $item)
                                                @php
                                                    $mark = $data['grid']['marks'][$student->id][$type] ?? null;
                                                @endphp
                                                <td>
                                                    @if ($mark && $mark->isSubmitted())
                                                        <a href="{{ $mark->downloadUrl() }}" target="_blank" rel="noopener" class="d-block mb-1" style="font-size:12px;">
                                                            <i class="fa-solid fa-paperclip"></i> {{ $mark->submission_original_filename ?: 'View submission' }}
                                                        </a>
                                                        <span class="text-secondary d-block mb-1" style="font-size:11px;">{{ $mark->submitted_at->diffForHumans() }}</span>
                                                    @else
                                                        <span class="text-secondary d-block mb-1" style="font-size:11px;">Not submitted</span>
                                                    @endif
                                                    <input type="number" step="0.01" min="0" @if ($item['max_marks']) max="{{ $item['max_marks'] }}" @endif
                                                        class="form-control ot-input" style="width:110px;"
                                                        name="marks[{{ $student->id }}][{{ $type }}]"
                                                        value="{{ old("marks.$student->id.$type", $mark?->marks) }}"
                                                        placeholder="—">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button class="btn btn-lg ot-btn-primary mt-2"><span><i class="fa-solid fa-save"></i> </span> Save marks</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
