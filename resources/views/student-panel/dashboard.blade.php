@extends('student-panel.partials.master')

@section('title')
{{ ___('common.Dashboard') }}
@endsection

@section('content')
<div class="page-content">

    @include('backend.partials.learning-engine-styles')
    @include('backend.partials.learning-engine-charts')
    @include('backend.partials.dashboard-v2-styles')

    @php
        $keaSpeakText = null;
        if (!empty($data['learning_home'])) {
            $lhForSpeech  = $data['learning_home'];
            $pendingGoals = collect($data['daily_goals'] ?? [])->where('completed', false)->count();

            $speakParts   = [\App\Support\Character::line('kea', $lhForSpeech['is_comeback'] ? 'comeback' : 'welcome')];
            $speakParts[] = 'You are Brain Level ' . ($lhForSpeech['brain_level']['level'] ?? 1) . '.';
            if (!empty($lhForSpeech['next_action']['reason'])) {
                $speakParts[] = $lhForSpeech['next_action']['reason'];
            }
            $nudge = $lhForSpeech['inactivity_nudge'] ?? null;
            if ($nudge && (empty($lhForSpeech['next_action']['skill']) || $lhForSpeech['next_action']['skill']->id !== $nudge['skill']->id)) {
                $speakParts[] = $nudge['line'];
            }
            $speakParts[] = 'You have ' . ($lhForSpeech['mastery_counts']['advanced'] ?? 0) . ' ' . ___('common.skills_mastered_so_far') . ($pendingGoals ? ', and ' . $pendingGoals . ' ' . ___('common.goals_left_today') : '') . '.';

            $keaSpeakText = implode(' ', $speakParts);
        }
        $avatarProfile = $data['avatar_profile'] ?? null;
        $keaImage = optional($avatarProfile)->avatar && $avatarProfile->avatar->image
            ? globalAsset($avatarProfile->avatar->image)
            : (setting('ai_helper_student_mascot') ? globalAsset(setting('ai_helper_student_mascot')) : null);
        $keaName = optional($avatarProfile)->avatar_name ?: ___('common.my_avatar');
        $keaVoicePreset = optional($avatarProfile)->voice_preset ?: 'classic';

        $lh = $data['learning_home'] ?? null;
        $mc = $lh['mastery_counts'] ?? null;
    @endphp

    <div class="bn-dv2">

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="bn-dv2-greet">{{ ___('common.welcome_back') }}, {{ @$data['student']->first_name }}! 👋</h1>
                <p class="bn-dv2-sub">{{ $lh['greeting_line'] ?? ___('common.keep_going_note') }}</p>
            </div>
            @include('backend.partials.theme-picker', ['onLight' => true])
        </div>

        @if (!empty($lh['milestone']) && dashboard_feature_enabled('student', 'milestone_banner'))
            <div class="bn-dv2-banner">
                <i class="fa-solid fa-star"></i>
                <div>
                    <div class="t">{{ ___('common.milestone_reached') }}: {{ $lh['milestone']['skill_title'] }}</div>
                    <div class="m">{{ $lh['milestone']['line'] }}</div>
                </div>
            </div>
        @endif

        {{-- Row 1: profile, Kea, quick stats --}}
        <div class="bn-dv2-grid bn-dv2-grid--hero" style="margin-top:10px;">
            <div class="bn-dv2-card bn-dv2-profile">
                <img src="{{ @globalAsset(@$data['student']->user->upload->path, '100X100.webp') }}" alt="{{ @$data['student']->first_name }}">
                <div style="min-width:0;">
                    <div class="name">{{ @$data['student']->first_name }} {{ @$data['student']->last_name }}</div>
                    <ul class="meta">
                        <li><i class="fa-solid fa-graduation-cap"></i>{{ @$data['student']->sessionStudentDetails->class->name }} ({{ @$data['student']->sessionStudentDetails->section->name }})</li>
                        <li><i class="fa-solid fa-id-card"></i>{{ ___('student_info.admission_no') }}: {{ @$data['student']->admission_no }}</li>
                        <li><i class="fa-solid fa-hashtag"></i>{{ ___('student_info.roll_no') }}: {{ @$data['student']->roll_no }}</li>
                        @if (@$data['student']->parent->guardian_name)
                            <li><i class="fa-solid fa-user-tie"></i>{{ @$data['student']->parent->guardian_name }}</li>
                        @endif
                        @if (@$data['student']->mobile)
                            <li><i class="fa-solid fa-phone"></i>{{ @$data['student']->mobile }}</li>
                        @endif
                    </ul>
                </div>
            </div>

            @if ($keaSpeakText && dashboard_feature_enabled('student', 'kea_voice'))
                @include('backend.partials.character-voice-avatar', ['image' => $keaImage, 'name' => $keaName, 'speakText' => $keaSpeakText, 'voicePreset' => $keaVoicePreset, 'variant' => 'card'])
            @endif

            <div class="bn-dv2-card">
                <div class="bn-dv2-mini-stats">
                    <div class="bn-dv2-mini-stat"><div class="icn"><i class="fa-solid fa-chalkboard"></i></div><div class="v">{{ $data['totalClass'] }}</div><div class="k">{{ ___('academic.class') }}</div></div>
                    <div class="bn-dv2-mini-stat"><div class="icn"><i class="fa-solid fa-book"></i></div><div class="v">{{ $data['totalSubject'] }}</div><div class="k">{{ ___('academic.subject') }}</div></div>
                    <div class="bn-dv2-mini-stat"><div class="icn"><i class="fa-solid fa-chalkboard-user"></i></div><div class="v">{{ $data['totalTeacher'] }}</div><div class="k">{{ ___('academic.teacher') }}</div></div>
                    <div class="bn-dv2-mini-stat"><div class="icn"><i class="fa-solid fa-calendar-days"></i></div><div class="v">{{ $data['totalEvent'] }}</div><div class="k">{{ ___('settings.event') }}</div></div>
                </div>
                <div class="bn-dv2-pill-row">
                    <div class="bn-dv2-pill"><i class="fa-solid fa-star"></i><div><div class="v">@if(($data['homework_total_marks'] ?? null) !== null){{ $data['homework_total_marks'] }}@else—@endif</div><div class="k">{{ ___('examination.scores') }}</div></div></div>
                    @if (!empty($data['weekly_wins']) && $data['weekly_wins']['has_wins'] && dashboard_feature_enabled('student', 'weekly_wins'))
                        <div class="bn-dv2-pill"><i class="fa-solid fa-trophy"></i><div><div class="v">{{ $data['weekly_wins']['mastered_titles']->count() }}</div><div class="k">{{ ___('common.mastered_this_week') }}</div></div></div>
                    @endif
                </div>
            </div>
        </div>

        @if (!empty($lh))
            {{-- Row 2: Brain Level, Knowledge Tree, Skill Mastery, Next Best Action --}}
            <div class="bn-dv2-grid bn-dv2-grid--auto">
                @if (!empty($lh['brain_level']) && dashboard_feature_enabled('student', 'brain_level'))
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

                @if (!empty($lh['knowledge_tree']) && dashboard_feature_enabled('student', 'knowledge_tree'))
                    @php $kt = $lh['knowledge_tree']; @endphp
                    <div class="bn-dv2-card">
                        <div class="bn-dv2-card-label"><i class="fa-solid fa-seedling"></i>{{ ___('common.knowledge_tree') }}</div>
                        <div class="bn-dv2-tree">
                            <div class="icn">{{ $kt['emoji'] }}</div>
                            <div class="stage">{{ $kt['label'] }}</div>
                            <div class="days">{{ $kt['active_days'] }} {{ ___('common.days_growing') }}</div>
                        </div>
                    </div>
                @endif

                @if (dashboard_feature_enabled('student', 'skill_mastery_overview'))
                    <div class="bn-dv2-card">
                        <div class="bn-dv2-card-label"><i class="fa-solid fa-chart-pie"></i>{{ ___('common.your_skill_snapshot') }}</div>
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

                @if (!empty($lh['next_action']) && dashboard_feature_enabled('student', 'next_best_action'))
                    @php $na = $lh['next_action']; @endphp
                    <div class="bn-dv2-card">
                        <div class="bn-dv2-card-label"><i class="fa-solid fa-compass"></i>{{ $na['type'] === 'struggle' ? ___('common.kea_noticed') : ___('common.your_next_best_action') }}</div>
                        <div class="bn-dv2-nba-title">{{ optional($na['skill'])->title ?? ___('common.all_caught_up') }}</div>
                        <div class="bn-dv2-nba-reason">{{ $na['reason'] }}</div>
                    </div>
                @endif
            </div>

            {{-- Row 3: What's Next, Mistake Bank, Refresh Time, Badges --}}
            @if (dashboard_feature_enabled('student', 'whats_next') || dashboard_feature_enabled('student', 'mistake_bank') || dashboard_feature_enabled('student', 'refresh_time') || dashboard_feature_enabled('student', 'badges'))
                <div class="bn-dv2-grid bn-dv2-grid--auto">
                    @if (dashboard_feature_enabled('student', 'whats_next'))
                        <div class="bn-dv2-card">
                            <div class="bn-dv2-card-label"><i class="fa-solid fa-route"></i>{{ ___('common.whats_next') }}</div>
                            @forelse ($lh['next_skills'] as $skill)
                                <div class="bn-dv2-list-row">
                                    <span class="num">{{ $loop->iteration }}</span>
                                    <span class="t">{{ $skill->title }}</span>
                                </div>
                            @empty
                                <p class="bn-empty-note">{{ ___('common.no_skills_set_up_yet_for_your_grade') }}</p>
                            @endforelse
                        </div>
                    @endif

                    @if (dashboard_feature_enabled('student', 'mistake_bank') && !empty($lh['needs_review']) && count($lh['needs_review']))
                        <div class="bn-dv2-card">
                            <div class="bn-dv2-card-label"><i class="fa-solid fa-magnifying-glass"></i>{{ ___('common.lets_investigate') }}</div>
                            @foreach ($lh['needs_review'] as $row)
                                <div class="bn-dv2-list-row">
                                    <span class="t">{{ $row['skill']->title }}</span>
                                    <span class="bn-dv2-tag bn-dv2-tag--{{ $row['stage']['level'] === 'new' ? 'warn' : 'ok' }}">{{ $row['stage']['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (dashboard_feature_enabled('student', 'refresh_time') && !empty($lh['due_for_review']) && count($lh['due_for_review']))
                        <div class="bn-dv2-card">
                            <div class="bn-dv2-card-label"><i class="fa-solid fa-rotate"></i>{{ ___('common.refresh_time') }}</div>
                            @foreach ($lh['due_for_review'] as $skill)
                                <div class="bn-dv2-list-row">
                                    <span class="t">{{ $skill->title }}</span>
                                    <span class="bn-dv2-tag bn-dv2-tag--warn">{{ ___('common.quick_refresh') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (dashboard_feature_enabled('student', 'badges') && !empty($lh['badges']))
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
                </div>
            @endif

            {{-- Row 4: Personal Best, Verified Skills, Today's Goals, Reflection Journal --}}
            @if (dashboard_feature_enabled('student', 'personal_best') || dashboard_feature_enabled('student', 'verified_skills') || dashboard_feature_enabled('student', 'daily_goals') || dashboard_feature_enabled('student', 'reflection_journal'))
                <div class="bn-dv2-grid bn-dv2-grid--auto">
                    @if (dashboard_feature_enabled('student', 'personal_best') && !empty($lh['personal_best']))
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

                    @if (dashboard_feature_enabled('student', 'verified_skills') && !empty($lh['verified_skills']))
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

                    @if (dashboard_feature_enabled('student', 'daily_goals'))
                        <div class="bn-dv2-card">
                            <div class="bn-dv2-card-label"><i class="fa-solid fa-flag-checkered"></i>{{ ___('common.todays_goals') }}</div>
                            <form action="{{ route('student-panel-dashboard.save-daily-goals') }}" method="post">
                                @csrf
                                @php $chosenKeys = collect($data['daily_goals'] ?? [])->pluck('key')->all(); @endphp
                                @foreach (\App\Repositories\LearningEngine\DailyGoalRepository::CATALOGUE as $key => $meta)
                                    @php
                                        $chosenRow = collect($data['daily_goals'] ?? [])->firstWhere('key', $key);
                                        $isDone    = $chosenRow['completed'] ?? false;
                                    @endphp
                                    <label class="bn-dv2-goal-row @if($isDone) done @endif">
                                        <input type="checkbox" name="goals[]" value="{{ $key }}" @if(in_array($key, $chosenKeys)) checked @endif onchange="this.form.submit()">
                                        <span class="box"></span>
                                        <span class="t">{{ $meta['label'] }}</span>
                                    </label>
                                @endforeach
                            </form>
                        </div>
                    @endif

                    @if (dashboard_feature_enabled('student', 'reflection_journal'))
                        <div class="bn-dv2-card bn-dv2-journal">
                            <div class="bn-dv2-card-label"><i class="fa-solid fa-feather-pointed"></i>{{ ___('common.todays_reflection') }}</div>
                            <form action="{{ route('student-panel-dashboard.save-reflection') }}" method="post">
                                @csrf
                                @php $reflection = $data['reflection_today'] ?? null; @endphp
                                <div class="bn-dv2-journal-q">{{ ___('common.what_was_hard_today') }}</div>
                                <textarea name="what_was_hard" rows="2" maxlength="1000" placeholder="{{ ___('common.reflection_hard_placeholder') }}">{{ optional($reflection)->what_was_hard }}</textarea>
                                <div class="bn-dv2-journal-q">{{ ___('common.what_strategy_worked_today') }}</div>
                                <textarea name="what_worked" rows="2" maxlength="1000" placeholder="{{ ___('common.reflection_worked_placeholder') }}">{{ optional($reflection)->what_worked }}</textarea>
                                <button type="submit" class="btn ot-btn-primary btn-sm mt-2">{{ ___('common.save_reflection') }}</button>
                                @if ($reflection)
                                    <span class="bn-empty-note" style="margin-left:8px;"><i class="fa-solid fa-circle-check"></i> {{ ___('common.saved_today') }}</span>
                                @endif
                            </form>
                        </div>
                    @endif
                </div>
            @endif
        @endif

        {{-- Row 5: AI Study Helper, Homework --}}
        @if (dashboard_feature_enabled('student', 'ai_ask_helper') || dashboard_feature_enabled('student', 'teach_kea') || dashboard_feature_enabled('student', 'ai_fact_checker'))
            <div class="bn-dv2-grid bn-dv2-grid--wide">
                <div class="bn-dv2-card">
                    <div class="bn-dv2-card-label"><i class="fa-solid fa-robot"></i>{{ ___('common.ai_study_helper') }}</div>
                    <div class="bn-dv2-ai-tools">
                        @if (dashboard_feature_enabled('student', 'ai_ask_helper'))
                            <a class="bn-dv2-ai-tool" href="{{ route('student-panel-ai-help.index') }}">
                                <div class="icn"><i class="fa-solid fa-comment-dots"></i></div>
                                <div class="t">{{ ___('common.ask') }}</div>
                                <div class="m">{{ ___('common.homework_help') }}</div>
                            </a>
                        @endif
                        @if (dashboard_feature_enabled('student', 'teach_kea'))
                            <a class="bn-dv2-ai-tool" href="{{ route('student-panel-ai-help.index') }}">
                                <div class="icn"><i class="fa-solid fa-dove"></i></div>
                                <div class="t">{{ ___('common.teach_kea') }}</div>
                                <div class="m">{{ ___('common.explain_a_skill') }}</div>
                            </a>
                        @endif
                        @if (dashboard_feature_enabled('student', 'ai_fact_checker'))
                            <a class="bn-dv2-ai-tool" href="{{ route('student-panel-ai-help.index') }}">
                                <div class="icn"><i class="fa-solid fa-check-double"></i></div>
                                <div class="t">{{ ___('common.ai_fact_checker') }}</div>
                                <div class="m">{{ ___('common.true_false_unclear') }}</div>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="bn-dv2-card">
                    <div class="bn-dv2-card-label"><i class="fa-solid fa-book"></i>{{ ___('common.homework') }}<a class="view-all" href="{{ route('student-panel-homeworks.index') }}">{{ ___('common.view_all') }}</a></div>
                    <div class="bn-dv2-hw-row">
                        <div class="icn"><i class="fa-solid fa-list-check"></i></div>
                        <div><div class="t">@if(($data['homework_total_marks'] ?? null) !== null){{ ___('common.your_homework_and_exam_marks') }}@else{{ ___('common.no_marks_recorded_yet') }}@endif</div></div>
                        @if (($data['homework_total_marks'] ?? null) !== null)
                            <div class="score">{{ $data['homework_total_marks'] }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

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

{{-- The floating version of this widget (backend.partials.character-voice-widget)
     is built and ready, just not used here for now — Kea's tap-to-speak
     card lives in the hero row above instead. --}}

@endsection
