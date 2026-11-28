@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">Academics Boost</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Academics Boost</li>
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
        <div class="ca-overview-page">
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
                    <p class="ca-hero-missing">No "Academics Boost" category found yet — add it in Website Setup &rarr; Course Catalogue &rarr; Program Category.</p>
                @endif
            </div>

            {{-- Featured Courses: a live reflection of Website Setup > Course
                 Catalogue > Program - nothing to manage here, add/edit a
                 Program there and it shows up here automatically. --}}
            <div class="ca-featured">
                <div class="ca-featured-head">
                    <div>
                        <span class="ca-section-chip"><i class="fa-solid fa-book-open"></i> Courses</span>
                        <h4>Featured Courses</h4>
                    </div>
                    @if (auth()->user()?->role_id == 1)
                        <a href="{{ route('program.index') }}">Manage in Website Setup <i class="fa-solid fa-arrow-right"></i></a>
                    @endif
                </div>
                @if ($data['featured']->isEmpty())
                    <p class="text-secondary mb-0" style="font-size: 13px;">No courses added yet — add one under Website Setup &rarr; Course Catalogue &rarr; Program, in the Academics Boost category.</p>
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
                                    <div class="ca-course-pills">
                                        @if ($program->badge)
                                            <span class="ca-course-badge">{{ $program->badge }}</span>
                                        @endif
                                        @if ($program->status_label_text)
                                            <span class="ca-status-pill ca-status-pill--{{ $program->status_label }}">{{ $program->status_label_text }}</span>
                                        @endif
                                    </div>
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
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap">
<style>
    /* Same sky-blue "gamified" look as the Homework page
       (homework-teacher-gamified.css's .hw-game-hero/.hw-gamified) -
       ported here under the existing .ca-* class names so none of the
       Blade markup/data above needed to change, only how it's painted. */
    .ca-overview-page {
        font-family: "DM Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        background: linear-gradient(180deg, #f8fafc 0%, #eff6ff 40%, #f8fbff 100%);
        padding: 1.1rem 1.1rem 1.5rem;
        border-radius: 18px;
    }

    .ca-hero {
        position: relative;
        overflow: hidden;
        background: linear-gradient(125deg, #dbeafe 0%, #e0f2fe 25%, #f0f9ff 55%, #ffffff 100%);
        border: 1px solid rgba(147, 197, 253, 0.55);
        border-radius: 18px;
        padding: 2rem 2.25rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 28px rgba(14, 165, 233, 0.12);
    }
    .ca-hero::after {
        content: "";
        position: absolute;
        top: -40%;
        right: -8%;
        width: 220px;
        height: 220px;
        background: radial-gradient(circle, rgba(56, 189, 248, 0.22) 0%, transparent 70%);
        pointer-events: none;
    }
    .ca-hero-badge {
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: linear-gradient(90deg, rgba(14, 165, 233, 0.15), rgba(59, 130, 246, 0.12));
        border: 1px solid rgba(56, 189, 248, 0.35);
        color: #0369a1;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        margin-bottom: 1.25rem;
    }
    .ca-hero-grid {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: stretch;
        gap: 2.5rem;
        flex-wrap: wrap;
        flex: 1 1 auto;
    }
    .ca-hero-eyebrow {
        font-size: 0.75rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        font-weight: 700;
        color: #0ea5e9;
        margin: 0 0 0.5rem;
        font-family: "Space Grotesk", "DM Sans", system-ui, sans-serif;
    }
    .ca-hero-copy {
        flex: 1 1 auto;
        min-width: 0;
    }
    .ca-hero-copy h2 {
        font-family: "Libre Baskerville", serif;
        font-size: clamp(1.5rem, 3vw, 2.1rem);
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.01em;
        margin: 0 0 0.75rem;
        line-height: 1.25;
    }
    .ca-hero-copy p {
        font-size: 1rem;
        color: #64748b;
        margin: 0;
        max-width: 48rem;
        line-height: 1.6;
    }
    .ca-hero-missing {
        position: relative;
        z-index: 1;
        font-size: 0.85rem;
        color: #8b93a3;
        margin: 0.75rem 0 0;
    }
    .ca-hero-art {
        position: relative;
        z-index: 1;
        flex: 0 0 42%;
        max-width: 460px;
        width: 100%;
        min-height: 140px;
    }
    .ca-hero-art img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 14px;
    }

    .ca-featured {
        background: linear-gradient(168deg, #eff6ff 0%, #dbeafe 20%, #e0f2fe 45%, #f8fafc 85%, #ffffff 100%);
        border: 1px solid rgba(147, 197, 253, 0.5);
        border-radius: 18px;
        padding: 1.5rem 1.75rem;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85), 0 10px 28px rgba(37, 99, 235, 0.08);
    }
    .ca-featured-head {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-bottom: 1.1rem;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .ca-section-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #0369a1;
        background: rgba(224, 242, 254, 0.9);
        border: 1px solid rgba(125, 211, 252, 0.5);
        padding: 0.25rem 0.55rem;
        border-radius: 999px;
        margin-bottom: 0.4rem;
    }
    .ca-featured-head h4 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 800;
        color: #0f172a;
        font-family: "Space Grotesk", "DM Sans", system-ui, sans-serif;
    }
    .ca-featured-head a {
        font-size: 0.85rem;
        font-weight: 700;
        color: #0369a1;
        text-decoration: none;
    }
    .ca-course-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }
    .ca-course-card {
        display: block;
        background: linear-gradient(145deg, #ffffff 0%, #f0f9ff 100%);
        border: 1px solid rgba(147, 197, 253, 0.45);
        border-radius: 14px;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.05);
        transition: box-shadow .15s ease, transform .15s ease;
    }
    .ca-course-card:hover {
        box-shadow: 0 12px 28px rgba(14, 165, 233, 0.16);
        transform: translateY(-2px);
        color: inherit;
    }
    .ca-course-card-media {
        height: 110px;
        background: #e0f2fe;
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
        padding: 0.85rem 1rem 1rem;
    }
    .ca-course-card-body h5 {
        font-size: 0.95rem;
        font-weight: 700;
        margin: 0 0 0.4rem;
        color: #0f172a;
    }
    .ca-course-meta {
        font-size: 0.82rem;
        color: #8b93a3;
        margin: 0 0 0.5rem;
    }
    .ca-course-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
    }
    .ca-course-badge {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        background: rgba(224, 242, 254, 0.9);
        color: #0369a1;
        border: 1px solid rgba(125, 211, 252, 0.5);
        padding: 0.2rem 0.65rem;
        border-radius: 999px;
    }
    /* Status pill colours are left as-is - they signal real state
       (ongoing/upcoming/coming soon/enrolling), not just decoration. */
    .ca-status-pill {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.2rem 0.65rem;
        border-radius: 999px;
    }
    .ca-status-pill--ongoing { background: #dcfce7; color: #15803d; }
    .ca-status-pill--upcoming { background: #dbeafe; color: #1d4ed8; }
    .ca-status-pill--coming_soon { background: #f1f5f9; color: #64748b; }
    .ca-status-pill--enrolling { background: #ece4fd; color: #5e17eb; }

    @media (max-width: 767px) {
        .ca-overview-page {
            padding: 0.75rem 0.75rem 1.25rem;
        }
        .ca-hero {
            padding: 1.5rem;
        }
        .ca-hero-grid {
            flex-direction: column;
            align-items: stretch;
            gap: 1.25rem;
        }
        .ca-hero-art {
            flex: none;
            max-width: none;
            height: 200px;
            min-height: 0;
        }
    }
</style>
@endpush
