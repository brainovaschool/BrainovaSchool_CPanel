{{--
    Shared visual system for the Phase 0/1 learning-engine widgets (Personal
    Learning Home, Mistake Review, Milestone banner, Parent Learning
    Snapshot, Skill Mastery Report). Extends the same turquoise/purple
    "Aurora Glass" brand tokens already used on the public site into these
    dashboard panels, since the admin/student/parent theme is a separate,
    older CSS system that never picked those up.

    Include once per view: @include('backend.partials.learning-engine-styles')
--}}
<style>
    :root {
        --bn-primary: #0097b2;
        --bn-primary-strong: #007585;
        --bn-primary-soft: #e1f6fa;
        --bn-accent: #5e17eb;
        --bn-accent-strong: #4a11c4;
        --bn-accent-soft: #efe8fd;
        --bn-ink: #0f2937;
        --bn-not-started: #d97706;
        --bn-not-started-soft: #fef3e2;
        --bn-developing: #2563eb;
        --bn-developing-soft: #e9f0fd;
        --bn-proficient: #5e17eb;
        --bn-proficient-soft: #efe8fd;
        --bn-advanced: #16a34a;
        --bn-advanced-soft: #e7f5ee;
        --bn-review: #e8664f;
        --bn-review-soft: #fdeae6;
    }

    .bn-panel {
        background: linear-gradient(180deg, #ffffff 0%, #fbfeff 100%);
        border: 1px solid rgba(0, 151, 178, 0.12);
        border-radius: 18px;
        box-shadow: 0 4px 24px rgba(15, 41, 55, 0.06), 0 1px 3px rgba(15, 41, 55, 0.04);
        overflow: hidden;
        margin-bottom: 24px;
    }

    .bn-panel__body {
        padding: 24px;
    }

    .bn-panel__header {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .bn-mascot-badge {
        flex-shrink: 0;
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--bn-primary-soft), var(--bn-accent-soft));
        box-shadow: 0 0 0 3px rgba(0, 151, 178, 0.12);
        overflow: hidden;
    }

    .bn-mascot-badge img {
        max-width: 78%;
        max-height: 78%;
        object-fit: contain;
    }

    .bn-panel__eyebrow {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--bn-primary-strong);
        margin: 0 0 2px;
    }

    .bn-panel__title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--bn-ink);
        margin: 0;
    }

    .bn-panel__line {
        margin: 4px 0 0;
        color: #56616a;
        font-size: 0.94rem;
        line-height: 1.5;
    }

    .bn-divider {
        border: none;
        border-top: 1px solid rgba(15, 41, 55, 0.08);
        margin: 20px 0;
    }

    .bn-section-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--bn-ink);
        opacity: 0.72;
        margin-bottom: 12px;
    }

    .bn-section-label i {
        color: var(--bn-primary);
    }

    .bn-skill-tile {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        background: #fff;
        border: 1px solid rgba(15, 41, 55, 0.08);
        border-left: 3px solid var(--bn-primary);
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 10px;
    }
    /* No hover-lift here on purpose — these are informational, not clickable
       yet (there's no "practice this skill" page to send someone to). A
       hover effect implies interactivity that doesn't exist; add it back
       only once these tiles actually link somewhere. */

    .bn-skill-tile__title {
        font-weight: 700;
        color: var(--bn-ink);
        font-size: 0.95rem;
    }

    .bn-skill-tile__meta {
        color: #7a8790;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .bn-skill-tile--review {
        border-left-color: var(--bn-review);
        background: var(--bn-review-soft);
    }

    .bn-skill-tile--refresh {
        border-left-color: var(--bn-advanced);
        background: var(--bn-advanced-soft);
    }

    .bn-pill--refresh {
        background: var(--bn-advanced-soft);
        color: var(--bn-advanced);
    }
    .bn-pill--refresh::before {
        background: var(--bn-advanced);
    }

    .bn-empty-note {
        color: #7a8790;
        font-size: 0.9rem;
        margin: 0;
    }

    .bn-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .bn-pill::before {
        content: '';
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .bn-pill--not-started { background: var(--bn-not-started-soft); color: var(--bn-not-started); }
    .bn-pill--not-started::before { background: var(--bn-not-started); }
    .bn-pill--developing { background: var(--bn-developing-soft); color: var(--bn-developing); }
    .bn-pill--developing::before { background: var(--bn-developing); }
    .bn-pill--proficient { background: var(--bn-proficient-soft); color: var(--bn-proficient); }
    .bn-pill--proficient::before { background: var(--bn-proficient); }
    .bn-pill--advanced { background: var(--bn-advanced-soft); color: var(--bn-advanced); }
    .bn-pill--advanced::before { background: var(--bn-advanced); }
    .bn-pill--review { background: var(--bn-review-soft); color: var(--bn-review); }
    .bn-pill--review::before { background: var(--bn-review); }

    /* Lightweight stacked-bar mastery graph — no charting library needed */
    .bn-progress {
        display: flex;
        width: 100%;
        height: 10px;
        border-radius: 6px;
        overflow: hidden;
        background: #eef2f4;
        margin-bottom: 14px;
    }

    .bn-progress__seg {
        height: 100%;
        transition: width 0.4s ease;
    }

    .bn-progress__seg--not-started { background: var(--bn-not-started); }
    .bn-progress__seg--developing { background: var(--bn-developing); }
    .bn-progress__seg--proficient { background: var(--bn-proficient); }
    .bn-progress__seg--advanced { background: var(--bn-advanced); }

    .bn-milestone {
        background: linear-gradient(120deg, var(--bn-advanced-soft), #ffffff 70%);
        border: 1px solid rgba(22, 163, 74, 0.25);
        border-radius: 18px;
        padding: 20px 24px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .bn-milestone__icon {
        flex-shrink: 0;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--bn-advanced);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        box-shadow: 0 0 0 6px rgba(22, 163, 74, 0.12);
    }

    .bn-milestone__title {
        font-weight: 700;
        color: var(--bn-ink);
        margin: 0 0 2px;
    }

    .bn-milestone__line {
        margin: 0;
        color: #56616a;
        font-size: 0.92rem;
    }

    /* Next Best Action — Phase 2, one clear action instead of a menu */
    .bn-next-action {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        background: linear-gradient(135deg, var(--bn-primary-soft), #fff 75%);
        border: 1px solid rgba(0, 151, 178, 0.18);
        border-radius: 14px;
        padding: 16px 18px;
        margin: 16px 0;
    }

    .bn-next-action--caught-up {
        background: linear-gradient(135deg, var(--bn-advanced-soft), #fff 75%);
        border-color: rgba(22, 163, 74, 0.2);
    }

    .bn-next-action--struggle {
        background: linear-gradient(135deg, var(--bn-accent-soft), #fff 75%);
        border-color: rgba(94, 23, 235, 0.18);
    }

    .bn-next-action--struggle .bn-next-action__icon {
        background: var(--bn-accent);
    }

    .bn-next-action--struggle .bn-next-action__eyebrow {
        color: var(--bn-accent-strong);
    }

    .bn-next-action__icon {
        flex-shrink: 0;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: var(--bn-primary);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .bn-next-action--caught-up .bn-next-action__icon {
        background: var(--bn-advanced);
    }

    .bn-next-action__eyebrow {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--bn-primary-strong);
        margin: 0 0 2px;
    }

    .bn-next-action__title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--bn-ink);
        margin: 0;
    }

    .bn-next-action__reason {
        margin: 4px 0 0;
        color: #56616a;
        font-size: 0.86rem;
        line-height: 1.5;
    }

    /* Profile hero — top of the dashboard: who this is, at a glance */
    .bn-hero {
        background: linear-gradient(120deg, var(--bn-primary) 0%, var(--bn-accent) 130%);
        border-radius: 18px;
        padding: 22px 24px;
        margin-bottom: 20px;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
    }

    .bn-hero__avatar {
        width: 68px;
        height: 68px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(255, 255, 255, 0.6);
        flex-shrink: 0;
        background: rgba(255, 255, 255, 0.15);
    }

    .bn-hero__eyebrow {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.75);
        margin: 0;
    }

    .bn-hero__name {
        font-size: 1.35rem;
        font-weight: 700;
        color: #fff;
        margin: 2px 0 8px;
    }

    .bn-hero__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 16px;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .bn-hero__meta li {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        color: rgba(255, 255, 255, 0.92);
    }

    .bn-hero__meta i { opacity: 0.85; width: 14px; text-align: center; }

    /* Brain Level badge — lives in the hero, next to the name */
    .bn-level-badge {
        flex-shrink: 0;
        background: rgba(255, 255, 255, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.35);
        border-radius: 14px;
        padding: 10px 16px;
        min-width: 150px;
    }

    .bn-level-badge__top {
        display: flex;
        align-items: baseline;
        gap: 6px;
        color: #fff;
        margin-bottom: 6px;
    }

    .bn-level-badge__num {
        font-size: 1.4rem;
        font-weight: 800;
        line-height: 1;
    }

    .bn-level-badge__label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        opacity: 0.85;
    }

    .bn-level-badge__bar {
        height: 6px;
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.25);
        overflow: hidden;
    }

    .bn-level-badge__fill {
        height: 100%;
        background: #fff;
        border-radius: 4px;
        transition: width 0.4s ease;
    }

    .bn-level-badge__xp {
        font-size: 0.68rem;
        color: rgba(255, 255, 255, 0.8);
        margin-top: 4px;
    }

    /* Stat tiles — real figures only; no fabricated points/rank here */
    .bn-stat-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .bn-stat-tile {
        background: #fff;
        border: 1px solid rgba(15, 41, 55, 0.08);
        border-radius: 14px;
        padding: 14px 16px;
        box-shadow: 0 2px 10px rgba(15, 41, 55, 0.04);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .bn-stat-tile__icon {
        flex-shrink: 0;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        color: #fff;
        background: var(--bn-primary);
    }

    .bn-stat-tile__value {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--bn-ink);
        line-height: 1.1;
    }

    .bn-stat-tile__label {
        font-size: 0.72rem;
        color: #7a8790;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .bn-stat-tile--tree {
        background: linear-gradient(135deg, var(--bn-advanced-soft), #fff 70%);
        border-color: rgba(22, 163, 74, 0.18);
    }

    .bn-stat-tile__emoji {
        flex-shrink: 0;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }

    /* Badges — tiered, tied to real evidence, never arbitrary */
    .bn-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 12px;
        padding: 8px 14px;
        font-size: 0.86rem;
        font-weight: 700;
        border: 1px solid;
    }

    .bn-badge--bronze { background: #fdf1e7; color: #a15c2e; border-color: rgba(161, 92, 46, 0.25); }
    .bn-badge--silver { background: #f1f4f7; color: #5b6b7a; border-color: rgba(91, 107, 122, 0.25); }
    .bn-badge--gold    { background: #fef8e3; color: #a1750a; border-color: rgba(161, 117, 10, 0.3); }

    .bn-badge__count {
        font-size: 0.72rem;
        font-weight: 600;
        opacity: 0.75;
    }

    /* Personal Best — the default comparison, never a rank */
    .bn-personal-best {
        display: flex;
        align-items: center;
        gap: 16px;
        background: #fff;
        border: 1px solid rgba(15, 41, 55, 0.08);
        border-radius: 14px;
        padding: 14px 18px;
    }

    .bn-personal-best__figure {
        text-align: center;
    }

    .bn-personal-best__num {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--bn-ink);
        line-height: 1.1;
    }

    .bn-personal-best__label {
        font-size: 0.68rem;
        color: #7a8790;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .bn-personal-best__arrow {
        color: #c3cbd1;
        font-size: 1.1rem;
    }

    .bn-personal-best__delta {
        margin-left: auto;
        font-size: 0.9rem;
        font-weight: 700;
    }

    .bn-personal-best__delta--up { color: var(--bn-advanced); }
    .bn-personal-best__delta--down { color: var(--bn-not-started); }
    .bn-personal-best__delta--flat { color: #7a8790; }

    @media (max-width: 576px) {
        .bn-panel__body { padding: 18px; }
        .bn-milestone { flex-direction: column; text-align: center; }
        .bn-hero { flex-direction: column; text-align: center; align-items: center; }
        .bn-hero__meta { justify-content: center; }
    }
</style>
