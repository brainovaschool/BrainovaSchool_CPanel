<!-- HOME: programs -->
@if (!empty($data['programCategories']) && count($data['programCategories']))
<section class="bn-home-section bn-programs">
    <div class="container">
        <div class="bn-home-head">
            <p class="bn-eyebrow">Our programs</p>
            <h2>Four ways to learn with Brainova</h2>
            <p>One faculty, one school — each path tracked and reported on consistently.</p>
        </div>

        <div class="bn-programs-grid">
            @php
                // Real launch status per path, since the site must only ever
                // promise what's actually live (see the website fix list,
                // items H1/H2/H12). Matched by slug rather than a stored
                // field — the fastest fix for a status that's fixed and
                // known right now; worth becoming an admin-editable field
                // on Program Category once more paths start changing status
                // regularly.
                $launchStatus = [
                    'homeschooling'     => ['label' => 'Coming soon — starts around March 2027', 'waitlist' => true],
                    'tutoring'          => ['label' => 'Enrolling now — classes start October 2026', 'waitlist' => false],
                    'academic-support'  => ['label' => 'Enrolling now — classes start October 2026', 'waitlist' => false],
                    'social-clubs'      => ['label' => 'Coming soon — starts around March 2027', 'waitlist' => true],
                ];
            @endphp
            @foreach ($data['programCategories'] as $cat)
                @php $status = $launchStatus[$cat->slug] ?? null; @endphp
                <a href="{{ route('frontend.program-category', $cat->slug) }}" class="bn-program-card bn-accent-{{ $cat->accent ?: 'teal' }}">
                    <span class="bn-program-card__glow" aria-hidden="true"></span>
                    <h3>{{ $cat->name }}</h3>
                    <p>{{ $cat->tagline ?: 'Explore what this path covers.' }}</p>
                    <span class="bn-program-card__foot">
                        <span>
                            @if ($status)
                                {{ $status['label'] }}
                            @elseif ($cat->programs_count > 0)
                                {{ $cat->programs_count }} {{ \Illuminate\Support\Str::plural('program', $cat->programs_count) }}
                            @else
                                Coming soon
                            @endif
                        </span>
                        <span class="bn-program-card__go">Explore &rarr;</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
