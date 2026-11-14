@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">Beyond Academics</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Beyond Academics</li>
                    </ol>
                </div>
                <div class="col-sm-6 text-sm-end">
                    @if ($data['category'])
                        <a href="{{ route('program-category.edit', $data['category']->id) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-pen"></i> Edit this banner in Website Setup
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @php($cat = $data['category'] ?? null)
        <div class="ba-overview-page">
        <section id="overview">
            <div class="ba-hero">
                <span class="ba-hero-badge">Overview</span>
                <div class="ba-hero-grid">
                    <div class="ba-hero-copy">
                        @if ($cat?->tagline)
                            <p class="ba-hero-eyebrow">{{ $cat->tagline }}</p>
                        @endif
                        <h2>{{ $cat?->hero_title ?: ($cat?->name ?: 'Learn New Skills, Explore New Possibilities') }}</h2>
                        @if ($cat?->hero_subtitle)
                            <p>{{ $cat->hero_subtitle }}</p>
                        @endif
                    </div>
                    @if ($cat?->image)
                        <div class="ba-hero-art">
                            <img src="{{ $cat->image }}" alt="">
                        </div>
                    @endif
                </div>
                @if (!$cat)
                    <p class="ba-hero-missing">No "Beyond Acedamics" category found in Website Setup &rarr; Course Catalogue &rarr; Program Category.</p>
                @endif
            </div>

            {{-- Featured Courses: a live reflection of Website Setup > Course
                 Catalogue > Program - nothing to manage here, add/edit a
                 Program there and it shows up here automatically. --}}
            <div class="ba-featured">
                <div class="ba-featured-head">
                    <h4>Featured Courses</h4>
                    <a href="{{ route('program.index') }}">Manage in Website Setup <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                @if ($data['featured']->isEmpty())
                    <p class="text-secondary mb-0" style="font-size: 13px;">No courses added yet — add one under Website Setup &rarr; Course Catalogue &rarr; Program.</p>
                @else
                    <div class="ba-course-grid">
                        @foreach ($data['featured'] as $program)
                            <a href="{{ route('frontend.course-detail', $program->slug) }}" target="_blank" rel="noopener" class="ba-course-card">
                                <div class="ba-course-card-media">
                                    @if ($program->image)
                                        <img src="{{ $program->image }}" alt="{{ $program->title }}">
                                    @endif
                                </div>
                                <div class="ba-course-card-body">
                                    <h5>{{ $program->title }}</h5>
                                    <p class="ba-course-meta">
                                        @if ($program->age_range)<span>{{ $program->age_range }}</span>@endif
                                        @if ($program->age_range && $program->lessons) <span>&bull;</span> @endif
                                        @if ($program->lessons)<span>{{ $program->lessons }}</span>@endif
                                    </p>
                                    <div class="ba-course-pills">
                                        @if ($program->badge)
                                            <span class="ba-course-badge">{{ $program->badge }}</span>
                                        @endif
                                        @if ($program->status_label_text)
                                            <span class="ba-status-pill ba-status-pill--{{ $program->status_label }}">{{ $program->status_label_text }}</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section id="course-details">
            @include('class-content._course-details-block')
        </section>

        <section id="progress-report" class="ba-placeholder-section">
            <h4>Progress &amp; Report</h4>
            <p class="text-secondary mb-0" style="font-size: 13px;">Content coming soon.</p>
        </section>
        </div>

    </div>
@endsection

@push('css')
<style>
    /* Same clean system-font stack the public site's own CSS falls back
       to - scoped to this page only via .ba-overview-page, so nothing
       else in the dashboard changes font. */
    .ba-overview-page {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    .ba-hero {
        background: #ffffff;
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        border-radius: 20px;
        padding: 2rem 2.25rem;
        margin-bottom: 1.25rem;
    }
    .ba-hero-badge {
        display: inline-block;
        background: #5e17eb;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.35rem 1rem;
        border-radius: 999px;
        margin-bottom: 1.25rem;
    }
    .ba-hero-grid {
        display: flex;
        align-items: stretch;
        gap: 2.5rem;
        flex-wrap: wrap;
        flex: 1 1 auto;
    }
    .ba-hero-eyebrow {
        font-size: 0.8rem;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        font-weight: 700;
        color: #6366f1;
        margin: 0 0 0.6rem;
    }
    .ba-hero-copy {
        flex: 1 1 auto;
        min-width: 0;
    }
    .ba-hero-copy h2 {
        font-size: clamp(1.5rem, 3vw, 2rem);
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 0.75rem;
        line-height: 1.25;
    }
    .ba-hero-copy p {
        font-size: 1rem;
        color: #475569;
        margin: 0;
        max-width: 48rem;
        line-height: 1.6;
    }
    .ba-hero-missing {
        font-size: 0.85rem;
        color: #8b93a3;
        margin: 0.75rem 0 0;
    }
    .ba-hero-art {
        flex: 0 0 42%;
        max-width: 460px;
        width: 100%;
        min-height: 140px;
    }
    .ba-hero-art img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 14px;
    }

    .ba-featured {
        background: #ffffff;
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        border-radius: 20px;
        padding: 1.5rem 1.75rem;
    }
    .ba-featured-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.1rem;
    }
    .ba-featured-head h4 {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
    }
    .ba-featured-head a {
        font-size: 0.85rem;
        font-weight: 600;
        color: #6366f1;
        text-decoration: none;
    }
    .ba-course-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }
    .ba-course-card {
        display: block;
        border: 1px solid var(--ot-border-basic-table, #edeef1);
        border-radius: 14px;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        transition: box-shadow .15s ease, transform .15s ease;
    }
    .ba-course-card:hover {
        box-shadow: 0 10px 26px rgba(15, 23, 42, .08);
        transform: translateY(-2px);
        color: inherit;
    }
    .ba-course-card-media {
        height: 110px;
        background: #eef1f6;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .ba-course-card-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .ba-course-card-body {
        padding: 0.85rem 1rem 1rem;
    }
    .ba-course-card-body h5 {
        font-size: 0.95rem;
        font-weight: 700;
        margin: 0 0 0.4rem;
        color: #0f172a;
    }
    .ba-course-meta {
        font-size: 0.82rem;
        color: #8b93a3;
        margin: 0 0 0.5rem;
    }
    .ba-course-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
    }
    .ba-course-badge {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        background: #e1f6fa;
        color: #007585;
        padding: 0.2rem 0.65rem;
        border-radius: 999px;
    }
    .ba-status-pill {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.2rem 0.65rem;
        border-radius: 999px;
    }
    .ba-status-pill--ongoing { background: #dcfce7; color: #15803d; }
    .ba-status-pill--upcoming { background: #dbeafe; color: #1d4ed8; }
    .ba-status-pill--coming_soon { background: #f1f5f9; color: #64748b; }
    .ba-status-pill--enrolling { background: #ece4fd; color: #5e17eb; }

    /* Overview, Course Details and Progress & Report are one page - the
       sidebar's sub-items are just anchor links into it, not separate
       pages. scroll-margin-top keeps the fixed header from covering the
       section heading when a link jumps here. */
    #overview, #course-details, #progress-report {
        scroll-margin-top: 100px;
        display: block;
    }
    #course-details, #progress-report {
        margin-top: 1.25rem;
    }
    .ba-placeholder-section {
        background: #ffffff;
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        border-radius: 20px;
        padding: 1.5rem 1.75rem;
    }
    .ba-placeholder-section h4 {
        margin: 0 0 0.5rem;
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
    }

    @media (max-width: 767px) {
        .ba-hero {
            padding: 1.5rem;
        }
        .ba-hero-grid {
            flex-direction: column;
            align-items: stretch;
            gap: 1.25rem;
        }
        .ba-hero-art {
            flex: none;
            max-width: none;
            height: 200px;
            min-height: 0;
        }
    }
</style>
@endpush
