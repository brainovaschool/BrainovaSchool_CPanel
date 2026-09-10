<!-- HOME: programs -->
@if (!empty($data['programCategories']) && count($data['programCategories']))
<section class="bn-home-section bn-programs">
    <div class="container">
        <div class="bn-home-head">
            <p class="bn-eyebrow">Our programs</p>
            <h2>Four ways to learn with Brainova</h2>
            <p>Every path is taught by the same faculty and tracked with the same weekly reporting.</p>
        </div>

        <div class="bn-programs-grid">
            @foreach ($data['programCategories'] as $cat)
                <a href="{{ route('frontend.program-category', $cat->slug) }}" class="bn-program-card bn-accent-{{ $cat->accent ?: 'teal' }}">
                    <span class="bn-program-card__glow" aria-hidden="true"></span>
                    <h3>{{ $cat->name }}</h3>
                    <p>{{ $cat->tagline ?: 'Explore what this path covers.' }}</p>
                    <span class="bn-program-card__foot">
                        <span>{{ $cat->programs_count > 0 ? $cat->programs_count . ' ' . \Illuminate\Support\Str::plural('programme', $cat->programs_count) : 'Now enrolling' }}</span>
                        <span class="bn-program-card__go">Explore &rarr;</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
