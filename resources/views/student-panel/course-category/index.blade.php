@extends('student-panel.partials.master')

@section('title')
{{ $data['title'] }}
@endsection

@section('content')
<div class="page-content">
    <div class="sp-cat-page">
        <section id="overview">
            @php($cat = $data['category'] ?? null)
            <div class="sp-cat-hero">
                <span class="sp-cat-hero-badge">Overview</span>
                <div class="sp-cat-hero-grid">
                    <div class="sp-cat-hero-copy">
                        @if ($cat?->tagline)
                            <p class="sp-cat-hero-eyebrow">{{ $cat->tagline }}</p>
                        @endif
                        <h2>{{ $cat?->hero_title ?: $data['categoryTitle'] }}</h2>
                        @if ($cat?->hero_subtitle)
                            <p>{{ $cat->hero_subtitle }}</p>
                        @endif
                    </div>
                    @if ($cat?->image)
                        <div class="sp-cat-hero-art">
                            <img src="{{ $cat->image }}" alt="">
                        </div>
                    @endif
                </div>
            </div>

            <div class="sp-cat-featured">
                <div class="sp-cat-featured-head">
                    <h4>My Courses</h4>
                </div>
                @if ($data['courseOptions']->isEmpty())
                    <p class="text-secondary mb-0" style="font-size: 13px;">You're not enrolled in any {{ $data['categoryTitle'] }} course yet.</p>
                @else
                    <div class="sp-cat-course-grid">
                        @foreach ($data['courseOptions'] as $program)
                            <a href="{{ route('student-panel-course.index', $data['categorySlug']) }}?program={{ $program->id }}#course-details" class="sp-cat-course-card">
                                <h5>{{ $program->title }}</h5>
                                <p class="sp-cat-course-meta">
                                    @if ($program->age_range)<span>{{ $program->age_range }}</span>@endif
                                    @if ($program->age_range && $program->lessons) <span>&bull;</span> @endif
                                    @if ($program->lessons)<span>{{ $program->lessons }}</span>@endif
                                </p>
                                @if ($program->status_label_text)
                                    <span class="sp-cat-status-pill sp-cat-status-pill--{{ $program->status_label }}">{{ $program->status_label_text }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section id="course-details">
            @include('student-panel.course-category._course-details-block')
        </section>

        <section id="progress-report">
            <h4>Progress &amp; Report</h4>
            @include('student-panel.course-category._progress-report-block')
        </section>
    </div>
</div>
@endsection

@push('css')
<style>
    #overview, #course-details, #progress-report { scroll-margin-top: 100px; display: block; }
    #course-details, #progress-report { margin-top: 1.25rem; }
    #progress-report h4 { margin: 0 0 0.75rem; font-size: 1.15rem; font-weight: 700; color: #0f172a; }

    .sp-cat-hero {
        background: linear-gradient(135deg, #5e17eb 0%, #7c3aed 100%);
        border-radius: 20px;
        padding: 2rem 2.25rem;
        color: #ffffff;
        margin-bottom: 1rem;
    }
    .sp-cat-hero-badge { display: inline-block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background: rgba(255,255,255,.18); padding: 0.25rem 0.7rem; border-radius: 999px; margin-bottom: 0.75rem; }
    .sp-cat-hero-grid { display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
    .sp-cat-hero-copy { flex: 1; min-width: 220px; }
    .sp-cat-hero-eyebrow { font-size: 0.85rem; font-weight: 600; opacity: .85; margin: 0 0 0.3rem; }
    .sp-cat-hero-copy h2 { font-size: 1.6rem; font-weight: 800; margin: 0 0 0.4rem; }
    .sp-cat-hero-copy p { font-size: 0.92rem; opacity: .9; margin: 0; }
    .sp-cat-hero-art img { max-width: 160px; border-radius: 14px; }
    .sp-cat-featured {
        background: #ffffff;
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        border-radius: 20px;
        padding: 1.25rem 1.5rem;
    }
    .sp-cat-featured-head h4 { margin: 0 0 0.9rem; font-size: 1.05rem; font-weight: 700; color: #0f172a; }
    .sp-cat-course-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
    .sp-cat-course-card {
        display: block; border: 1px solid var(--ot-border-basic-table, #e4e7ec); border-radius: 14px;
        padding: 1rem 1.1rem; text-decoration: none; color: inherit; transition: box-shadow .15s ease;
    }
    .sp-cat-course-card:hover { box-shadow: 0 6px 18px rgba(15,23,42,.08); }
    .sp-cat-course-card h5 { font-size: 0.95rem; font-weight: 700; color: #0f172a; margin: 0 0 0.35rem; }
    .sp-cat-course-meta { font-size: 0.78rem; color: #8b93a3; margin: 0 0 0.5rem; display: flex; gap: 0.4rem; }
    .sp-cat-status-pill { display: inline-block; font-size: 0.68rem; font-weight: 700; padding: 0.15rem 0.55rem; border-radius: 999px; }
    .sp-cat-status-pill--ongoing { background: #dcfce7; color: #15803d; }
    .sp-cat-status-pill--upcoming { background: #dbeafe; color: #1d4ed8; }
    .sp-cat-status-pill--coming_soon { background: #f1f5f9; color: #64748b; }
    .sp-cat-status-pill--enrolling { background: #ece4fd; color: #5e17eb; }
</style>
@endpush
