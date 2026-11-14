@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">Overview</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Beyond Screens</li>
                        <li class="breadcrumb-item">Overview</li>
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
        <div class="bs-overview-page">
            <div class="bs-hero">
                <span class="bs-hero-badge">Overview</span>
                <div class="bs-hero-grid">
                    <div class="bs-hero-copy">
                        @if ($cat?->tagline)
                            <p class="bs-hero-eyebrow">{{ $cat->tagline }}</p>
                        @endif
                        <h2>{{ $cat?->hero_title ?: ($cat?->name ?: 'Hands-On Learning, Screen-Free') }}</h2>
                        @if ($cat?->hero_subtitle)
                            <p>{{ $cat->hero_subtitle }}</p>
                        @endif
                    </div>
                    @if ($cat?->image)
                        <div class="bs-hero-art">
                            <img src="{{ $cat->image }}" alt="">
                        </div>
                    @endif
                </div>
                @if (!$cat)
                    <p class="bs-hero-missing">No "Beyond Screens" category found in Website Setup &rarr; Course Catalogue &rarr; Program Category.</p>
                @endif
            </div>

            {{-- Featured Courses: a live reflection of Website Setup > Course
                 Catalogue > Program - nothing to manage here, add/edit a
                 Program there and it shows up here automatically. --}}
            <div class="bs-featured">
                <div class="bs-featured-head">
                    <h4>Featured Courses</h4>
                    <a href="{{ route('program.index') }}">Manage in Website Setup <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                @if ($data['featured']->isEmpty())
                    <p class="text-secondary mb-0" style="font-size: 13px;">No courses added yet — add one under Website Setup &rarr; Course Catalogue &rarr; Program, in the Beyond Screens category.</p>
                @else
                    <div class="bs-course-grid">
                        @foreach ($data['featured'] as $program)
                            <a href="{{ route('frontend.course-detail', $program->slug) }}" target="_blank" rel="noopener" class="bs-course-card">
                                <div class="bs-course-card-media">
                                    @if ($program->image)
                                        <img src="{{ $program->image }}" alt="{{ $program->title }}">
                                    @endif
                                </div>
                                <div class="bs-course-card-body">
                                    <h5>{{ $program->title }}</h5>
                                    <p class="bs-course-meta">
                                        @if ($program->age_range)<span>{{ $program->age_range }}</span>@endif
                                        @if ($program->age_range && $program->lessons) <span>&bull;</span> @endif
                                        @if ($program->lessons)<span>{{ $program->lessons }}</span>@endif
                                    </p>
                                    @if ($program->badge)
                                        <span class="bs-course-badge">{{ $program->badge }}</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection

@push('css')
<style>
    /* Same clean system-font stack the public site's own CSS falls back
       to - scoped to this page only via .bs-overview-page, so nothing
       else in the dashboard changes font. */
    .bs-overview-page {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    .bs-hero {
        background: #ffffff;
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        border-radius: 20px;
        padding: 2rem 2.25rem;
        margin-bottom: 1.25rem;
    }
    .bs-hero-badge {
        display: inline-block;
        background: #5e17eb;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.35rem 1rem;
        border-radius: 999px;
        margin-bottom: 1.25rem;
    }
    .bs-hero-grid {
        display: flex;
        align-items: center;
        gap: 2.5rem;
        flex-wrap: wrap;
        flex: 1 1 auto;
    }
    .bs-hero-eyebrow {
        font-size: 0.8rem;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        font-weight: 700;
        color: #6366f1;
        margin: 0 0 0.6rem;
    }
    .bs-hero-copy {
        flex: 1 1 auto;
        min-width: 0;
    }
    .bs-hero-copy h2 {
        font-size: clamp(1.5rem, 3vw, 2rem);
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 0.75rem;
        line-height: 1.25;
    }
    .bs-hero-copy p {
        font-size: 1rem;
        color: #475569;
        margin: 0;
        max-width: 48rem;
        line-height: 1.6;
    }
    .bs-hero-missing {
        font-size: 0.85rem;
        color: #8b5a00;
        background: #fff7e6;
        border-radius: 8px;
        padding: 0.5rem 0.75rem;
        margin: 0.75rem 0 0;
    }
    .bs-hero-art {
        flex: 0 0 42%;
        max-width: 460px;
        width: 100%;
    }
    .bs-hero-art img {
        display: block;
        width: 100%;
        aspect-ratio: 1920 / 700;
        object-fit: cover;
        border-radius: 14px;
    }

    .bs-featured {
        background: #ffffff;
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        border-radius: 20px;
        padding: 1.5rem 1.75rem;
    }
    .bs-featured-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.1rem;
    }
    .bs-featured-head h4 {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
    }
    .bs-featured-head a {
        font-size: 0.85rem;
        font-weight: 600;
        color: #6366f1;
        text-decoration: none;
    }
    .bs-course-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }
    .bs-course-card {
        display: block;
        border: 1px solid var(--ot-border-basic-table, #edeef1);
        border-radius: 14px;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        transition: box-shadow .15s ease, transform .15s ease;
    }
    .bs-course-card:hover {
        box-shadow: 0 10px 26px rgba(15, 23, 42, .08);
        transform: translateY(-2px);
        color: inherit;
    }
    .bs-course-card-media {
        height: 110px;
        background: #eef1f6;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .bs-course-card-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .bs-course-card-body {
        padding: 0.85rem 1rem 1rem;
    }
    .bs-course-card-body h5 {
        font-size: 0.95rem;
        font-weight: 700;
        margin: 0 0 0.4rem;
        color: #0f172a;
    }
    .bs-course-meta {
        font-size: 0.82rem;
        color: #8b93a3;
        margin: 0 0 0.5rem;
    }
    .bs-course-badge {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        background: #e1f6fa;
        color: #007585;
        padding: 0.2rem 0.65rem;
        border-radius: 999px;
    }

    @media (max-width: 767px) {
        .bs-hero {
            padding: 1.5rem;
        }
        .bs-hero-grid {
            flex-direction: column;
            align-items: stretch;
            gap: 1.25rem;
        }
        .bs-hero-art {
            flex: none;
            max-width: none;
        }
    }
</style>
@endpush
