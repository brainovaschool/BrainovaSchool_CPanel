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
                        <li class="breadcrumb-item">Core Academics</li>
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
        <div class="ca-hero">
            <span class="ca-hero-badge">Overview</span>
            <div class="ca-hero-grid">
                <div class="ca-hero-copy">
                    @if ($cat?->tagline)
                        <p class="ca-hero-eyebrow">{{ $cat->tagline }}</p>
                    @endif
                    <h2>{{ $cat?->hero_title ?: ($cat?->name ?: 'Strong Foundations, Real Results') }}</h2>
                    @if ($cat?->hero_subtitle)
                        <p>{{ $cat->hero_subtitle }}</p>
                    @endif
                </div>
                @if ($cat?->image)
                    <div class="ca-hero-art">
                        <img src="{{ $cat->image }}" alt="">
                    </div>
                @endif
            </div>
            @if (!$cat)
                <p class="ca-hero-missing">No "Core Academics" category found in Website Setup &rarr; Course Catalogue &rarr; Program Category.</p>
            @endif
        </div>

        {{-- Featured Courses: a live reflection of Website Setup > Course
             Catalogue > Program - nothing to manage here, add/edit a
             Program there and it shows up here automatically. --}}
        <div class="ca-featured">
            <div class="ca-featured-head">
                <h4>Featured Courses</h4>
                <a href="{{ route('program.index') }}">Manage in Website Setup <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            @if ($data['featured']->isEmpty())
                <p class="text-secondary mb-0" style="font-size: 13px;">No courses added yet — add one under Website Setup &rarr; Course Catalogue &rarr; Program, in the Core Academics category.</p>
            @else
                <div class="ca-course-grid">
                    @foreach ($data['featured'] as $program)
                        <a href="{{ route('frontend.course-detail', $program->slug) }}" target="_blank" rel="noopener" class="ca-course-card">
                            <div class="ca-course-card-media">
                                @if ($program->image)
                                    <img src="{{ $program->image }}" alt="{{ $program->title }}">
                                @endif
                            </div>
                            <div class="ca-course-card-body">
                                <h5>{{ $program->title }}</h5>
                                <p class="ca-course-meta">
                                    @if ($program->age_range)<span>{{ $program->age_range }}</span>@endif
                                    @if ($program->age_range && $program->lessons) <span>&bull;</span> @endif
                                    @if ($program->lessons)<span>{{ $program->lessons }}</span>@endif
                                </p>
                                @if ($program->badge)
                                    <span class="ca-course-badge">{{ $program->badge }}</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
@endsection

@push('css')
<style>
    .ca-hero {
        position: relative;
        background: linear-gradient(120deg, #efe7ff 0%, #e4ecff 60%, #eef2ff 100%);
        border-radius: 20px;
        padding: 28px 32px 32px;
        margin-bottom: 20px;
        overflow: hidden;
    }
    .ca-hero-badge {
        display: inline-block;
        background: #5e17eb;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        padding: 6px 16px;
        border-radius: 999px;
        margin-bottom: 16px;
    }
    .ca-hero-grid {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        flex-wrap: wrap;
    }
    .ca-hero-eyebrow {
        font-size: 12px;
        letter-spacing: .08em;
        text-transform: uppercase;
        font-weight: 700;
        color: #5e17eb;
        margin: 0 0 8px;
    }
    .ca-hero-copy {
        flex: 1 1 auto;
        min-width: 0;
    }
    .ca-hero-copy h2 {
        font-size: 28px;
        font-weight: 800;
        color: #1f2430;
        margin: 0 0 10px;
        line-height: 1.25;
    }
    .ca-hero-copy p {
        font-size: 15px;
        color: #475065;
        margin: 0;
        max-width: 48ch;
    }
    .ca-hero-missing {
        font-size: 13px;
        color: #8b5a00;
        background: #fff7e6;
        border-radius: 8px;
        padding: 8px 12px;
        margin: 12px 0 0;
    }
    .ca-hero-art {
        flex: 0 0 38%;
        max-width: 380px;
        width: 100%;
    }
    .ca-hero-art img {
        display: block;
        width: 100%;
        aspect-ratio: 1920 / 700;
        object-fit: cover;
        border-radius: 14px;
    }

    .ca-featured {
        background: var(--ot-bg-table-card, #fff);
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        border-radius: 16px;
        padding: 20px 22px;
        margin-bottom: 20px;
    }
    .ca-featured-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .ca-featured-head h4 {
        margin: 0;
        font-size: 17px;
        font-weight: 700;
        color: #1f2430;
    }
    .ca-featured-head a {
        font-size: 13px;
        font-weight: 600;
        color: #5e17eb;
        text-decoration: none;
    }
    .ca-course-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
    }
    .ca-course-card {
        display: block;
        border: 1px solid var(--ot-border-basic-table, #edeef1);
        border-radius: 14px;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        transition: box-shadow .12s ease, transform .12s ease;
    }
    .ca-course-card:hover {
        box-shadow: 0 6px 18px rgba(30, 30, 60, .08);
        transform: translateY(-2px);
        color: inherit;
    }
    .ca-course-card-media {
        height: 110px;
        background: #eef1f6;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .ca-course-card-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .ca-course-card-body {
        padding: 12px 14px 14px;
    }
    .ca-course-card-body h5 {
        font-size: 14px;
        font-weight: 700;
        margin: 0 0 6px;
        color: #1f2430;
    }
    .ca-course-meta {
        font-size: 12px;
        color: #8b93a3;
        margin: 0 0 8px;
    }
    .ca-course-badge {
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        background: #e1f6fa;
        color: #007585;
        padding: 3px 10px;
        border-radius: 999px;
    }

    @media (max-width: 575px) {
        .ca-hero-art { display: none; }
    }
</style>
@endpush
