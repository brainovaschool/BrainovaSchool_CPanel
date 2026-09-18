{{--
    Shared ring-gauge and donut renderers for the learning-engine dashboards
    (Brain Level, Personal Best, Skill Mastery breakdown). Pure inline SVG,
    no charting library — colors are read from the CSS custom properties
    already defined in learning-engine-styles.blade.php, so they follow the
    theme picker automatically.

    Include once per page: @include('backend.partials.learning-engine-charts')
    Then mark elements with:
      <div class="bn-ring" data-value="78" data-color="var(--bn-primary)" data-track="var(--bn-primary-soft)"></div>
      <div class="bn-donut" data-segments="8,12,15,10" data-colors="var(--bn-not-started),var(--bn-developing),var(--bn-proficient),var(--bn-advanced)"></div>
--}}
@once
@push('script')
<script>
(function () {
    function polar(cx, cy, r, deg) {
        var a = (deg - 90) * Math.PI / 180;
        return { x: cx + r * Math.cos(a), y: cy + r * Math.sin(a) };
    }
    function arcPath(cx, cy, r, start, end) {
        var p1 = polar(cx, cy, r, end), p2 = polar(cx, cy, r, start);
        var large = (end - start) <= 180 ? 0 : 1;
        return ['M', p1.x, p1.y, 'A', r, r, 0, large, 0, p2.x, p2.y].join(' ');
    }

    function renderRing(el) {
        var value = Math.max(0, Math.min(100, parseFloat(el.dataset.value || '0')));
        var color = el.dataset.color || 'var(--bn-primary)';
        var track = el.dataset.track || 'var(--bn-primary-soft)';
        var cx = 52, cy = 52, r = 42;

        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 104 104');
        svg.innerHTML =
            '<path d="' + arcPath(cx, cy, r, 0, 359.999) + '" fill="none" stroke="' + track + '" stroke-width="10"/>' +
            '<path d="' + arcPath(cx, cy, r, 0, value / 100 * 360) + '" fill="none" stroke="' + color + '" stroke-width="10" stroke-linecap="round"/>';
        el.innerHTML = '';
        el.appendChild(svg);
    }

    function renderDonut(el) {
        var segs = (el.dataset.segments || '').split(',').map(Number);
        var colors = (el.dataset.colors || '').split(',');
        var total = segs.reduce(function (a, b) { return a + b; }, 0) || 1;
        var cx = 40, cy = 40, r = 32, gap = 3, angle = 0, paths = '';

        segs.forEach(function (v, i) {
            var sweep = v / total * 360;
            var s = angle + gap / 2, e = angle + sweep - gap / 2;
            if (e > s) {
                paths += '<path d="' + arcPath(cx, cy, r, s, e) + '" fill="none" stroke="' + (colors[i] || '#ccc') + '" stroke-width="16" stroke-linecap="round"/>';
            }
            angle += sweep;
        });

        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 80 80');
        svg.innerHTML = paths;
        el.innerHTML = '';
        el.appendChild(svg);
    }

    function renderAllLearningCharts() {
        document.querySelectorAll('.bn-ring').forEach(renderRing);
        document.querySelectorAll('.bn-donut').forEach(renderDonut);
    }

    renderAllLearningCharts();
    // Re-render when the theme picker changes the color tokens these charts
    // read at draw time — a plain CSS var change doesn't repaint an already-
    // drawn SVG stroke.
    document.addEventListener('click', function (e) {
        if (e.target.closest && e.target.closest('.bn-theme-swatch')) {
            setTimeout(renderAllLearningCharts, 0);
        }
    });
})();
</script>
@endpush
@endonce
