@extends('frontend.master')

@php
    $category   = $data['category'];
    $focuses    = $data['focuses'] ?? collect();
    $programs   = $data['programs'] ?? collect();
    $active     = $data['active_focus'] ?? null;
    $activeFocusModel = $data['active_focus_model'] ?? null;
    $total      = $data['total'] ?? 0;
    $trust      = $data['trust'] ?? [];
    $heroImg    = $category->image;

    // Same "don't promise what isn't live" status as the homepage cards
    // (see frontend/partials/home/programs.blade.php) — kept in sync by
    // slug rather than shared code, since this is a stopgap until launch
    // status becomes a real admin-editable field on Program Category.
    $comingSoonSlugs = ['homeschooling', 'social-clubs'];
    $isComingSoon    = in_array($category->slug, $comingSoonSlugs, true);
    $launchNote      = $isComingSoon ? 'Starts around March 2027.' : null;
@endphp

@section('title')
    {{ $category->hero_title ?: $category->name }}
@endsection

@push('css')
    <link rel="stylesheet" href="{{ global_asset('frontend') }}/css/frontend-courses.css">
@endpush

@section('main')

<div class="fe-courses-page">
    <div class="fe-courses-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="{{ $heroImg ? 'col-lg-7' : 'col-lg-10 col-xl-8' }}">
                    @if ($category->tagline)
                        <p style="font-size:.8rem;letter-spacing:.14em;text-transform:uppercase;font-weight:600;color:var(--bn-primary-strong,#007585);margin-bottom:12px">{{ $category->tagline }}</p>
                    @endif
                    <h1>{{ $category->hero_title ?: $category->name }}</h1>
                    @if ($category->hero_subtitle)
                        <p class="fe-courses-hero-lead">{{ $category->hero_subtitle }}</p>
                    @endif
                    @if ($isComingSoon)
                        <p style="display:inline-block;background:#fff3d6;color:#8a5b00;font-weight:600;font-size:.85rem;padding:6px 14px;border-radius:20px;margin-bottom:14px;">
                            Coming soon — {{ $launchNote }}
                        </p>
                        <div class="fe-courses-hero-cta">
                            <a href="#waitlist" class="fe-btn-pill fe-btn-primary">Join the waitlist</a>
                            <a href="{{ route('frontend.contact') }}" class="fe-btn-pill fe-btn-ghost">Ask a question</a>
                        </div>
                    @else
                        <div class="fe-courses-hero-cta">
                            <a href="{{ route('frontend.contact') }}" class="fe-btn-pill fe-btn-primary">Talk to admissions</a>
                            <a href="{{ route('frontend.online-admission') }}" class="fe-btn-pill fe-btn-ghost">Start online admission</a>
                        </div>
                    @endif
                </div>
                @if ($heroImg)
                    <div class="col-lg-5 d-none d-lg-block">
                        <img src="{{ $heroImg }}" alt="{{ $category->name }}" class="img-fluid" style="border-radius:18px">
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($focuses->count())
        <section class="fe-courses-filters section_padding pt-4 pb-0">
            <div class="container">
                <div class="fe-filter-pills" role="tablist" aria-label="{{ $category->name }} focus areas">
                    <a href="{{ route('frontend.program-category', $category->slug) }}"
                        class="fe-filter-pill {{ $active ? '' : 'fe-is-active' }}">All</a>
                    @foreach ($focuses as $focus)
                        <a href="{{ route('frontend.program-category', ['category' => $category->slug, 'focus' => $focus->slug]) }}"
                            class="fe-filter-pill {{ $active === $focus->slug ? 'fe-is-active' : '' }}">{{ $focus->name }}</a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="fe-courses-grid section_padding pt-4">
        <div class="container">
            @if ($activeFocusModel && $activeFocusModel->description)
                <p class="fe-focus-description mb-4">{{ $activeFocusModel->description }}</p>
            @endif
            <div class="fe-courses-toolbar">
                <p class="fe-courses-count mb-0">
                    @if ($paginator->total() > 0)
                        Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }} programs
                        @if ($active)
                            <span class="fe-courses-count-filter">({{ $total }} total in {{ $category->name }})</span>
                        @endif
                    @endif
                </p>
                <a href="{{ route('frontend.contact') }}" class="fe-btn-pill fe-btn-ghost fe-mini d-none d-sm-inline-flex">Ask a question</a>
            </div>

            @if ($programs->count())
                <div class="row fe-courses-row">
                    @foreach ($programs as $program)
                        @php $accent = $program->accent ?: 'indigo'; @endphp
                        <div class="col-xl-4 col-lg-4 col-md-6 mb-4 fe-course-wrap">
                            <article class="fe-course-card">
                                <div class="fe-course-card-media fe-accent-{{ $accent }} {{ $program->image ? '' : 'fe-course-card-media--placeholder' }}">
                                    @if ($program->image)
                                        <img src="{{ $program->image }}" alt="{{ $program->title }}">
                                    @endif
                                    <span class="fe-course-card__badge">{{ $program->badge ?: $category->name }}</span>
                                </div>
                                <div class="fe-course-card-body">
                                    <h3 class="fe-course-card-title">{{ $program->title }}</h3>
                                    {{-- M4: every program is a Brainova program — spelled out once here
                                         rather than only next to named sub-brands like CODENOVA/AI Sparklab,
                                         since there's no reliable way to tell those apart from a plain
                                         program title without a dedicated field. --}}
                                    <p style="font-size:.72rem;letter-spacing:.04em;color:#8a97a3;margin:-6px 0 8px;">A Brainova program</p>
                                    <p class="fe-course-card-desc">{{ \Illuminate\Support\Str::limit(strip_tags($program->description ?? ''), 120) }}</p>

                                    @if ($program->price)
                                        <div class="fe-course-card-price-wrap" aria-label="Course fee">
                                            <span class="fe-course-card-price-label">Fee</span>
                                            <div class="fe-course-card-price">{{ $program->price }}</div>
                                            @if (\Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($program->price), 'contact'))
                                                <div style="font-size:.72rem;color:#8a97a3;">We reply within 1 business day</div>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="fe-course-meta">
                                        @if ($program->age_range)<span><i class="fas fa-user-graduate"></i>{{ $program->age_range }}</span>@endif
                                        @if ($program->grade)<span><i class="fas fa-school"></i>{{ $program->grade }}</span>@endif
                                        @if ($program->lessons)<span><i class="fas fa-book-open"></i>{{ $program->lessons }}</span>@endif
                                        @if ($program->duration)<span><i class="far fa-clock"></i>{{ $program->duration }}</span>@endif
                                    </div>

                                    @if ($program->enrolled)
                                        <div class="fe-course-enrolled"><i class="fas fa-users me-1"></i>{{ $program->enrolled }}</div>
                                    @endif

                                    <div class="fe-course-actions">
                                        <a href="{{ route('frontend.course-detail', $program->slug) }}" class="fe-btn-pill fe-btn-primary fe-mini">View program</a>
                                        <a href="{{ route('frontend.contact') }}" class="fe-btn-pill fe-btn-ghost fe-mini">Enquire</a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>

                @include('frontend.partials.courses-pagination', ['paginator' => $paginator])
            @elseif ($isComingSoon)
                <div class="fe-courses-empty fe-is-visible">
                    <p>{{ $category->name }} hasn't launched yet — {{ \Illuminate\Support\Str::lower($launchNote) }} Join the waitlist below and we'll let you know as soon as it opens.</p>
                </div>
            @else
                <div class="fe-courses-empty fe-is-visible">
                    <p>No programs are listed here yet. <a href="{{ route('frontend.contact') }}">Contact us</a> and we'll point you in the right direction.</p>
                </div>
            @endif

            @if ($isComingSoon)
                <div id="waitlist" style="margin-top:40px;">
                    @include('frontend.partials.waitlist-form', ['category' => $category, 'launchNote' => $launchNote])
                </div>
            @endif
        </div>
    </section>

    @if (!empty($trust['headline']))
        <section class="fe-courses-trust">
            <div class="container">
                <h2>{{ $trust['headline'] }}</h2>
                @if (!empty($trust['body']))
                    <p class="mb-0">{{ $trust['body'] }}</p>
                @endif
            </div>
        </section>
    @endif
</div>
@endsection
