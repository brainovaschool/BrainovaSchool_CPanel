@extends('student-panel.partials.master')

@section('title')
{{ ___('common.Dashboard') }}
@endsection

@push('css')
<style>
/* Scores card: align like other summary boxes */
.student-dash-scores.ot_crm_summeryBox {
  align-items: center;
}
.student-dash-scores.ot_crm_summeryBox > .icon {
  flex-shrink: 0;
  align-self: center;
  display: flex;
  align-items: center;
  justify-content: center;
}
.student-dash-scores .summeryContent {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
</style>
@endpush


@section('content')
<div class="page-content">

    @if (!empty($data['learning_home']))
        @php $lh = $data['learning_home']; @endphp

        @if (!empty($lh['milestone']))
            <div class="card ot-card mb-24" style="border-left:4px solid #2f8f5b;">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="fa-solid fa-star" style="color:#2f8f5b;font-size:1.6rem;"></i>
                    <div>
                        <h5 class="mb-1">{{ ___('common.milestone_reached') }}: {{ $lh['milestone']['skill_title'] }}</h5>
                        <p class="mb-0 gray-color">{{ $lh['milestone']['line'] }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="card ot-card mb-24">
            <div class="card-body">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    @if ($lh['greeting_image'])
                        <img src="{{ $lh['greeting_image'] }}"
                            alt="{{ $lh['greeting_name'] }}" style="height:72px;width:auto;flex-shrink:0;">
                    @endif
                    <div style="min-width:0;">
                        <h5 class="mb-1">{{ $lh['greeting_name'] }} says:</h5>
                        <p class="mb-0 gray-color">{{ $lh['greeting_line'] }}</p>
                    </div>
                </div>

                <hr class="my-3">

                <div class="row g-3">
                    <div class="col-lg-7">
                        <h6 class="mb-2">{{ ___('common.whats_next') }}</h6>
                        @forelse ($lh['next_skills'] as $skill)
                            <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2">
                                <div>
                                    <div class="fw-bold">{{ $skill->title }}</div>
                                    <small class="gray-color">{{ $skill->subject->name ?? '' }}</small>
                                </div>
                            </div>
                        @empty
                            <p class="gray-color mb-0">{{ ___('common.no_skills_set_up_yet_for_your_grade') }}</p>
                        @endforelse
                    </div>
                    <div class="col-lg-5">
                        <h6 class="mb-2">{{ ___('common.your_skill_snapshot') }}</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge-basic-warning-text">{{ ___('common.not_started') }}: {{ $lh['mastery_counts']['not_started'] }}</span>
                            <span class="badge-basic-info-text">{{ ___('common.developing') }}: {{ $lh['mastery_counts']['developing'] }}</span>
                            <span class="badge-basic-primary-text">{{ ___('common.proficient') }}: {{ $lh['mastery_counts']['proficient'] }}</span>
                            <span class="badge-basic-success-text">{{ ___('common.advanced') }}: {{ $lh['mastery_counts']['advanced'] }}</span>
                        </div>
                    </div>
                </div>

                @if (!empty($lh['needs_review']) && count($lh['needs_review']))
                    <hr class="my-3">
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-magnifying-glass mt-1" style="color:#e8664f;"></i>
                        <div style="min-width:0;">
                            <h6 class="mb-1">{{ ___('common.lets_investigate') }}</h6>
                            <p class="gray-color mb-2">{{ $lh['review_line'] }}</p>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($lh['needs_review'] as $skill)
                                    <span class="badge-basic-danger-text">{{ $skill->title }}@if($skill->subject) — {{ $skill->subject->name }}@endif</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-5 g-3 mb-24">
        <div class="col">
            <div class="ot_crm_summeryBox d-flex align-items-center h-100">
                <div class="icon">
                    <img class="img-fluid" src="{{ global_asset('backend/assets/images/crm/crm_summery1.svg') }}" alt="">
                </div>
                <div class="summeryContent">
                    <h4>{{ ___('academic.class') }}</h4>
                    <h1>{{ $data['totalClass'] }}</h1>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="ot_crm_summeryBox d-flex align-items-center h-100">
                <div class="icon">
                    <img class="img-fluid" src="{{ global_asset('backend/assets/images/crm/crm_summery2.svg') }}" alt="">
                </div>
                <div class="summeryContent">
                    <h4>{{ ___('academic.subject') }}</h4>
                    <h1>{{ $data['totalSubject'] }}</h1>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="ot_crm_summeryBox d-flex align-items-center h-100">
                <div class="icon">
                    <img class="img-fluid" src="{{ global_asset('backend/assets/images/crm/crm_summery3.svg') }}" alt="">
                </div>
                <div class="summeryContent">
                    <h4>{{ ___('academic.teacher') }}</h4>
                    <h1>{{ $data['totalTeacher'] }}</h1>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="ot_crm_summeryBox d-flex align-items-center h-100">
                <div class="icon">
                    <img class="img-fluid" src="{{ global_asset('backend/assets/images/crm/crm_summery4.svg') }}" alt="">
                </div>
                <div class="summeryContent">
                    <h4>{{ ___('settings.event') }}</h4>
                    <h1>{{ $data['totalEvent'] }}</h1>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="ot_crm_summeryBox student-dash-scores d-flex h-100">
                <div class="icon">
                    <img class="img-fluid" src="{{ global_asset('backend/assets/images/crm/crm_summery2.svg') }}" alt="">
                </div>
                <div class="summeryContent">
                    <h4>{{ ___('examination.scores') }}</h4>
                    <h1>@if(($data['homework_total_marks'] ?? null) !== null){{ $data['homework_total_marks'] }}@else—@endif</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-xl-6">
            <div class="ot-card chart-card2 ot_heightFull mb-24">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap_10 card_header_border">
                    <div class="card-title">
                        <h4>{{___('student_info.student_info')}}</h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <img class="mt-2" width="100" height="100" src="{{ @globalAsset(@$data['student']->user->upload->path, '100X100.webp') }}" alt="{{ @$data['student']->first_name }}">
                            <div class="d-flex justify-content-between align-content-center mb-3 mt-2">
                                <div class="align-self-center">
                                    <h5 class="title">{{ ___('student_info.student_name') }}</h5>
                                    <p class="paragraph">{{ @$data['student']->first_name }} {{ @$data['student']->last_name }}</p>
                                    <input type="hidden" name="student_id" id="student_id" value="{{ @$data['student']->id }}" />
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-content-center mb-3">
                                <div class="align-self-center">
                                    <h5 class="title">{{ ___('student_info.admission_no') }}</h5>
                                    <p class="paragraph">{{ @$data['student']->admission_no }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex justify-content-between align-content-center mb-3">
                                <div class="align-self-center">
                                    <h5 class="title">{{ ___('academic.class') }} ({{ ___('academic.section') }})</h5>
                                    <p class="paragraph">{{ @$data['student']->sessionStudentDetails->class->name }} ({{ @$data['student']->sessionStudentDetails->section->name }})</p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-content-center mb-3">
                                <div class="align-self-center">
                                    <h5 class="title">{{ ___('student_info.roll_no') }}</h5>
                                    <p class="paragraph">{{ @$data['student']->roll_no }}</p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-content-center mb-3">
                                <div class="align-self-center">
                                    <h5 class="title">{{ ___('student_info.guardian_name') }}</h5>
                                    <p class="paragraph">{{ @$data['student']->parent->guardian_name }}</p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-content-center mb-3">
                                <div class="align-self-center">
                                    <h5 class="title">{{ ___('student_info.mobile_number') }}</h5>
                                    <p class="paragraph">{{ @$data['student']->mobile }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="ot-card chart-card2 ot_heightFull mb-24">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap_10 card_header_border">
                    <div class="card-title">
                        <h4>{{___('dashboard.upcoming_events')}}</h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="event_upcoming_list">
                        @foreach ($data['events'] as $item)
                            <div class="event_upcoming_single d-flex align-items-center gap_20 flex-wrap">
                                <div class="icon d-flex align-items-center flex-column justify-content-center">
                                    <h4>{{ date('d', strtotime($item->date)) }}</h4>
                                    <h5>{{ date('D', strtotime($item->date)) }}</h5>
                                </div>
                                <div class="event_content_info">
                                    <h4><a href="{{ route('event.edit', $item->id) }}">{!! Str::limit($item->title,40) !!}</a></h4>
                                    <p class="d-flex align-items-center gap-2 "> <svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M8.42676 1.50024H10.4268C10.5594 1.50024 10.6865 1.55292 10.7803 1.64669C10.8741 1.74046 10.9268 1.86764 10.9268 2.00024V10.0002C10.9268 10.1329 10.8741 10.26 10.7803 10.3538C10.6865 10.4476 10.5594 10.5002 10.4268 10.5002H1.42676C1.29415 10.5002 1.16697 10.4476 1.0732 10.3538C0.979436 10.26 0.926758 10.1329 0.926758 10.0002V2.00024C0.926758 1.86764 0.979436 1.74046 1.0732 1.64669C1.16697 1.55292 1.29415 1.50024 1.42676 1.50024H3.42676V0.500244H4.42676V1.50024H7.42676V0.500244H8.42676V1.50024ZM9.92676 5.50024H1.92676V9.50024H9.92676V5.50024ZM7.42676 2.50024H4.42676V3.50024H3.42676V2.50024H1.92676V4.50024H9.92676V2.50024H8.42676V3.50024H7.42676V2.50024ZM2.92676 6.50024H3.92676V7.50024H2.92676V6.50024ZM5.42676 6.50024H6.42676V7.50024H5.42676V6.50024ZM7.92676 6.50024H8.92676V7.50024H7.92676V6.50024Z" fill="#6B6B6B" />
                                        </svg>
                                        <span>{{ $item->date == date('Y-m-d') ? 'Today' : dateFormat($item->date) }} | {{ timeFormat($item->start_time) }} - {{ timeFormat($item->end_time) }}</span>
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection