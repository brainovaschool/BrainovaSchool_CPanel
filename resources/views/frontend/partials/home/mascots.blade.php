<!-- HOME: meet Brainbot & Kea -->
@php
    $bnBrainbot = public_path('frontend/img/mascots/brainbot.png');
    $bnKea      = public_path('frontend/img/mascots/kea.png');
    $bnHasMascots = is_file($bnBrainbot) && is_file($bnKea);
@endphp

@if ($bnHasMascots)
<section class="bn-home-section bn-mascots">
    <div class="container">
        <div class="bn-home-head">
            <p class="bn-eyebrow">Learning companions</p>
            <h2>Meet Brainbot &amp; Kea</h2>
            <p>Two friendly guides who show up across Brainova lessons, activities and videos &mdash;
            making big ideas feel playful.</p>
        </div>

        <div class="bn-mascots-grid">
            <article class="bn-mascot bn-mascot--bot">
                <div class="bn-mascot__art">
                    <img src="{{ global_asset('frontend') }}/img/mascots/brainbot.png" alt="Brainbot, the Brainova learning robot" loading="lazy">
                </div>
                <div class="bn-mascot__body">
                    <h3>Brainbot</h3>
                    <p>The curious little robot with a glowing mind. Brainbot walks learners through
                    tricky concepts, celebrates progress, and turns &ldquo;I can&rsquo;t&rdquo; into
                    &ldquo;let&rsquo;s try&rdquo; &mdash; a friendly face for the AI-assisted parts of learning.</p>
                </div>
            </article>

            <article class="bn-mascot bn-mascot--bird">
                <div class="bn-mascot__art">
                    <img src="{{ global_asset('frontend') }}/img/mascots/kea.png" alt="Kea, the Brainova exploration guide" loading="lazy">
                </div>
                <div class="bn-mascot__body">
                    <h3>Kea</h3>
                    <p>The clever, playful parrot who loves to question and explore. Kea leads
                    investigations, hands-on projects and &ldquo;why does that happen?&rdquo; moments &mdash;
                    the spirit of inquiry that runs through every Brainova programme.</p>
                </div>
            </article>
        </div>
    </div>
</section>
@endif
