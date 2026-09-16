@extends('student-panel.partials.master')

@section('title')
{{ ___('common.Dashboard') }}
@endsection

@section('content')
<div class="page-content">

    @include('backend.partials.learning-engine-styles')

    @php
        $keaSpeakText = null;
        if (!empty($data['learning_home'])) {
            $lhForSpeech  = $data['learning_home'];
            $pendingGoals = collect($data['daily_goals'] ?? [])->where('completed', false)->count();

            // Kea speaks her own line here regardless of which character
            // the greeting card further down is showing today (that one
            // can be Brainbot) — the avatar in the hero is always her.
            $speakParts   = [\App\Support\Character::line('kea', $lhForSpeech['is_comeback'] ? 'comeback' : 'welcome')];
            $speakParts[] = 'You are Brain Level ' . ($lhForSpeech['brain_level']['level'] ?? 1) . '.';
            if (!empty($lhForSpeech['next_action']['reason'])) {
                $speakParts[] = $lhForSpeech['next_action']['reason'];
            }
            // Phase 4, idea #6: a separate, proactive nudge about a specific
            // neglected skill — skipped when it's the same skill the Next
            // Best Action reason above already mentions, so Kea doesn't
            // repeat herself.
            $nudge = $lhForSpeech['inactivity_nudge'] ?? null;
            if ($nudge && (empty($lhForSpeech['next_action']['skill']) || $lhForSpeech['next_action']['skill']->id !== $nudge['skill']->id)) {
                $speakParts[] = $nudge['line'];
            }
            $speakParts[] = 'You have ' . ($lhForSpeech['mastery_counts']['advanced'] ?? 0) . ' ' . ___('common.skills_mastered_so_far') . ($pendingGoals ? ', and ' . $pendingGoals . ' ' . ___('common.goals_left_today') : '') . '.';

            $keaSpeakText = implode(' ', $speakParts);
        }
        $keaImage = setting('ai_helper_student_mascot') ? globalAsset(setting('ai_helper_student_mascot')) : null;
    @endphp

    {{-- Profile hero — who this is, at a glance, always at the top --}}
    <div class="bn-hero">
        <img class="bn-hero__avatar" src="{{ @globalAsset(@$data['student']->user->upload->path, '100X100.webp') }}" alt="{{ @$data['student']->first_name }}">
        <div style="min-width:0;">
            <p class="bn-hero__eyebrow">{{ ___('common.welcome_back') }}</p>
            <p class="bn-hero__name">{{ @$data['student']->first_name }} {{ @$data['student']->last_name }}</p>
            <ul class="bn-hero__meta">
                <li><i class="fa-solid fa-graduation-cap"></i> {{ @$data['student']->sessionStudentDetails->class->name }} ({{ @$data['student']->sessionStudentDetails->section->name }})</li>
                <li><i class="fa-solid fa-id-card"></i> {{ ___('student_info.admission_no') }}: {{ @$data['student']->admission_no }}</li>
                <li><i class="fa-solid fa-hashtag"></i> {{ ___('student_info.roll_no') }}: {{ @$data['student']->roll_no }}</li>
                @if (@$data['student']->parent->guardian_name)
                    <li><i class="fa-solid fa-user-tie"></i> {{ @$data['student']->parent->guardian_name }}</li>
                @endif
                @if (@$data['student']->mobile)
                    <li><i class="fa-solid fa-phone"></i> {{ @$data['student']->mobile }}</li>
                @endif
            </ul>
        </div>
        @if ($keaSpeakText)
            @include('backend.partials.character-voice-avatar', ['image' => $keaImage, 'name' => 'Kea', 'speakText' => $keaSpeakText])
        @endif
        @if (!empty($data['learning_home']['brain_level']))
            @php $bl = $data['learning_home']['brain_level']; @endphp
            <div class="bn-level-badge">
                <div class="bn-level-badge__top">
                    <i class="fa-solid fa-brain"></i>
                    <span class="bn-level-badge__num">{{ $bl['level'] }}</span>
                    <span class="bn-level-badge__label">{{ ___('common.brain_level') }}</span>
                </div>
                <div class="bn-level-badge__bar">
                    <div class="bn-level-badge__fill" style="width:{{ $bl['progress_pct'] }}%"></div>
                </div>
                <div class="bn-level-badge__xp">{{ $bl['xp_into_level'] }} / {{ $bl['xp_for_level'] }} XP {{ ___('common.to_next_level') }}</div>
            </div>
        @endif
    </div>

    {{-- Real figures only — no fabricated rank here: a leaderboard is
         deliberately on hold until there are real per-grade headcounts to
         make ranking meaningful, even though Brain Level/XP now exist. --}}
    <div class="bn-stat-row">
        @if (!empty($data['learning_home']['knowledge_tree']))
            @php $kt = $data['learning_home']['knowledge_tree']; @endphp
            <div class="bn-stat-tile bn-stat-tile--tree">
                <div class="bn-stat-tile__emoji">{{ $kt['emoji'] }}</div>
                <div><div class="bn-stat-tile__value" style="font-size:0.95rem;">{{ $kt['label'] }}</div><div class="bn-stat-tile__label">{{ $kt['active_days'] }} {{ ___('common.days_growing') }}</div></div>
            </div>
        @endif
        <div class="bn-stat-tile">
            <div class="bn-stat-tile__icon" style="background:#2563eb;"><i class="fa-solid fa-chalkboard"></i></div>
            <div><div class="bn-stat-tile__value">{{ $data['totalClass'] }}</div><div class="bn-stat-tile__label">{{ ___('academic.class') }}</div></div>
        </div>
        <div class="bn-stat-tile">
            <div class="bn-stat-tile__icon" style="background:#5e17eb;"><i class="fa-solid fa-book"></i></div>
            <div><div class="bn-stat-tile__value">{{ $data['totalSubject'] }}</div><div class="bn-stat-tile__label">{{ ___('academic.subject') }}</div></div>
        </div>
        <div class="bn-stat-tile">
            <div class="bn-stat-tile__icon" style="background:#d97706;"><i class="fa-solid fa-chalkboard-user"></i></div>
            <div><div class="bn-stat-tile__value">{{ $data['totalTeacher'] }}</div><div class="bn-stat-tile__label">{{ ___('academic.teacher') }}</div></div>
        </div>
        <div class="bn-stat-tile">
            <div class="bn-stat-tile__icon" style="background:#0097b2;"><i class="fa-solid fa-calendar-days"></i></div>
            <div><div class="bn-stat-tile__value">{{ $data['totalEvent'] }}</div><div class="bn-stat-tile__label">{{ ___('settings.event') }}</div></div>
        </div>
        <div class="bn-stat-tile">
            <div class="bn-stat-tile__icon" style="background:#16a34a;"><i class="fa-solid fa-star"></i></div>
            <div><div class="bn-stat-tile__value">@if(($data['homework_total_marks'] ?? null) !== null){{ $data['homework_total_marks'] }}@else—@endif</div><div class="bn-stat-tile__label">{{ ___('examination.scores') }}</div></div>
        </div>
        @if (!empty($data['weekly_wins']) && $data['weekly_wins']['has_wins'])
            <div class="bn-stat-tile">
                <div class="bn-stat-tile__icon" style="background:#e8664f;"><i class="fa-solid fa-trophy"></i></div>
                <div><div class="bn-stat-tile__value">{{ $data['weekly_wins']['mastered_titles']->count() }}</div><div class="bn-stat-tile__label">{{ ___('common.mastered_this_week') }}</div></div>
            </div>
        @endif
    </div>

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
                    <div class="bn-next-action @if($na['type'] === 'caught_up') bn-next-action--caught-up @elseif($na['type'] === 'struggle') bn-next-action--struggle @endif">
                        <div class="bn-next-action__icon">
                            <i class="fa-solid @if($na['type'] === 'caught_up') fa-champagne-glasses @elseif($na['type'] === 'struggle') fa-hand-holding-heart @elseif($na['type'] === 'review') fa-magnifying-glass @else fa-compass @endif"></i>
                        </div>
                        <div>
                            <p class="bn-next-action__eyebrow">{{ $na['type'] === 'struggle' ? ___('common.kea_noticed') : ___('common.your_next_best_action') }}</p>
                            <p class="bn-next-action__title">{{ optional($na['skill'])->title ?? ___('common.all_caught_up') }}</p>
                            <p class="bn-next-action__reason">{{ $na['reason'] }}</p>
                        </div>
                    </div>
                @endif

                <hr class="bn-divider">

                <div class="bn-section-label"><i class="fa-solid fa-flag-checkered"></i> {{ ___('common.todays_goals') }}</div>
                <form action="{{ route('student-panel-dashboard.save-daily-goals') }}" method="post" style="margin-bottom:8px;">
                    @csrf
                    @php $chosenKeys = collect($data['daily_goals'] ?? [])->pluck('key')->all(); @endphp
                    @foreach (\App\Repositories\LearningEngine\DailyGoalRepository::CATALOGUE as $key => $meta)
                        @php
                            $chosenRow = collect($data['daily_goals'] ?? [])->firstWhere('key', $key);
                            $isDone    = $chosenRow['completed'] ?? false;
                        @endphp
                        <label class="bn-goal-option @if($isDone) bn-goal-option--done @endif">
                            <input type="checkbox" name="goals[]" value="{{ $key }}" @if(in_array($key, $chosenKeys)) checked @endif onchange="this.form.submit()">
                            <i class="fa-solid @if($isDone) fa-circle-check @else fa-{{ $meta['icon'] }} @endif"></i>
                            <span>{{ $meta['label'] }}</span>
                        </label>
                    @endforeach
                    <p class="bn-empty-note" style="margin-top:4px;">{{ ___('common.pick_up_to_3_goals_note') }}</p>
                </form>

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

                @if (!empty($lh['badges']) || !empty($lh['personal_best']))
                    <hr class="bn-divider">
                    <div class="row g-4">
                        @if (!empty($lh['badges']))
                            <div class="col-lg-7">
                                <div class="bn-section-label"><i class="fa-solid fa-award"></i> {{ ___('common.badges') }}</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($lh['badges'] as $badge)
                                        <span class="bn-badge bn-badge--{{ $badge['tier'] }}">
                                            <i class="fa-solid fa-medal"></i> {{ ucfirst($badge['tier']) }} {{ $badge['subject'] }} Explorer
                                            <span class="bn-badge__count">· {{ $badge['count'] }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if (!empty($lh['personal_best']))
                            @php $pb = $lh['personal_best']; @endphp
                            <div class="col-lg-5">
                                <div class="bn-section-label"><i class="fa-solid fa-chart-line"></i> {{ ___('common.personal_best') }}</div>
                                <div class="bn-personal-best">
                                    <div class="bn-personal-best__figure">
                                        <div class="bn-personal-best__num">{{ $pb['last_week'] }}%</div>
                                        <div class="bn-personal-best__label">{{ ___('common.last_week') }}</div>
                                    </div>
                                    <i class="fa-solid fa-arrow-right bn-personal-best__arrow"></i>
                                    <div class="bn-personal-best__figure">
                                        <div class="bn-personal-best__num">{{ $pb['this_week'] }}%</div>
                                        <div class="bn-personal-best__label">{{ ___('common.this_week') }}</div>
                                    </div>
                                    <span class="bn-personal-best__delta @if($pb['delta'] > 0) bn-personal-best__delta--up @elseif($pb['delta'] < 0) bn-personal-best__delta--down @else bn-personal-best__delta--flat @endif">
                                        {{ $pb['delta'] > 0 ? '+' : '' }}{{ $pb['delta'] }}%
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                @if (!empty($lh['verified_skills']))
                    <hr class="bn-divider">
                    <div class="bn-section-label"><i class="fa-solid fa-certificate"></i> {{ ___('common.verified_skills') }}</div>
                    <div class="bn-verified-grid">
                        @foreach ($lh['verified_skills'] as $vs)
                            <div class="bn-verified-card">
                                <div class="bn-verified-card__badge"><i class="fa-solid fa-circle-check"></i> {{ ___('common.verified') }}</div>
                                <div class="bn-verified-card__title">{{ $vs['skill']->title }}</div>
                                <div class="bn-verified-card__meta">{{ $vs['skill']->subject->name ?? '' }} · {{ $vs['accuracy'] }}% · {{ $vs['mastered_at']->format('d M Y') }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

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

    <div class="row">
        <div class="col-12">
            <div class="ot-card chart-card2 ot_heightFull mb-24">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap_10 card_header_border">
                    <div class="card-title">
                        <h4>{{___('dashboard.upcoming_events')}}</h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="event_upcoming_list">
                        @forelse ($data['events'] as $item)
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
                        @empty
                            <p class="gray-color mb-0">{{ ___('common.no_data_available') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- The floating version of this widget (backend.partials.character-voice-widget)
     is built and ready, just not used here for now — Kea's tap-to-speak
     avatar lives in the hero above instead. Swap it back in later by
     including that partial with $keaImage/$keaSpeakText from above. --}}

@endsection
