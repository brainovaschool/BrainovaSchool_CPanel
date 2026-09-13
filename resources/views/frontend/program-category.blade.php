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
                    <div class="fe-courses-hero-cta">
                        <a href="{{ route('frontend.contact') }}" class="fe-btn-pill fe-btn-primary">Talk to admissions</a>
                        <a href="{{ route('frontend.online-admission') }}" class="fe-btn-pill fe-btn-ghost">Start online admission</a>
                    </div>
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
                                    <p class="fe-course-card-desc">{{ \Illuminate\Support\Str::limit(strip_tags($program->description ?? ''), 120) }}</p>

                                    @if ($program->price)
                                        <div class="fe-course-card-price-wrap" aria-label="Course fee">
                                            <span class="fe-course-card-price-label">Fee</span>
                                            <div class="fe-course-card-price">{{ $program->price }}</div>
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
            @else
                <div class="fe-courses-empty fe-is-visible">
                    <p>Only Registered members can avail this facility. For registration, <a href="{{ route('frontend.contact') }}">contact our representative</a>.</p>
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
