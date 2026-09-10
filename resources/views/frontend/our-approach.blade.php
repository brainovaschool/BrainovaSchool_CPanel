@extends('frontend.master')
@section('title')
    Our Approach
@endsection

@section('main')

<div class="breadcrumb_area">
    <div class="container">
        <div class="breadcam_wrap text-center">
            <h3>Our Approach</h3>
            <p>The pedagogy, values and curriculum thinking behind every Brainova programme.</p>
        </div>
    </div>
</div>

<div class="bn-approach">
    <div class="container">

        {{-- Philosophy --}}
        <section class="bn-approach-lead">
            <p class="bn-eyebrow">Philosophy</p>
            <p class="bn-approach-quote">
                Brainova is bringing an evolution in learning &mdash; a continuous, real-world learning
                continuum that sparks curiosity and nurtures a scientific mindset. We shift the emphasis
                from rote memorisation to inquiry, creativity and discovery, developing innovative,
                ethical and future-ready thinkers who see science not as a subject, but as a lens for
                understanding and responsibly shaping the world.
            </p>
        </section>

        {{-- Pedagogy: 3 pillars --}}
        <section class="bn-home-section" style="padding-top:20px">
            <div class="bn-home-head">
                <p class="bn-eyebrow">Pedagogy</p>
                <h2>Three pillars of how we teach</h2>
            </div>

            <div class="bn-pillars">
                <article class="bn-pillar">
                    <h3>Learning approaches</h3>
                    <ul>
                        <li><strong>Blended &amp; hybrid</strong> — face-to-face and digital, synchronous and asynchronous.</li>
                        <li><strong>Experiential &amp; inquiry-based</strong> — experiments, simulations and real-world problem-solving.</li>
                        <li><strong>Project-based &amp; interdisciplinary</strong> — cross-curricular projects across STEM, languages, arts and leadership.</li>
                        <li><strong>Competency-based &amp; individualised</strong> — instruction aligned to each learner&rsquo;s pace and profile.</li>
                        <li><strong>AI-enabled</strong> — adaptive platforms and analytics personalise instruction in real time.</li>
                    </ul>
                </article>
                <article class="bn-pillar">
                    <h3>Cognitive &amp; future-ready skills</h3>
                    <ul>
                        <li><strong>Critical thinking</strong> — metacognition, logical reasoning, memory, attention, cognitive flexibility.</li>
                        <li><strong>21st-century competencies</strong> — creativity, collaboration, communication, adaptability, digital literacy.</li>
                        <li><strong>Early talent identification</strong> — continuous observation and targeted enrichment from the early years.</li>
                        <li><strong>Assessment for growth</strong> — formative and diagnostic checks that reinforce mastery and a growth mindset.</li>
                    </ul>
                </article>
                <article class="bn-pillar">
                    <h3>Social, emotional &amp; leadership</h3>
                    <ul>
                        <li><strong>Social &amp; emotional learning</strong> — self-awareness, self-regulation, empathy, resilience, interpersonal skills.</li>
                        <li><strong>Leadership development</strong> — responsibility, initiative, ethical decision-making and teamwork through real projects.</li>
                        <li><strong>Wellbeing &amp; mindfulness</strong> — emotional balance, positive identity, stress management and mental-health literacy.</li>
                    </ul>
                </article>
            </div>
        </section>

        {{-- E6 values --}}
        <section class="bn-home-section bn-values" style="padding-top:20px">
            <div class="bn-home-head">
                <p class="bn-eyebrow">Core values &middot; E&#8310;</p>
                <h2>E to the power of six</h2>
            </div>
            <div class="bn-values-grid">
                @foreach ([
                    ['Ethics', 'Integrity &amp; Islamic values'],
                    ['Empathy', 'Compassion &amp; understanding'],
                    ['Exploration', 'Innovation &amp; curiosity'],
                    ['Endeavour', 'Purposeful effort &amp; resilience'],
                    ['Excellence', 'High standards &amp; mastery'],
                    ['Empowerment', 'Personalisation &amp; wellbeing'],
                ] as $i => $v)
                    <div class="bn-value">
                        <span class="bn-value__e">E<sup>{{ $i + 1 }}</sup></span>
                        <h3>{{ $v[0] }}</h3>
                        <p>{!! $v[1] !!}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- AI + Curriculum + Campus --}}
        <section class="bn-home-section" style="padding-top:20px">
            <div class="bn-approach-cols">
                <div class="bn-approach-col">
                    <p class="bn-eyebrow">E-learning with AI</p>
                    <h3>Technology as a tutor</h3>
                    <ul>
                        <li><strong>Smart classrooms</strong> — interactive tools, virtual labs and adaptive AI systems.</li>
                        <li><strong>Digital ecosystem</strong> — integrated student portals and seamless access to resources.</li>
                        <li><strong>Digital literacy &amp; safety</strong> — responsible technology use, online safety and ethical behaviour.</li>
                    </ul>
                </div>
                <div class="bn-approach-col">
                    <p class="bn-eyebrow">Curriculum</p>
                    <h3>Balanced and globally relevant</h3>
                    <ul>
                        <li><strong>Balanced curriculum</strong> — literacy, numeracy, STEM, arts, humanities, leadership and life skills.</li>
                        <li><strong>Early years (EYFS-aligned)</strong> — play-based, inquiry-driven, developmentally appropriate.</li>
                        <li><strong>Hybrid approach</strong> — best practice from IB, Cambridge, IPC and IEYC.</li>
                        <li><strong>Activity-based development</strong> — hands-on work building fine motor skills, coordination and early literacy/numeracy.</li>
                    </ul>
                </div>
                <div class="bn-approach-col">
                    <p class="bn-eyebrow">Campus &amp; environment</p>
                    <h3>Built for how children learn</h3>
                    <ul>
                        <li><strong>Future-focused campus</strong> — safe, eco-friendly, child-centred spaces with modern labs, art studios and flexible learning hubs.</li>
                        <li><strong>Facilities</strong> — STEM and art labs, libraries, playgrounds, sports areas, and safe baby &amp; toddler areas guided by developmental milestones.</li>
                    </ul>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="bn-home-section" style="padding-bottom:70px">
            <div class="bn-handbook-inner bn-aurora">
                <div>
                    <h2>See it in a real week</h2>
                    <p>The best way to understand the Brainova approach is to watch your child in a session.</p>
                </div>
                <div class="bn-handbook-actions">
                    <a href="{{ route('frontend.book-free-trial') }}" class="bn-btn bn-btn--primary">Book a free trial</a>
                    <a href="{{ route('frontend.courses') }}" class="bn-btn bn-btn--ghost">Explore programs</a>
                </div>
            </div>
        </section>

    </div>
</div>

@endsection
