@extends('parent-panel.partials.master')

@section('title')
{{ ___('common.Dashboard') }}
@endsection

@section('content')
    <div class="row">
        <form action="{{ route('parent-panel-student.search') }}" method="post" id="marksheed" enctype="multipart/form-data">
            @csrf
            <div class="card ot-card mb-24 position-relative z_1">
                <div class="card-header d-flex align-items-center gap-4 flex-wrap">
                    <h3 class="mb-0">{{ ___('common.Filtering') }}</h3>

                    <div class="card_header_right d-flex align-items-center gap-3 flex-fill justify-content-end flex-wrap">
                        <!-- table_searchBox -->

                        <div class="single_large_selectBox">
                            <select class="nice-select niceSelect bordered_style wide @error('student') is-invalid @enderror" name="student">
                                <option value="">{{ ___('student_info.select_student') }}</option>
                                @foreach ($data['students'] as $item)
                                <option {{ old('student', Session::get('student_id')) == $item->id ? 'selected' : '' }} value="{{ $item->id }}">{{ $item->first_name }} {{ $item->last_name }}
                                    @endforeach
                            </select>
                            @error('student')
                            <div id="validationServer04Feedback" class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <button class="btn btn-lg ot-btn-primary" type="submit">
                            {{___('common.Search')}}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @include('backend.partials.learning-engine-styles')
    @include('backend.partials.learning-engine-charts')
    @include('backend.partials.dashboard-v2-styles')

    @if($data['student'])
    @php
        $lh = $data['learning_home'] ?? null;
        $mc = $lh['mastery_counts'] ?? null;
    @endphp

    <div class="bn-dv2">

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="bn-dv2-greet">{{ ___('common.learning_snapshot') }}</h1>
                <p class="bn-dv2-sub">{{ @$data['student']->first_name }} {{ @$data['student']->last_name }}</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                @include('backend.partials.theme-picker', ['onLight' => true])
                @if (dashboard_feature_enabled('parent', 'learning_guide'))
                    <a href="{{ route('parent-panel-dashboard.learning-guide') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-circle-question me-1"></i> {{ ___('common.what_does_this_mean') }}
                    </a>
                @endif
            </div>
        </div>

        @if (!empty($data['weekly_wins']) && $data['weekly_wins']['has_wins'] && dashboard_feature_enabled('parent', 'weekly_wins'))
            @php $ww = $data['weekly_wins']; @endphp
            <div class="bn-dv2-banner">
                <i class="fa-solid fa-heart"></i>
                <div>
                    <div class="t">{{ ___('common.good_news_this_week') }}</div>
                    <div class="m">
                        @if ($ww['mastered_titles']->count())
                            {{ $data['student']->first_name }} {{ ___('common.mastered') }} {{ $ww['mastered_titles']->count() }}
                            {{ $ww['mastered_titles']->count() === 1 ? ___('common.new_skill') : ___('common.new_skills') }}
                            ({{ $ww['mastered_titles']->implode(', ') }}){{ $ww['correct_this_week'] > 0 ? ' ' . ___('common.and') : '' }}
                        @endif
                        @if ($ww['correct_this_week'] > 0)
                            {{ ___('common.answered') }} {{ $ww['correct_this_week'] }} {{ ___('common.questions_correctly_this_week') }}.
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Row 1: profile card + quick stats --}}
        <div class="bn-dv2-grid" style="grid-template-columns:1fr 1.6fr;">
            <div class="bn-dv2-card bn-dv2-profile">
                <img src="{{ @globalAsset(@$data['student']->user->upload->path, '100X100.webp') }}" alt="{{ @$data['student']->first_name }}">
                <div style="min-width:0;">
                    <div class="name">{{ @$data['student']->first_name }} {{ @$data['student']->last_name }}</div>
                    <ul class="meta">
                        <li><i class="fa-solid fa-graduation-cap"></i>{{ @$data['student']->sessionStudentDetails->class->name }} ({{ @$data['student']->sessionStudentDetails->section->name }})</li>
                        <li><i class="fa-solid fa-id-card"></i>{{ ___('student_info.admission_no') }}: {{ @$data['student']->admission_no }}</li>
                        <li><i class="fa-solid fa-hashtag"></i>{{ ___('student_info.roll_no') }}: {{ @$data['student']->roll_no }}</li>
                        @if (@$data['student']->mobile)
                            <li><i class="fa-solid fa-phone"></i>{{ @$data['student']->mobile }}</li>
                        @endif
                    </ul>
                </div>
            </div>
            <div class="bn-dv2-card">
                <div class="bn-dv2-mini-stats" style="grid-template-columns:repeat(5,1fr);">
                    <div class="bn-dv2-mini-stat"><div class="icn"><i class="fa-solid fa-chalkboard"></i></div><div class="v">{{ $data['totalClass'] }}</div><div class="k">{{ ___('academic.class') }}</div></div>
                    <div class="bn-dv2-mini-stat"><div class="icn"><i class="fa-solid fa-book"></i></div><div class="v">{{ $data['totalSubject'] }}</div><div class="k">{{ ___('academic.subject') }}</div></div>
                    <div class="bn-dv2-mini-stat"><div class="icn"><i class="fa-solid fa-chalkboard-user"></i></div><div class="v">{{ $data['totalTeacher'] }}</div><div class="k">{{ ___('academic.teacher') }}</div></div>
                    <div class="bn-dv2-mini-stat"><div class="icn"><i class="fa-solid fa-calendar-days"></i></div><div class="v">{{ $data['totalEvent'] }}</div><div class="k">{{ ___('settings.event') }}</div></div>
                    <div class="bn-dv2-mini-stat"><div class="icn"><i class="fa-solid fa-star"></i></div><div class="v">@if(($data['homework_total_marks'] ?? null) !== null){{ $data['homework_total_marks'] }}@else—@endif</div><div class="k">{{ ___('examination.scores') }}</div></div>
                </div>
            </div>
        </div>

        @if (!empty($lh))
            {{-- Row 2: Brain Level, Knowledge Tree, Skill Mastery --}}
            <div class="bn-dv2-grid bn-dv2-grid--auto">
                @if (!empty($lh['brain_level']) && dashboard_feature_enabled('parent', 'brain_level'))
                    @php $bl = $lh['brain_level']; @endphp
                    <div class="bn-dv2-card">
                        <div class="bn-dv2-card-label"><i class="fa-solid fa-brain"></i>{{ ___('common.brain_level') }}</div>
                        <div class="bn-dv2-ring-wrap">
                            <div class="bn-ring" data-value="{{ $bl['progress_pct'] }}" data-color="var(--bn-primary)" data-track="var(--bn-primary-soft)"></div>
                            <div class="bn-dv2-ring-num">{{ $bl['level'] }}</div>
                            <div class="bn-dv2-ring-cap">{{ $bl['progress_pct'] }}% · {{ ___('common.to_next_level') }} {{ $bl['level'] + 1 }}</div>
                        </div>
                    </div>
                @endif

                @if (!empty($lh['knowledge_tree']) && dashboard_feature_enabled('parent', 'knowledge_tree'))
                    @php $kt = $lh['knowledge_tree']; @endphp
                    <div class="bn-dv2-card">
                        <div class="bn-dv2-card-label"><i class="fa-solid fa-seedling"></i>{{ ___('common.knowledge_tree') }}</div>
                        <div class="bn-dv2-card-body">
                            <div class="bn-dv2-tree-row">
                                @include('backend.partials.knowledge-tree', ['tree' => $kt])
                                <div class="bn-dv2-tree-info">
                                    <div class="bn-dv2-tree-name">{{ $kt['label'] }}</div>
                                    <div class="bn-dv2-tree-sub">{{ $kt['active_days'] }} {{ ___('common.days_growing') }}</div>
                                    @if ($kt['at_cap'])
                                        <div class="bn-dv2-tree-sub"><b>{{ ___('common.fully_grown') }}</b></div>
                                    @else
                                        <div class="bn-dv2-tree-bar" role="img"
                                            aria-label="{{ $kt['days_in_band'] }} of 10 days toward the next stage">
                                            <span style="width:{{ $kt['days_in_band'] * 10 }}%;"></span>
                                        </div>
                                        <div class="bn-dv2-tree-sub">{{ $kt['days_to_grow'] }} {{ ___('common.more_days_to_grow_taller') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if (dashboard_feature_enabled('parent', 'learning_snapshot'))
                    <div class="bn-dv2-card">
                        <div class="bn-dv2-card-label"><i class="fa-solid fa-chart-pie"></i>{{ ___('common.skill_mastery_overview') }}</div>
                        <div style="display:flex; gap:12px; align-items:center;">
                            <div class="bn-donut" style="width:80px;height:80px;flex-shrink:0;"
                                data-segments="{{ $mc['not_started'] }},{{ $mc['developing'] }},{{ $mc['proficient'] }},{{ $mc['advanced'] }}"
                                data-colors="var(--bn-not-started),var(--bn-developing),var(--bn-proficient),var(--bn-advanced)"></div>
                            <ul class="bn-dv2-legend">
                                <li><span class="dot" style="background:var(--bn-not-started)"></span>{{ ___('common.not_started') }}<b>{{ $mc['not_started'] }}</b></li>
                                <li><span class="dot" style="background:var(--bn-developing)"></span>{{ ___('common.developing') }}<b>{{ $mc['developing'] }}</b></li>
                                <li><span class="dot" style="background:var(--bn-proficient)"></span>{{ ___('common.proficient') }}<b>{{ $mc['proficient'] }}</b></li>
                                <li><span class="dot" style="background:var(--bn-advanced)"></span>{{ ___('common.advanced') }}<b>{{ $mc['advanced'] }}</b></li>
                            </ul>
                        </div>
                    </div>
                @endif
            </div>

            @if (dashboard_feature_enabled('parent', 'learning_snapshot'))
                {{-- Row 3: currently working on / needs review --}}
                <div class="bn-dv2-grid bn-dv2-grid--auto">
                    <div class="bn-dv2-card">
                        <div class="bn-dv2-card-label"><i class="fa-solid fa-route"></i>{{ ___('common.currently_working_on') }}</div>
                        @forelse ($lh['next_skills'] as $skill)
                            <div class="bn-dv2-list-row">
                                <span class="num">{{ $loop->iteration }}</span>
                                <span class="t">{{ $skill->title }}</span>
                            </div>
                        @empty
                            <p class="bn-empty-note">{{ ___('common.no_skills_set_up_yet_for_your_grade') }}</p>
                        @endforelse
                    </div>

                    @if (!empty($lh['needs_review']) && count($lh['needs_review']))
                        <div class="bn-dv2-card">
                            <div class="bn-dv2-card-label"><i class="fa-solid fa-magnifying-glass"></i>{{ ___('common.could_use_another_look') }}</div>
                            @foreach ($lh['needs_review'] as $row)
                                <div class="bn-dv2-list-row">
                                    <span class="t">{{ $row['skill']->title }}</span>
                                    <span class="bn-dv2-tag bn-dv2-tag--{{ $row['stage']['level'] === 'new' ? 'warn' : 'ok' }}">{{ $row['stage']['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- Row 4: Badges, Personal Best, Verified Skills --}}
            @if (dashboard_feature_enabled('parent', 'badges') || dashboard_feature_enabled('parent', 'personal_best') || dashboard_feature_enabled('parent', 'verified_skills'))
                <div class="bn-dv2-grid bn-dv2-grid--auto">
                    @if (!empty($lh['badges']) && dashboard_feature_enabled('parent', 'badges'))
                        <div class="bn-dv2-card">
                            <div class="bn-dv2-card-label"><i class="fa-solid fa-award"></i>{{ ___('common.badges') }}</div>
                            <div class="bn-dv2-badge-shelf">
                                @foreach ($lh['badges'] as $badge)
                                    <div class="bn-dv2-badge-chip bn-dv2-badge-chip--{{ $badge['tier'] }}">
                                        <div class="icn">🏅</div>
                                        <div>{{ $badge['subject'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (!empty($lh['personal_best']) && dashboard_feature_enabled('parent', 'personal_best'))
                        @php $pb = $lh['personal_best']; @endphp
                        <div class="bn-dv2-card">
                            <div class="bn-dv2-card-label"><i class="fa-solid fa-chart-line"></i>{{ ___('common.personal_best') }}</div>
                            <div class="bn-dv2-ring-wrap">
                                <div class="bn-ring" data-value="{{ $pb['this_week'] }}"
                                    data-color="@if($pb['delta'] < 0) var(--bn-not-started) @else var(--bn-advanced) @endif"
                                    data-track="@if($pb['delta'] < 0) var(--bn-not-started-soft) @else var(--bn-advanced-soft) @endif"></div>
                                <div class="bn-dv2-ring-num">{{ $pb['this_week'] }}%</div>
                                <div class="bn-dv2-ring-cap">{{ ___('common.this_week') }} · {{ $pb['delta'] > 0 ? '+' : '' }}{{ $pb['delta'] }}% {{ ___('common.vs_last_week') }}</div>
                            </div>
                        </div>
                    @endif

                    @if (!empty($lh['verified_skills']) && dashboard_feature_enabled('parent', 'verified_skills'))
                        <div class="bn-dv2-card">
                            <div class="bn-dv2-card-label"><i class="fa-solid fa-certificate"></i>{{ ___('common.verified_skills') }}</div>
                            @foreach ($lh['verified_skills'] as $vs)
                                <div class="bn-dv2-vs-row">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span class="t">{{ $vs['skill']->title }}</span>
                                    <span class="m">{{ $vs['accuracy'] }}% · {{ $vs['mastered_at']->format('d M') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        @endif

        {{-- Contact info + upcoming events --}}
        <div class="bn-dv2-grid" style="grid-template-columns:1fr 1fr;">
            <div class="bn-dv2-card">
                <div class="bn-dv2-card-label"><i class="fa-solid fa-address-card"></i>{{ ___('student_info.student_info') }}</div>
                <ul class="meta" style="list-style:none; margin:0; padding:0; display:grid; gap:6px; font-size:.84rem; color:var(--dv2-ink-soft, #5c6270);">
                    <li><i class="fa-solid fa-user-tie" style="width:16px;"></i> {{ ___('student_info.guardian_name') }}: {{ @$data['student']->parent->guardian_name }}</li>
                    <li><i class="fa-solid fa-phone" style="width:16px;"></i> {{ ___('student_info.mobile_number') }}: {{ @$data['student']->mobile }}</li>
                </ul>
            </div>
            <div class="bn-dv2-card">
                <div class="bn-dv2-card-label"><i class="fa-solid fa-calendar-days"></i>{{___('dashboard.upcoming_events')}}</div>
                @forelse ($data['events'] as $item)
                    <div class="bn-dv2-hw-row" style="padding-block:6px;">
                        <div class="icn"><i class="fa-solid fa-calendar-day"></i></div>
                        <div>
                            <div class="t"><a href="{{ route('event.edit', $item->id) }}" style="color:inherit;">{!! Str::limit($item->title, 40) !!}</a></div>
                            <div class="m">{{ $item->date == date('Y-m-d') ? ___('common.today') : dateFormat($item->date) }} | {{ timeFormat($item->start_time) }} - {{ timeFormat($item->end_time) }}</div>
                        </div>
                    </div>
                @empty
                    <p class="bn-empty-note">{{ ___('common.no_data_available') }}</p>
                @endforelse
            </div>
        </div>

    </div>
    @endif
@endsection
