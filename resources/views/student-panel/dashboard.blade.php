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

    @include('backend.partials.learning-engine-styles')

    @if (!empty($data['learning_home']))
        @php
            $lh = $data['learning_home'];
            $mc = $lh['mastery_counts'];
            $mcTotal = max(1, array_sum($mc));
        @endphp

        @if (!empty($lh['milestone']))
            <div class="bn-milestone">
                <div class="bn-milestone__icon"><i class="fa-solid fa-star"></i></div>
                <div>
                    <p class="bn-milestone__title">{{ ___('common.milestone_reached') }}: {{ $lh['milestone']['skill_title'] }}</p>
                    <p class="bn-milestone__line">{{ $lh['milestone']['line'] }}</p>
                </div>
            </div>
        @endif

        <div class="bn-panel">
            <div class="bn-panel__body">
                <div class="bn-panel__header">
                    @if ($lh['greeting_image'])
                        <div class="bn-mascot-badge"><img src="{{ $lh['greeting_image'] }}" alt="{{ $lh['greeting_name'] }}"></div>
                    @endif
                    <div style="min-width:0;">
                        <p class="bn-panel__eyebrow">{{ $lh['greeting_name'] }}</p>
                        <p class="bn-panel__line">{{ $lh['greeting_line'] }}</p>
                    </div>
                </div>

                @if (!empty($lh['next_action']))
                    @php $na = $lh['next_action']; @endphp
                    <div class="bn-next-action @if($na['type'] === 'caught_up') bn-next-action--caught-up @endif">
                        <div class="bn-next-action__icon">
                            <i class="fa-solid @if($na['type'] === 'caught_up') fa-champagne-glasses @elseif($na['type'] === 'review') fa-magnifying-glass @else fa-compass @endif"></i>
                        </div>
                        <div>
                            <p class="bn-next-action__eyebrow">{{ ___('common.your_next_best_action') }}</p>
                            <p class="bn-next-action__title">{{ optional($na['skill'])->title ?? ___('common.all_caught_up') }}</p>
                            <p class="bn-next-action__reason">{{ $na['reason'] }}</p>
                        </div>
                    </div>
                @endif

                <hr class="bn-divider">

                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="bn-section-label"><i class="fa-solid fa-route"></i> {{ ___('common.whats_next') }}</div>
                        @forelse ($lh['next_skills'] as $skill)
                            <div class="bn-skill-tile">
                                <div>
                                    <div class="bn-skill-tile__title">{{ $skill->title }}</div>
                                    <div class="bn-skill-tile__meta">{{ $skill->subject->name ?? '' }}</div>
                                </div>
                            </div>
                        @empty
                            <p class="bn-empty-note">{{ ___('common.no_skills_set_up_yet_for_your_grade') }}</p>
                        @endforelse
                    </div>
                    <div class="col-lg-5">
                        <div class="bn-section-label"><i class="fa-solid fa-chart-simple"></i> {{ ___('common.your_skill_snapshot') }}</div>
                        <div class="bn-progress">
                            <div class="bn-progress__seg bn-progress__seg--not-started" style="width:{{ $mc['not_started'] / $mcTotal * 100 }}%"></div>
                            <div class="bn-progress__seg bn-progress__seg--developing" style="width:{{ $mc['developing'] / $mcTotal * 100 }}%"></div>
                            <div class="bn-progress__seg bn-progress__seg--proficient" style="width:{{ $mc['proficient'] / $mcTotal * 100 }}%"></div>
                            <div class="bn-progress__seg bn-progress__seg--advanced" style="width:{{ $mc['advanced'] / $mcTotal * 100 }}%"></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="bn-pill bn-pill--not-started">{{ ___('common.not_started') }} · {{ $mc['not_started'] }}</span>
                            <span class="bn-pill bn-pill--developing">{{ ___('common.developing') }} · {{ $mc['developing'] }}</span>
                            <span class="bn-pill bn-pill--proficient">{{ ___('common.proficient') }} · {{ $mc['proficient'] }}</span>
                            <span class="bn-pill bn-pill--advanced">{{ ___('common.advanced') }} · {{ $mc['advanced'] }}</span>
                        </div>
                    </div>
                </div>

                @if (!empty($lh['needs_review']) && count($lh['needs_review']))
                    <hr class="bn-divider">
                    <div class="bn-section-label"><i class="fa-solid fa-magnifying-glass"></i> {{ ___('common.lets_investigate') }}</div>
                    <p class="bn-panel__line" style="margin-bottom:12px;">{{ $lh['review_line'] }}</p>
                    @foreach ($lh['needs_review'] as $row)
                        <div class="bn-skill-tile bn-skill-tile--review">
                            <div>
                                <div class="bn-skill-tile__title">{{ $row['skill']->title }}</div>
                                @if ($row['skill']->subject)
                                    <div class="bn-skill-tile__meta">{{ $row['skill']->subject->name }}</div>
                                @endif
                            </div>
                            <span class="bn-pill bn-pill--review">{{ $row['stage']['label'] }}</span>
                        </div>
                    @endforeach
                @endif

                @if (!empty($lh['due_for_review']) && count($lh['due_for_review']))
                    <hr class="bn-divider">
                    <div class="bn-section-label"><i class="fa-solid fa-rotate"></i> {{ ___('common.refresh_time') }}</div>
                    <p class="bn-panel__line" style="margin-bottom:12px;">{{ $lh['refresher_line'] }}</p>
                    @foreach ($lh['due_for_review'] as $skill)
                        <div class="bn-skill-tile bn-skill-tile--refresh">
                            <div>
                                <div class="bn-skill-tile__title">{{ $skill->title }}</div>
                                @if ($skill->subject)
                                    <div class="bn-skill-tile__meta">{{ $skill->subject->name }}</div>
                                @endif
                            </div>
                            <span class="bn-pill bn-pill--refresh">{{ ___('common.quick_refresh') }}</span>
                        </div>
                    @endforeach
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