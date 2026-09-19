{{--
    The actual "Mission Control" visual system from the approved preview,
    ported into real CSS. Everything is scoped under .bn-dv2 so it can never
    leak into the rest of the admin theme (sidebar, tables, other pages) —
    only the dashboard content area opts in.

    Brand color (--bn-dv2-accent) reuses the existing theme-picker tokens
    (--bn-primary/--bn-accent from learning-engine-styles.blade.php), so the
    6 color themes work here unchanged. Mastery-stage colors reuse the same
    fixed --bn-not-started/--bn-developing/etc. tokens too — never themed.

    Include once per page, after learning-engine-styles:
      @include('backend.partials.learning-engine-styles')
      @include('backend.partials.learning-engine-charts')
      @include('backend.partials.dashboard-v2-styles')
--}}
<style>
    .bn-dv2 {
        /* Page and panel tints are mixed from the active theme's brand colour,
           so picking a new theme repaints the dashboard rather than only
           recolouring icons. Card faces stay near-white for readability. */
        --dv2-page: color-mix(in srgb, var(--bn-primary) 12%, #f3f4f7);
        --dv2-surface: #ffffff;
        --dv2-surface-2: color-mix(in srgb, var(--bn-primary) 7%, #f8f9fb);
        --dv2-ink: #14161a;
        --dv2-ink-soft: #5c6270;
        --dv2-ink-mute: #9095a1;
        --dv2-line: color-mix(in srgb, var(--bn-primary) 16%, #e7e9ee);
        --dv2-shadow: 0 1px 2px rgba(20,20,30,.04), 0 12px 28px -16px rgba(20,20,30,.14);
        --dv2-good: var(--bn-advanced);

        background: var(--dv2-page);
        color: var(--dv2-ink);
        font-family: 'Manrope', system-ui, sans-serif;
        padding: 20px;
        border-radius: 18px;
    }
    .bn-dv2 h1, .bn-dv2 h2, .bn-dv2 h3 {
        font-family: 'Sora', system-ui, sans-serif;
        margin: 0;
        color: var(--dv2-ink);
    }

    .bn-dv2-greet { font-size: 1.35rem; font-weight: 560; }
    .bn-dv2-sub { color: var(--dv2-ink-soft); font-size: .88rem; margin-top: 3px; }

    .bn-dv2-grid { display: grid; gap: 14px; margin-bottom: 14px; }
    .bn-dv2-grid--auto { grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); }
    .bn-dv2-grid--hero { grid-template-columns: 1.3fr 1fr 1.6fr; }
    .bn-dv2-grid--wide { grid-template-columns: 1.4fr 1fr; }
    @media (max-width: 900px) {
        .bn-dv2-grid--hero, .bn-dv2-grid--wide { grid-template-columns: 1fr; }
    }

    .bn-dv2-card {
        background: var(--dv2-surface);
        border: 1px solid var(--dv2-line);
        border-radius: 16px;
        box-shadow: var(--dv2-shadow);
        padding: 16px;
    }

    /* Profile card */
    .bn-dv2-profile { display: flex; gap: 12px; align-items: flex-start; }
    .bn-dv2-profile img { width: 64px; height: 64px; border-radius: 14px; object-fit: cover; flex-shrink: 0; }
    .bn-dv2-profile .name { font-weight: 800; font-size: 1.02rem; }
    .bn-dv2-profile .meta { list-style: none; margin: 6px 0 0; padding: 0; display: grid; gap: 3px; font-size: .78rem; color: var(--dv2-ink-soft); }
    .bn-dv2-profile .meta li i { width: 14px; color: var(--dv2-ink-mute); margin-right: 5px; }

    /* Kea card — replaces the old floating/inline voice avatar on the
       dashboard specifically; the character-voice-avatar partial still
       powers the tap-to-speak behavior underneath. */
    .bn-dv2-kea { display: flex; align-items: center; gap: 12px; cursor: pointer; }
    .bn-dv2-kea__stack { position: relative; width: 64px; height: 64px; flex-shrink: 0; }
    .bn-dv2-kea__stack img { position: absolute; height: auto; }
    .bn-dv2-kea .fallback {
        width: 64px; height: 64px; object-fit: contain; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: var(--bn-primary);
    }
    .bn-dv2-kea .t { font-weight: 700; font-size: .9rem; }
    .bn-dv2-kea .m { font-size: .76rem; color: var(--dv2-ink-mute); }
    .bn-dv2-kea .mic {
        margin-left: auto; width: 32px; height: 32px; border-radius: 50%; background: var(--bn-primary);
        color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .bn-dv2-kea.is-speaking .mic { background: var(--bn-advanced); }

    /* Stat card (class/subject/teacher/event + homework/mastered pills) */
    .bn-dv2-mini-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .bn-dv2-mini-stat { text-align: center; }
    .bn-dv2-mini-stat .icn {
        width: 34px; height: 34px; border-radius: 10px; background: var(--bn-primary-soft); color: var(--bn-primary-strong);
        display: flex; align-items: center; justify-content: center; margin: 0 auto 6px;
    }
    .bn-dv2-mini-stat .v { font-weight: 800; font-size: 1.1rem; }
    .bn-dv2-mini-stat .k { font-size: .7rem; color: var(--dv2-ink-mute); text-transform: uppercase; letter-spacing: .04em; }
    .bn-dv2-pill-row { display: flex; gap: 8px; margin-top: 12px; }
    .bn-dv2-pill { flex: 1; background: var(--dv2-surface-2); border-radius: 10px; padding: 8px 10px; display: flex; align-items: center; gap: 8px; }
    .bn-dv2-pill i { color: var(--dv2-good); }
    .bn-dv2-pill .v { font-weight: 800; font-size: .92rem; }
    .bn-dv2-pill .k { font-size: .68rem; color: var(--dv2-ink-mute); }

    .bn-dv2-card-label {
        font-size: .78rem; font-weight: 800; color: var(--dv2-ink-soft); display: flex; align-items: center; gap: 6px;
        margin-bottom: 10px;
    }
    .bn-dv2-card-label i { color: var(--bn-primary); }
    .bn-dv2-card-label .view-all { margin-left: auto; font-size: .74rem; font-weight: 700; color: var(--bn-primary); text-decoration: none; }

    /* Ring / gauge presentation (SVG drawn by learning-engine-charts.blade.php) */
    .bn-dv2-ring-wrap { display: flex; flex-direction: column; align-items: center; text-align: center; }
    .bn-dv2-ring-wrap .bn-ring { width: 104px; height: 104px; }
    .bn-dv2-ring-num { font-size: 1.3rem; font-weight: 800; margin-top: 4px; }
    .bn-dv2-ring-cap { font-size: .72rem; color: var(--dv2-ink-mute); margin-top: 2px; }

    /* Knowledge Tree mini */
    .bn-dv2-tree { display: flex; flex-direction: column; align-items: center; text-align: center; }
    .bn-dv2-tree .icn { font-size: 2.2rem; }
    .bn-dv2-tree .stage { font-weight: 800; margin-top: 4px; }
    .bn-dv2-tree .days { font-size: .76rem; color: var(--dv2-ink-mute); }

    /* Donut legend */
    .bn-dv2-legend { list-style: none; margin: 10px 0 0; padding: 0; display: grid; gap: 5px; }
    .bn-dv2-legend li { display: flex; align-items: center; gap: 6px; font-size: .76rem; color: var(--dv2-ink-soft); }
    .bn-dv2-legend .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .bn-dv2-legend b { margin-left: auto; color: var(--dv2-ink); }

    /* Next Best Action */
    .bn-dv2-nba-title { font-weight: 800; font-size: .95rem; margin-top: 6px; }
    .bn-dv2-nba-reason { font-size: .8rem; color: var(--dv2-ink-soft); margin-top: 4px; line-height: 1.45; }

    /* Numbered / tagged list rows */
    .bn-dv2-list-row { display: flex; align-items: center; gap: 8px; padding: 7px 0; border-bottom: 1px solid var(--dv2-line); font-size: .84rem; }
    .bn-dv2-list-row:last-child { border-bottom: none; }
    .bn-dv2-list-row .num {
        width: 20px; height: 20px; border-radius: 50%; background: var(--dv2-surface-2); color: var(--dv2-ink-mute);
        font-size: .68rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .bn-dv2-list-row .t { flex: 1; }
    .bn-dv2-tag { font-size: .68rem; font-weight: 700; padding: 3px 8px; border-radius: 20px; white-space: nowrap; }
    .bn-dv2-tag--warn { background: var(--bn-not-started-soft); color: var(--bn-not-started); }
    .bn-dv2-tag--ok { background: var(--bn-advanced-soft); color: var(--bn-advanced); }
    .bn-dv2-tag--review { background: var(--bn-review-soft); color: var(--bn-review); }

    /* Badge shelf */
    .bn-dv2-badge-shelf { display: flex; gap: 10px; flex-wrap: wrap; }
    .bn-dv2-badge-chip { display: flex; flex-direction: column; align-items: center; gap: 4px; font-size: .7rem; color: var(--dv2-ink-soft); font-weight: 700; text-align: center; width: 70px; }
    .bn-dv2-badge-chip .icn {
        width: 44px; height: 44px; border-radius: 12px; background: var(--dv2-surface-2);
        display: flex; align-items: center; justify-content: center; font-size: 1.2rem; border: 1px solid;
    }
    .bn-dv2-badge-chip--bronze .icn { background: #fdf1e7; border-color: rgba(161,92,46,.25); }
    .bn-dv2-badge-chip--silver .icn { background: #f1f4f7; border-color: rgba(91,107,122,.25); }
    .bn-dv2-badge-chip--gold .icn   { background: #fef8e3; border-color: rgba(161,117,10,.3); }

    /* Verified skills row */
    .bn-dv2-vs-row { display: flex; align-items: center; gap: 8px; padding: 6px 0; font-size: .82rem; }
    .bn-dv2-vs-row i { color: var(--dv2-good); }
    .bn-dv2-vs-row .t { flex: 1; font-weight: 600; }
    .bn-dv2-vs-row .m { color: var(--dv2-ink-mute); font-size: .72rem; }

    /* Daily goals */
    .bn-dv2-goal-row { display: flex; align-items: center; gap: 8px; padding: 6px 0; font-size: .84rem; cursor: pointer; }
    .bn-dv2-goal-row input { position: absolute; opacity: 0; width: 16px; height: 16px; }
    .bn-dv2-goal-row .box { width: 16px; height: 16px; border-radius: 5px; border: 2px solid var(--dv2-line); flex-shrink: 0; position: relative; }
    .bn-dv2-goal-row.done .box { background: var(--bn-primary); border-color: var(--bn-primary); }
    .bn-dv2-goal-row.done .box::after { content: '✓'; color: #fff; font-size: .6rem; position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; }
    .bn-dv2-goal-row.done .t { color: var(--dv2-ink-mute); text-decoration: line-through; }

    /* Reflection journal */
    .bn-dv2-journal-q { font-size: .72rem; color: var(--dv2-ink-mute); margin-top: 8px; }
    .bn-dv2-journal-a { font-size: .82rem; margin-top: 2px; }
    .bn-dv2-journal textarea, .bn-dv2-journal input[type="text"] {
        width: 100%; border: 1px solid var(--dv2-line); border-radius: 8px; padding: 6px 8px; font-family: inherit;
        font-size: .82rem; margin-top: 2px; background: var(--dv2-surface-2); color: var(--dv2-ink);
    }

    /* AI tools */
    .bn-dv2-ai-tools { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .bn-dv2-ai-tool { background: var(--dv2-surface-2); border-radius: 12px; padding: 12px; text-align: center; text-decoration: none; display: block; }
    .bn-dv2-ai-tool .icn {
        width: 36px; height: 36px; border-radius: 10px; background: var(--bn-primary); color: #fff;
        display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;
    }
    .bn-dv2-ai-tool .t { font-weight: 700; font-size: .82rem; color: var(--dv2-ink); }
    .bn-dv2-ai-tool .m { font-size: .68rem; color: var(--dv2-ink-mute); }

    /* Homework row */
    .bn-dv2-hw-row { display: flex; align-items: center; gap: 12px; }
    .bn-dv2-hw-row .icn { width: 40px; height: 40px; border-radius: 10px; background: var(--dv2-surface-2); display: flex; align-items: center; justify-content: center; color: var(--bn-primary); flex-shrink: 0; }
    .bn-dv2-hw-row .t { font-weight: 700; font-size: .86rem; }
    .bn-dv2-hw-row .m { font-size: .74rem; color: var(--dv2-ink-mute); }
    .bn-dv2-hw-row .score { margin-left: auto; font-weight: 800; }

    /* Banner (weekly wins / milestone) */
    .bn-dv2-banner {
        background: linear-gradient(135deg, var(--bn-primary-soft), var(--dv2-surface));
        border: 1px solid var(--dv2-line); border-radius: 14px; padding: 14px 18px; margin-bottom: 14px;
        display: flex; gap: 12px; align-items: center;
    }
    .bn-dv2-banner i { font-size: 1.2rem; color: var(--bn-primary); }
    .bn-dv2-banner .t { font-weight: 800; font-size: .9rem; }
    .bn-dv2-banner .m { font-size: .8rem; color: var(--dv2-ink-soft); }

    /* Mini table (teacher's Skill Mastery Report, parent's student list, etc.) */
    .bn-dv2-mini-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
    .bn-dv2-mini-table th { text-align: left; font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: var(--dv2-ink-mute); padding: 6px 8px; border-bottom: 1px solid var(--dv2-line); }
    .bn-dv2-mini-table td { padding: 8px; border-bottom: 1px solid var(--dv2-line); }
    .bn-dv2-mini-table tr:last-child td { border-bottom: none; }
</style>
