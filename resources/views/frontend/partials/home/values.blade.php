<!-- HOME: E6 core values -->
<section class="bn-home-section bn-values">
    <div class="container">
        <div class="bn-home-head">
            <p class="bn-eyebrow">Core values &middot; E&#8310;</p>
            <h2>Six values, taught the way they&rsquo;re lived</h2>
            <p>Every programme, lesson and interaction at Brainova is anchored to E to the power of six.</p>
        </div>

        <div class="bn-values-grid">
            @php
                $bnValues = [
                    ['Ethics', 'Integrity &amp; Islamic values'],
                    ['Empathy', 'Compassion &amp; understanding'],
                    ['Exploration', 'Innovation &amp; curiosity'],
                    ['Endeavour', 'Purposeful effort &amp; resilience'],
                    ['Excellence', 'High standards &amp; mastery'],
                    ['Empowerment', 'Personalisation &amp; wellbeing'],
                ];
            @endphp
            @foreach ($bnValues as $i => $v)
                <div class="bn-value">
                    <span class="bn-value__e">E<sup>{{ $i + 1 }}</sup></span>
                    <h3>{{ $v[0] }}</h3>
                    <p>{!! $v[1] !!}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
