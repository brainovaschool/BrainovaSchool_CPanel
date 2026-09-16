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
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .bn-skill-tile:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(15, 41, 55, 0.08);
    }

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

    @media (max-width: 576px) {
        .bn-panel__body { padding: 18px; }
        .bn-milestone { flex-direction: column; text-align: center; }
    }
</style>
