@extends('student-panel.partials.master')

@section('title')
    {{ @$data['title'] }}
@endsection

@push('css')
<style>
.kg{ background:var(--bn-wash); border-radius:18px; padding:20px; }
.kg-head h4{ color:var(--bn-primary-strong); margin:0; }
.kg-head p{ color:#5b6474; margin:4px 0 0; font-size:.92rem; }

/* The three-step story of how everything fits together, read left to right. */
.kg-steps{ display:flex; gap:12px; flex-wrap:wrap; margin-top:16px; }
.kg-step{
    flex:1 1 180px; background:#fff; border:2px solid var(--bn-surface-line); border-radius:16px;
    padding:18px 14px; text-align:center; position:relative;
}
.kg-step .num{
    position:absolute; top:-12px; left:50%; transform:translateX(-50%);
    width:26px; height:26px; border-radius:50%; background:var(--bn-primary); color:#fff;
    font-size:.8rem; font-weight:800; display:flex; align-items:center; justify-content:center;
}
.kg-step .ico{ font-size:2.4rem; line-height:1; }
.kg-step .t{ font-weight:800; margin-top:8px; color:var(--bn-ink); }
.kg-step .m{ font-size:.84rem; color:#5b6474; margin-top:2px; }

.kg-section-title{ font-weight:800; color:var(--bn-ink); margin:26px 0 10px; font-size:1.05rem; }

.kg-grid{ display:grid; grid-template-columns:repeat(auto-fill, minmax(230px, 1fr)); gap:12px; }
.kg-card{
    background:#fff; border:2px solid var(--bn-surface-line); border-radius:16px; padding:16px;
}
.kg-card .ico{ font-size:2rem; line-height:1; }
.kg-card .t{ font-weight:800; margin-top:6px; color:var(--bn-ink); }
.kg-card .m{ font-size:.86rem; color:#5b6474; margin-top:4px; line-height:1.5; }
.kg-card .grow{
    margin-top:10px; font-size:.82rem; font-weight:700; color:var(--bn-primary-strong);
    background:var(--bn-primary-soft); border-radius:10px; padding:7px 10px;
}

/* Side-by-side comparison for the thing kids ask about most: why the number
   on the dashboard isn't their exam mark. */
.kg-compare{ display:flex; gap:12px; flex-wrap:wrap; margin-top:10px; }
.kg-compare > div{ flex:1 1 240px; background:#fff; border-radius:16px; padding:16px; border:2px solid var(--bn-surface-line); }
.kg-compare h6{ font-weight:800; margin:6px 0 4px; }
.kg-compare p{ font-size:.86rem; color:#5b6474; margin:0; line-height:1.5; }

.kg-back{ margin-top:22px; }
</style>
@endpush

@section('content')
<div class="page-content">
    @include('backend.partials.learning-engine-styles')

    <div class="kg">
        <div class="kg-head">
            <h4>How your dashboard works 🎒</h4>
            <p>A quick tour. Nothing here is a test — it's just to help you read your own progress.</p>
        </div>

        <div class="kg-steps">
            <div class="kg-step">
                <span class="num">1</span>
                <div class="ico">✏️</div>
                <div class="t">Answer questions</div>
                <div class="m">In quizzes and exams your teacher sets.</div>
            </div>
            <div class="kg-step">
                <span class="num">2</span>
                <div class="ico">⚡</div>
                <div class="t">Earn XP</div>
                <div class="m">Every right answer gives you XP points.</div>
            </div>
            <div class="kg-step">
                <span class="num">3</span>
                <div class="ico">🧠</div>
                <div class="t">Brain Level goes up</div>
                <div class="m">Enough XP and you reach the next level.</div>
            </div>
        </div>

        <div class="kg-section-title">Two different scores — don't mix them up</div>
        <div class="kg-compare">
            <div>
                <div class="ico" style="font-size:1.6rem;">⚡</div>
                <h6>XP &amp; Brain Level</h6>
                <p>Your learning journey. It only ever goes up — it never drops, even on a bad day.</p>
            </div>
            <div>
                <div class="ico" style="font-size:1.6rem;">📄</div>
                <h6>Homework &amp; exam marks</h6>
                <p>Your teacher's marks, shown separately. These are <b>not</b> connected to your XP or Brain Level.</p>
            </div>
        </div>

        <div class="kg-section-title">What each part means</div>
        <div class="kg-grid">

            @if (dashboard_feature_enabled('student', 'brain_level'))
                <div class="kg-card">
                    <div class="ico">🧠</div>
                    <div class="t">Brain Level</div>
                    <div class="m">How far you've come overall. The ring shows how close you are to the next level.</div>
                    <div class="grow">Grows when: you answer questions correctly.</div>
                </div>
            @endif

            @if (dashboard_feature_enabled('student', 'knowledge_tree'))
                <div class="kg-card">
                    <div class="ico">🌱</div>
                    <div class="t">Knowledge Tree</div>
                    <div class="m">Your tree grows the more days you show up and practise.</div>
                    <div class="grow">Grows when: you practise on a new day.</div>
                </div>
            @endif

            @if (dashboard_feature_enabled('student', 'skill_mastery_overview'))
                <div class="kg-card">
                    <div class="ico">🍩</div>
                    <div class="t">Skill Snapshot</div>
                    <div class="m">Each skill moves along: Not started → Developing → Proficient → Advanced.</div>
                    <div class="grow">Moves up when: you get a skill right again and again.</div>
                </div>
            @endif

            @if (dashboard_feature_enabled('student', 'next_best_action'))
                <div class="kg-card">
                    <div class="ico">🧭</div>
                    <div class="t">Next Best Action</div>
                    <div class="m">One suggestion of what to do next, so you never have to guess.</div>
                </div>
            @endif

            @if (dashboard_feature_enabled('student', 'mistake_bank'))
                <div class="kg-card">
                    <div class="ico">🔍</div>
                    <div class="t">Let's Investigate</div>
                    <div class="m">Skills you got wrong before. Getting them right again clears them.</div>
                    <div class="grow">Tip: mistakes here are normal — they're how you improve.</div>
                </div>
            @endif

            @if (dashboard_feature_enabled('student', 'refresh_time'))
                <div class="kg-card">
                    <div class="ico">🔄</div>
                    <div class="t">Refresh Time</div>
                    <div class="m">Things you learned a while ago and haven't touched lately.</div>
                </div>
            @endif

            @if (dashboard_feature_enabled('student', 'badges'))
                <div class="kg-card">
                    <div class="ico">🏅</div>
                    <div class="t">Badges</div>
                    <div class="m">Earned per subject: bronze, then silver, then gold.</div>
                    <div class="grow">Grows when: you master more skills in one subject.</div>
                </div>
            @endif

            @if (dashboard_feature_enabled('student', 'personal_best'))
                <div class="kg-card">
                    <div class="ico">📈</div>
                    <div class="t">Personal Best</div>
                    <div class="m">This week compared with last week. You're only ever racing yourself — never your classmates.</div>
                </div>
            @endif

            @if (dashboard_feature_enabled('student', 'verified_skills'))
                <div class="kg-card">
                    <div class="ico">✅</div>
                    <div class="t">Verified Skills</div>
                    <div class="m">Skills you've truly mastered, with the date you did it.</div>
                </div>
            @endif

            @if (dashboard_feature_enabled('student', 'daily_goals'))
                <div class="kg-card">
                    <div class="ico">🎯</div>
                    <div class="t">Today's Goals</div>
                    <div class="m">Pick up to 3 small goals. They tick themselves off as you work.</div>
                </div>
            @endif

            @if (dashboard_feature_enabled('student', 'reflection_journal'))
                <div class="kg-card">
                    <div class="ico">🪶</div>
                    <div class="t">Reflection Journal</div>
                    <div class="m">Two quick questions about your day. There's no wrong answer.</div>
                    <div class="grow">Earns XP: once a day when you write it.</div>
                </div>
            @endif

            @if (dashboard_feature_enabled('student', 'kea_voice'))
                <div class="kg-card">
                    <div class="ico">🔊</div>
                    <div class="t">Your talking avatar</div>
                    <div class="m">Tap it and it reads your update out loud to you.</div>
                </div>
            @endif

            @if (dashboard_feature_enabled('student', 'teach_kea'))
                <div class="kg-card">
                    <div class="ico">🐦</div>
                    <div class="t">Teach Kea</div>
                    <div class="m">Explain something in your own words. Teaching it proves you really know it.</div>
                    <div class="grow">Earns XP: every time you explain a skill.</div>
                </div>
            @endif

            @if (dashboard_feature_enabled('student', 'ai_ask_helper'))
                <div class="kg-card">
                    <div class="ico">💬</div>
                    <div class="t">Ask for help</div>
                    <div class="m">Stuck on homework? Ask, and you'll get a hint to help you work it out.</div>
                </div>
            @endif

        </div>

        <div class="kg-section-title">🪙 Coins</div>
        <div class="kg-grid">
            <div class="kg-card">
                <div class="ico">🪙</div>
                <div class="t">Where coins come from</div>
                <div class="m">You earn coins at the same time as XP — so every right answer gives you both.</div>
            </div>
            <div class="kg-card">
                <div class="ico">🛍️</div>
                <div class="t">What to spend them on</div>
                <div class="m">Outfits and accessories in Avatar World.</div>
            </div>
            <div class="kg-card">
                <div class="ico">🛡️</div>
                <div class="t">Spending is safe</div>
                <div class="m">Buying things never lowers your XP or your Brain Level.</div>
            </div>
        </div>

        <div class="kg-back">
            <a href="{{ route('student-panel-dashboard.index') }}" class="btn ot-btn-primary">
                <i class="fa-solid fa-arrow-left"></i> Back to my dashboard
            </a>
        </div>
    </div>
</div>
@endsection
