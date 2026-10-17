@extends('backend.master')

@section('title')
{{ ___('dashboard.Dashboard') }}
@endsection

@section('content')
<div class="page-content">
    <div class="row ">

        {{-- Counter --}}
        @if (hasPermission('counter_read'))
            <div class="col-xl-3 col-lg-3 col-md-6">
               <a href="{{ route('student.index') }}">
                    <div class="ot_crm_summeryBox2 d-flex align-items-center mb-24">
                        <div class="icon style2">
                            <img class="img-fluid" src="{{ global_asset('backend/assets/images/crm/4.svg') }}" alt="crm_summery1">
                        </div>
                        <div class="summeryContent">
                            <h4>{{ $data['student'] }}</h4>
                            <h1>@if (auth()->user()->role_id == 5) {{ ___('dashboard.Students') }} @else {{ ___('dashboard.Student') }} @endif</h1>
                        </div>
                    </div>
               </a>
            </div>
            @if (auth()->user()->role_id != 5)
            <div class="col-xl-3 col-lg-3 col-md-6">
               <a href="{{ route('parent.index') }}">
                 <div class="ot_crm_summeryBox2 d-flex align-items-center mb-24">
                    <div class="icon style3">
                        <img class="img-fluid" src="{{ global_asset('backend/assets/images/crm/2.svg') }}" alt="crm_summery1">
                    </div>
                    <div class="summeryContent">
                        <h4>{{ $data['parent'] }}</h4>
                        <h1>{{ ___('dashboard.Parent') }}</h1>
                    </div>
                </div>
               </a>
            </div>
            <div class="col-xl-3 col-lg-3 col-md-6">
                <a href="{{ route('users.index') }}">
                    <div class="ot_crm_summeryBox2 d-flex align-items-center mb-24">
                        <div class="icon">
                            <img class="img-fluid" src="{{ global_asset('backend/assets/images/crm/3.svg') }}" alt="crm_summery1">
                        </div>
                        <div class="summeryContent">
                            <h4>{{ $data['teacher'] }}</h4>
                            <h1>{{ ___('academic.teacher') }}</h1>
                        </div>
                    </div>
                </a>
            </div>
            @endif
            <div class="col-xl-3 col-lg-3 col-md-6">
                @if (auth()->user()->role_id == 5)
                    {{-- Teachers: assigned classes for current session (not global sessions list) --}}
                    @if (hasPermission('classes_read'))
                    <a href="{{ route('classes.index') }}">
                        <div class="ot_crm_summeryBox2 d-flex align-items-center mb-24">
                            <div class="icon style4">
                                <img class="img-fluid" src="{{ global_asset('backend/assets/images/crm/1.svg') }}" alt="">
                            </div>
                            <div class="summeryContent">
                                <h4>{{ $data['teacher_classes_count'] ?? 0 }}</h4>
                                <h1>{{ ___('academic.classes') }}</h1>
                            </div>
                        </div>
                    </a>
                    @else
                    <div class="ot_crm_summeryBox2 d-flex align-items-center mb-24" role="group" aria-label="{{ ___('academic.classes') }}">
                        <div class="icon style4">
                            <img class="img-fluid" src="{{ global_asset('backend/assets/images/crm/1.svg') }}" alt="">
                        </div>
                        <div class="summeryContent">
                            <h4>{{ $data['teacher_classes_count'] ?? 0 }}</h4>
                            <h1>{{ ___('academic.classes') }}</h1>
                        </div>
                    </div>
                    @endif
                @else
                    @if (hasPermission('session_read'))
                    <a href="{{ route('sessions.index') }}">
                        <div class="ot_crm_summeryBox2 d-flex align-items-center mb-24">
                            <div class="icon style4">
                                <img class="img-fluid" src="{{ global_asset('backend/assets/images/crm/1.svg') }}" alt="crm_summery1">
                            </div>
                            <div class="summeryContent">
                                <h4>{{ $data['session'] }}</h4>
                                <h1>{{ ___('settings.Session') }}</h1>
                            </div>
                        </div>
                    </a>
                    @else
                    <div class="ot_crm_summeryBox2 d-flex align-items-center mb-24" role="group" aria-label="{{ ___('settings.Session') }}">
                        <div class="icon style4">
                            <img class="img-fluid" src="{{ global_asset('backend/assets/images/crm/1.svg') }}" alt="crm_summery1">
                        </div>
                        <div class="summeryContent">
                            <h4>{{ $data['session'] }}</h4>
                            <h1>{{ ___('settings.Session') }}</h1>
                        </div>
                    </div>
                    @endif
                @endif
            </div>
            @if (auth()->user()->role_id == 5)
            <div class="col-xl-3 col-lg-3 col-md-6">
                @if (hasPermission('subject_assign_read'))
                    <a href="{{ route('assign-subject.index') }}">
                        <div class="ot_crm_summeryBox2 d-flex align-items-center mb-24">
                            <div class="icon style3">
                                <img class="img-fluid" src="{{ global_asset('backend/assets/images/crm/crm_summery3.svg') }}" alt="">
                            </div>
                            <div class="summeryContent">
                                <h4>{{ ($data['teacher_assigned_subjects'] ?? collect())->count() }}</h4>
                                <h1>{{ ___('dashboard.subjects') }}</h1>
                            </div>
                        </div>
                    </a>
                @else
                    <div class="ot_crm_summeryBox2 d-flex align-items-center mb-24" role="group" aria-label="{{ ___('dashboard.subjects') }}">
                        <div class="icon style3">
                            <img class="img-fluid" src="{{ global_asset('backend/assets/images/crm/crm_summery3.svg') }}" alt="">
                        </div>
                        <div class="summeryContent">
                            <h4>{{ ($data['teacher_assigned_subjects'] ?? collect())->count() }}</h4>
                            <h1>{{ ___('dashboard.subjects') }}</h1>
                        </div>
                    </div>
                @endif
            </div>
            @endif
        @endif

        @if (auth()->check() && auth()->user()->role_id == 5 && auth()->user()->staff)
        <div class="col-12 mb-24">
            <div class="ot-card">
                <div class="card-header card_header_border d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="card-title mb-0">
                        <h4 class="mb-0">{{ ___('dashboard.my_assigned_subjects') }}</h4>
                    </div>
                </div>
                <div class="card-body">
                    @php $assignedSubjects = $data['teacher_assigned_subjects'] ?? collect(); @endphp
                    @forelse ($assignedSubjects as $subj)
                        <span class="badge bg-light text-dark border me-2 mb-2 py-2 px-3" style="font-weight:600;font-size:0.8125rem">{{ $subj->name }}@if(!empty($subj->code))<span class="text-muted fw-normal"> — {{ $subj->code }}</span>@endif</span>
                    @empty
                        <p class="text-muted mb-0 small">{{ ___('dashboard.no_assigned_subjects_this_session') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
        @endif

        @if (hasPermission('fees_collesction_read'))
            <div class="col-xxl-8 col-xl-12 ">
                <div class="ot-card chart-card2 ot_heightFull mb-24">

                    {{-- Tittle --}}
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap_20">
                        <div class="card-title ">
                            <h4 class="mb-0">{{___('dashboard.fees_collection')}} ({{ date('Y') }})</h4>
                        </div>
                    </div>

                    {{-- Chart --}}
                    <div id="academic_chart"></div>

                </div>
            </div>
        @endif

        {{-- Revenue --}}
        @if (hasPermission('revenue_read'))
            <div class="col-12 col-lg-12 col-xl-6 col-xxl-4">
                <div class="ot-card ot_heightFull mb-24">
                    <div class="card-header d-flex justify-content-between">
                        <div class="card-title">
                            <h4>{{ ___('dashboard.Revenue') }} ({{ date('Y') }})</h4>
                        </div>
                    </div>
                    <div class="d-flex flex-column align-items-center w-100">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div id="ot-line-chart-income"></div>
                            <div class="chart-custom-content gap-0 flex-column align-items-start">
                                <h3>{{ ___('dashboard.total_income') }}</h3>
                                <div class="d-flex align-items-baseline gap-2">
                                    <h2 class="counter">{{ $data['income'] }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div id="ot-line-chart-expense"></div>
                            <div class="chart-custom-content gap-0 flex-column align-items-start">
                                <h3>{{ ___('dashboard.total_expense') }}</h3>
                                <div class="d-flex align-items-baseline gap-2">
                                    <h2 class="counter">{{ $data['expense'] }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div id="ot-line-chart-revenue"></div>
                            <div class="chart-custom-content gap-0 flex-column align-items-start">
                                <h3>{{ ___('dashboard.total_balance') }}</h3>
                                <div class="d-flex align-items-baseline gap-2">
                                    <h2 class="counter">{{ $data['balance'] }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Fees collection this month --}}
        @if (hasPermission('fees_collection_this_month_read'))
            <div class="col-12 col-lg-12 col-xl-6 col-xxl-6">
                <div class="ot-card mb-24 ot_heightFull">
                    <div class="card-header d-flex justify-content-between">
                        <div class="card-title">
                            <h4>{{ ___("dashboard.fees_collection") }} ({{ date('M Y') }})</h4>
                        </div>
                    </div>
                    <div id="fees_collection_this_month"></div>
                </div>
            </div>
        @endif

        {{-- Income & Expense This Month --}}
        @if (hasPermission('income_expense_read'))
            <div class="col-12 col-lg-12 col-xl-6 col-xxl-6">
                <div class="ot-card mb-24 ot_heightFull">
                    <div class="card-header d-flex justify-content-between">
                        <div class="card-title">
                            <h4>{{ ___("dashboard.income_and_expense") }} ({{ date('M Y') }})</h4>
                        </div>
                    </div>
                    <div id="income_expense_chart_this_month"></div>
                </div>
            </div>
        @endif

        <!-- Upcoming Events -->
        @if (hasPermission('upcoming_events_read'))
            <div class="col-xxl-4 col-xl-6">
                <div class="ot-card chart-card2 ot_heightFull mb-24">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap_10 card_header_border">
                        <div class="card-title">
                            <h4>{{___('dashboard.upcoming_events')}}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="event_upcoming_list">


                            @foreach ($data['events'] as $item)
                                <!-- event_upcoming_single  -->
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
        @endif

        {{-- Attendance --}}
        @if (hasPermission('attendance_chart_read'))
            <div class="col-12 col-lg-12 col-xl-12 col-xxl-8">
                <div class="ot-card mb-24 ot_heightFull">
                    <div class="card-header d-flex justify-content-between">
                        <div class="card-title">
                            <h4>{{ ___("dashboard.todays_attendance") }} ({{ date('d M Y') }})</h4>
                        </div>
                    </div>
                    <div id="today_attendance_chart"></div>
                </div>
            </div>
        @endif

        {{-- Calendar --}}
        @if (hasPermission('calendar_read'))
            <div class="col-lg-8">
                <div class="ot-card mb-24">
                    <div id='calendar'></div>
                </div>
            </div>
        @endif

        {{-- Recent notifications: comments, due-date reminders and anything
             else raised through the system — the header bell has the same
             list, but it's easy to miss up there. --}}
        <div class="{{ hasPermission('calendar_read') ? 'col-lg-4' : 'col-12' }}">
            <div class="ot-card mb-24 bn-dash-notifications">
                <div class="d-flex align-items-center justify-content-between mb-20">
                    <h4 class="mb-0">{{ ___('common.Notifications') }}</h4>
                    @if ($data['recentNotifications']->isNotEmpty())
                        <span class="badge-basic-info-text">{{ $data['recentNotifications']->count() }} unread</span>
                    @endif
                </div>
                @forelse ($data['recentNotifications'] as $notification)
                    <a href="{{ route('viewNotification', $notification->id) }}" class="bn-dash-notification-item">
                        <span class="bn-dash-notification-dot"></span>
                        <span class="bn-dash-notification-body">
                            <span class="bn-dash-notification-title">{{ $notification->title }}</span>
                            <span class="bn-dash-notification-message">{{ $notification->message }}</span>
                            <span class="bn-dash-notification-time">{{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}</span>
                        </span>
                    </a>
                @empty
                    <p class="text-secondary mb-0">{{ ___('common.no_data_available') }}</p>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection

@push('css')
    <style>
        .bn-dash-notification-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 0;
            border-bottom: 1px solid var(--ot-border-primary, #eaeaea);
        }
        .bn-dash-notification-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .bn-dash-notification-item:first-child {
            padding-top: 0;
        }
        .bn-dash-notification-dot {
            flex: 0 0 8px;
            width: 8px;
            height: 8px;
            margin-top: 6px;
            border-radius: 50%;
            background: linear-gradient(130.57deg, #936DFF -0.48%, #3A5EE3 71.79%);
        }
        .bn-dash-notification-body {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .bn-dash-notification-title {
            font-weight: 600;
            font-size: 14px;
            color: var(--ot-text-title);
        }
        .bn-dash-notification-message {
            font-size: 13px;
            color: var(--ot-text-secondary, var(--ot-text-primary));
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .bn-dash-notification-time {
            font-size: 12px;
            color: var(--ot-text-secondary, var(--ot-text-primary));
            opacity: .7;
            margin-top: 2px;
        }
    </style>
@endpush

@push('script')
<script src="{{ global_asset('backend') }}/assets/js/apex-chart.js"></script>
@endpush
