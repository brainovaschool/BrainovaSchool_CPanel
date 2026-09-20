@extends('backend.master')

@section('title')
    {{ @$data['title'] }}
@endsection

@push('css')
<style>
.tg-lead{ color:#5b6474; line-height:1.65; margin:0; }
.tg-row{ display:flex; gap:16px; padding:18px 0; border-bottom:1px solid #eee; }
.tg-row:last-child{ border-bottom:none; }
.tg-row__icon{
    flex:0 0 44px; width:44px; height:44px; border-radius:12px;
    display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.05rem;
}
.tg-row__body h5{ margin-bottom:6px; }
.tg-row__body p{ margin:0; color:#5b6474; line-height:1.6; }
.tg-stage{ display:flex; gap:10px; flex-wrap:wrap; margin:14px 0 0; }
.tg-stage > div{ flex:1 1 180px; border-radius:12px; padding:14px; color:#fff; }
.tg-stage h6{ color:#fff; font-weight:800; margin:0 0 4px; }
.tg-stage p{ margin:0; font-size:.84rem; line-height:1.5; opacity:.95; }
.tg-note{ background:#eaf2ff; border-radius:12px; padding:16px 18px; color:#1e3a5f; line-height:1.65; font-size:.94rem; }
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="page-header">
        <div class="row">
            <div class="col-sm-6">
                <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('skill-mastery-report.index') }}">Skill Mastery Report</a></li>
                    <li class="breadcrumb-item">{{ ___('common.guide') }}</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="card ot-card mb-24">
        <div class="card-body">
            <h4 class="mb-2">Reading the Skill Mastery Report</h4>
            <p class="tg-lead">
                This report shows where each student actually is on each skill, built from their answers over
                time rather than from a single test. It is deliberately not a ranking — there is no class
                position, no top-of-the-class, and no score to compare children against one another.
            </p>
        </div>
    </div>

    <div class="card ot-card mb-24">
        <div class="card-body">
            <h5 class="mb-1">The four stages</h5>
            <p class="tg-lead">Every skill a student attempts sits in one of these. They move up — and back down — on evidence.</p>

            <div class="tg-stage">
                <div style="background:#d97706;">
                    <h6>Not Started</h6>
                    <p>No attempts recorded yet. Nothing has been assessed.</p>
                </div>
                <div style="background:#2563eb;">
                    <h6>Developing</h6>
                    <p>They've attempted it, but accuracy isn't consistent yet.</p>
                </div>
                <div style="background:#5e17eb;">
                    <h6>Proficient</h6>
                    <p>Reliable across several attempts, but not yet at mastery.</p>
                </div>
                <div style="background:#16a34a;">
                    <h6>Advanced</h6>
                    <p>Consistently correct over repeated attempts — genuine mastery.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card ot-card mb-24">
        <div class="card-body">

            <div class="tg-row">
                <div class="tg-row__icon" style="background:#0097b2;"><i class="fa-solid fa-tags"></i></div>
                <div class="tg-row__body">
                    <h5>Where the data comes from — quiz skill tagging</h5>
                    <p>
                        This report is only as complete as your tagging. When you attach a skill to a homework
                        quiz question, every answer to that question feeds the student's mastery for that skill.
                        Untagged questions still get marked as normal, they just don't contribute here. If a
                        class looks emptier than expected, untagged questions are almost always the reason.
                    </p>
                </div>
            </div>

            <div class="tg-row">
                <div class="tg-row__icon" style="background:#d97706;"><i class="fa-solid fa-hand-holding-heart"></i></div>
                <div class="tg-row__body">
                    <h5>The "possible struggle" flag</h5>
                    <p>
                        Raised when a student has attempted a skill several times and is still under roughly
                        40% accuracy. It's a prompt to take a look, not a verdict — the student never sees it,
                        and it carries no score. Treat it as "worth a conversation this week."
                    </p>
                </div>
            </div>

            <div class="tg-row">
                <div class="tg-row__icon" style="background:#5e17eb;"><i class="fa-solid fa-magnifying-glass"></i></div>
                <div class="tg-row__body">
                    <h5>"Needs review"</h5>
                    <p>
                        Skills where the student has got something wrong that they haven't yet put right. It
                        clears itself once they answer that skill correctly again, so a falling number here is
                        a student repairing their own gaps.
                    </p>
                </div>
            </div>

            <div class="tg-row">
                <div class="tg-row__icon" style="background:#16a34a;"><i class="fa-solid fa-brain"></i></div>
                <div class="tg-row__body">
                    <h5>What students see — XP, Brain Level and Coins</h5>
                    <p>
                        Students earn XP for correct answers on tagged questions, and their Brain Level rises
                        with total XP. XP never decreases, so a bad week can't undo earlier progress. Coins are
                        earned alongside XP and spent on avatar items — spending them never affects XP, Brain
                        Level or anything in this report. None of this touches their homework or exam marks,
                        which stay entirely separate.
                    </p>
                </div>
            </div>

        </div>
    </div>

    <div class="card ot-card mb-24">
        <div class="card-body">
            <p class="tg-note mb-0">
                <b>Why there's no ranking.</b> Mastery here is measured against the skill, not against
                classmates. Two students can both be Advanced on the same skill, and that's a success for both.
                The report is built to show you who needs support, not who is ahead.
            </p>
        </div>
    </div>

    <a href="{{ route('skill-mastery-report.index') }}" class="btn ot-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to the report
    </a>
</div>
@endsection
