{{--
    Dashboard color theme picker — a personalization option, not a Website
    Setup admin control. The choice is stored in this browser's localStorage
    (bn_theme) and only changes the brand accent tokens (--bn-primary/--bn-accent)
    defined in learning-engine-styles.blade.php — never the mastery-stage
    colors, which stay fixed everywhere.

    Usage: @include('backend.partials.theme-picker', ['onLight' => false])
    Pass onLight => true when placing this on a plain white/light card
    instead of the gradient hero (e.g. the avatar shop page).
--}}
<div class="bn-theme-picker {{ ($onLight ?? false) ? 'bn-theme-picker--on-light' : '' }}" role="group" aria-label="{{ ___('common.dashboard_color') }}">
    <span class="bn-theme-picker__label">{{ ___('common.theme') }}</span>
    <button type="button" class="bn-theme-swatch bn-theme-swatch--aurora" data-bn-theme="aurora" aria-pressed="true" aria-label="Aurora"></button>
    <button type="button" class="bn-theme-swatch bn-theme-swatch--nature" data-bn-theme="nature" aria-pressed="false" aria-label="Nature"></button>
    <button type="button" class="bn-theme-swatch bn-theme-swatch--midnight" data-bn-theme="midnight" aria-pressed="false" aria-label="Midnight"></button>
    <button type="button" class="bn-theme-swatch bn-theme-swatch--sunrise" data-bn-theme="sunrise" aria-pressed="false" aria-label="Sunrise"></button>
    <button type="button" class="bn-theme-swatch bn-theme-swatch--berry" data-bn-theme="berry" aria-pressed="false" aria-label="Berry"></button>
    <button type="button" class="bn-theme-swatch bn-theme-swatch--ocean" data-bn-theme="ocean" aria-pressed="false" aria-label="Ocean"></button>
</div>

@once
    @push('script')
    <script>
    (function () {
        var KEY = 'bn_theme';
        var saved = null;
        try { saved = localStorage.getItem(KEY); } catch (e) {}
        if (saved) document.documentElement.setAttribute('data-bn-theme', saved);

        function syncPressedState() {
            var active = saved || 'aurora';
            document.querySelectorAll('.bn-theme-swatch').forEach(function (btn) {
                btn.setAttribute('aria-pressed', btn.dataset.bnTheme === active ? 'true' : 'false');
            });
        }
        syncPressedState();

        document.addEventListener('click', function (e) {
            var btn = e.target.closest && e.target.closest('.bn-theme-swatch');
            if (!btn) return;

            saved = btn.dataset.bnTheme;
            document.documentElement.setAttribute('data-bn-theme', saved);
            syncPressedState();
            try { localStorage.setItem(KEY, saved); } catch (err) {}
        });
    })();
    </script>
    @endpush
@endonce
