@extends('parent-panel.partials.master')

@section('title')
{{ $data['title'] }}
@endsection

@push('css')
<style>
.pg-guide-item { display:flex; gap:16px; padding:18px 0; border-bottom:1px solid #eee; }
.pg-guide-item:last-child { border-bottom:none; }
.pg-guide-item__icon {
    flex:0 0 44px; width:44px; height:44px; border-radius:12px;
    display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.1rem;
}
.pg-guide-item__body h5 { margin-bottom:6px; }
.pg-guide-item__body p { margin:0; color:#5b6474; line-height:1.6; }
.pg-note {
    background:#eaf2ff; border-radius:12px; padding:16px 18px; color:#1e3a5f; line-height:1.6; font-size:.94rem;
}
</style>
@endpush

@section('content')
<div class="page-content">
    <div class="card ot-card mb-24">
        <div class="card-body">
            <h4 class="mb-2">{{ ___('common.understanding_your_childs_dashboard') }}</h4>
            <p class="text-secondary mb-0">{{ ___('common.learning_guide_intro') }}</p>
        </div>
    </div>

    <div class="card ot-card mb-24">
        <div class="card-body">

            <div class="pg-guide-item">
                <div class="pg-guide-item__icon" style="background:#0097b2;"><i class="fa-solid fa-feather"></i></div>
                <div class="pg-guide-item__body">
                    <h5>Kea &amp; Brainbot's greeting</h5>
                    <p>Every time your child opens their dashboard, one of two friendly characters greets them — Kea the curious bird, or Brainbot the methodical robot. If it's been a few days since they last practiced, Kea welcomes them back warmly, with zero guilt about the gap. A missed day is never treated as a failure here.</p>
                </div>
            </div>

            <div class="pg-guide-item">
                <div class="pg-guide-item__icon" style="background:#5e17eb;"><i class="fa-solid fa-compass"></i></div>
                <div class="pg-guide-item__body">
                    <h5>"Your Next Best Action"</h5>
                    <p>Instead of a long list of homework, your child sees one clear suggestion of what to do next — a skill they're partway through, one that needs another look, or (when they've worked through everything set up for their grade) a simple "you're all caught up." Every suggestion comes with a plain reason, not just an instruction.</p>
                </div>
            </div>

            <div class="pg-guide-item">
                <div class="pg-guide-item__icon" style="background:#0e7490;"><i class="fa-solid fa-chart-simple"></i></div>
                <div class="pg-guide-item__body">
                    <h5>Skill mastery: Not Started → Developing → Proficient → Advanced</h5>
                    <p>Every skill your child practices moves through these four honest stages, based on real accuracy over multiple attempts — never a single test score. "Advanced" means they've shown they can do it reliably, not just once. This is about real understanding, not grades or rankings.</p>
                </div>
            </div>

            <div class="pg-guide-item">
                <div class="pg-guide-item__icon" style="background:#d97706;"><i class="fa-solid fa-magnifying-glass"></i></div>
                <div class="pg-guide-item__body">
                    <h5>"Could use another look" (the Mistake Bank)</h5>
                    <p>When your child gets something wrong, it doesn't just disappear into a red mark — it shows up here as something to revisit, with a visible recovery path: <strong>Needs practice → First recovery → Second recovery → Mastered.</strong> Getting it wrong once is treated as completely normal; the system is designed to bring them back to it, not to dwell on the miss.</p>
                </div>
            </div>

            <div class="pg-guide-item">
                <div class="pg-guide-item__icon" style="background:#2563eb;"><i class="fa-solid fa-brain"></i></div>
                <div class="pg-guide-item__body">
                    <h5>Brain Level</h5>
                    <p>This is <em>not</em> a school grade. It's a number built entirely from what your child has actually mastered, how well they retain it over time, and how consistently they practice — never from their age or grade level. It's completely normal for a younger student to have a higher Brain Level than an older one, or vice versa. Please don't read it as a ranking against classmates; there isn't one.</p>
                </div>
            </div>

            <div class="pg-guide-item">
                <div class="pg-guide-item__icon" style="background:#2f8f5b;"><i class="fa-solid fa-tree"></i></div>
                <div class="pg-guide-item__body">
                    <h5>The Knowledge Tree</h5>
                    <p>Most apps use a "streak" that breaks — and shames a child — the moment they miss a day. We deliberately don't do that. The Knowledge Tree only ever grows or pauses; it can never go backward. A quiet week just means growth is paused, not lost. There is nothing here to feel bad about after a busy or difficult week.</p>
                </div>
            </div>

            <div class="pg-guide-item">
                <div class="pg-guide-item__icon" style="background:#db2777;"><i class="fa-solid fa-award"></i></div>
                <div class="pg-guide-item__body">
                    <h5>Badges &amp; Verified Skills</h5>
                    <p>Badges are only ever awarded for real evidence — for example, mastering several skills in one subject — never for logging in or clicking around. A "Verified Skill" card is the same idea: proof of a specific skill your child has genuinely mastered, with the date and their real accuracy shown honestly, not a generic "completed" sticker.</p>
                </div>
            </div>

            <div class="pg-guide-item">
                <div class="pg-guide-item__icon" style="background:#8b5cf6;"><i class="fa-solid fa-chart-line"></i></div>
                <div class="pg-guide-item__body">
                    <h5>Personal Best</h5>
                    <p>This compares your child's accuracy <em>this week</em> to <em>their own</em> accuracy last week — never to another student. There is no class leaderboard in this dashboard; growth is always measured against their own past self.</p>
                </div>
            </div>

            <div class="pg-guide-item">
                <div class="pg-guide-item__icon" style="background:#e8664f;"><i class="fa-solid fa-heart"></i></div>
                <div class="pg-guide-item__body">
                    <h5>"Good News This Week"</h5>
                    <p>Once a week, you'll see a short, honest highlight of real wins — skills newly mastered, questions answered correctly — right at the top of this dashboard. If nothing notable happened that week, this banner simply doesn't appear; we never invent a "win" to show you.</p>
                </div>
            </div>

        </div>
    </div>

    <div class="pg-note">
        <i class="fa-solid fa-circle-info me-2"></i>
        None of this uses your child's data to rank them against other students, and nothing here is graded by AI —
        every number shown (Brain Level, mastery stages, badges) is calculated the same simple, transparent way every time,
        so it stays trustworthy and explainable. If anything here is unclear, please reach out to your child's teacher or the school office.
    </div>
</div>
@endsection
