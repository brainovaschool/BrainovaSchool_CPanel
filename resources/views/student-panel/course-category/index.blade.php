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
                @if (!$cat)
                    <p class="sp-cat-hero-missing">No "{{ $data['categoryTitle'] }}" category found.</p>
                @endif
            </div>

            {{-- Featured Courses: a live reflection of every active Program
                 in this category, exactly like the admin/teacher page's
                 own Featured Courses - a catalog to browse, not limited to
                 what this student happens to be enrolled in already. --}}
            <div class="sp-cat-featured">
                <div class="sp-cat-featured-head">
                    <h4>Featured Courses</h4>
                </div>
                @if ($data['featured']->isEmpty())
                    <p class="text-secondary mb-0" style="font-size: 13px;">No courses added yet in this category.</p>
                @else
                    <div class="sp-cat-course-grid">
                        @foreach ($data['featured'] as $program)
                            <a href="{{ route('frontend.course-detail', $program->slug) }}" target="_blank" rel="noopener" class="sp-cat-course-card">
                                <div class="sp-cat-course-card-media">
                                    @if ($program->image)
                                        <img src="{{ $program->image }}" alt="{{ $program->title }}">
                                    @endif
                                </div>
                                <div class="sp-cat-course-card-body">
                                    <h5>{{ $program->title }}</h5>
                                    <p class="sp-cat-course-meta">
                                        @if ($program->age_range)<span>{{ $program->age_range }}</span>@endif
                                        @if ($program->age_range && $program->lessons) <span>&bull;</span> @endif
                                        @if ($program->lessons)<span>{{ $program->lessons }}</span>@endif
                                    </p>
                                    <div class="sp-cat-course-pills">
                                        @if ($program->badge)
                                            <span class="sp-cat-course-badge">{{ $program->badge }}</span>
                                        @endif
                                        @if ($program->status_label_text)
                                            <span class="sp-cat-status-pill sp-cat-status-pill--{{ $program->status_label }}">{{ $program->status_label_text }}</span>
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
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap">
<style>
    #overview, #course-details, #progress-report { scroll-margin-top: 100px; display: block; }
    #course-details, #progress-report { margin-top: 1.25rem; }
    #progress-report h4 { margin: 0 0 0.75rem; font-size: 1.15rem; font-weight: 700; color: #0f172a; }

    {{-- Same hero look as the admin/teacher category pages (resources/
         views/beyond-academics/overview.blade.php etc.), and the same
         real public-site heading font (Libre Baskerville, loaded above)
         rather than an invented color scheme. --}}
    .sp-cat-hero {
        background: #ffffff;
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        border-radius: 20px;
        padding: 2rem 2.25rem;
        margin-bottom: 1.25rem;
    }
    .sp-cat-hero-badge {
        display: inline-block;
        background: #5e17eb;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.35rem 1rem;
        border-radius: 999px;
        margin-bottom: 1.25rem;
    }
    .sp-cat-hero-grid { display: flex; align-items: stretch; gap: 2.5rem; flex-wrap: wrap; flex: 1 1 auto; }
    .sp-cat-hero-eyebrow { font-size: 0.8rem; letter-spacing: 0.1em; text-transform: uppercase; font-weight: 700; color: #007585; margin: 0 0 0.6rem; }
    .sp-cat-hero-copy { flex: 1 1 auto; min-width: 0; }
    .sp-cat-hero-copy h2 { font-family: "Libre Baskerville", serif; font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 700; color: #0f2937; margin: 0 0 0.75rem; line-height: 1.25; }
    .sp-cat-hero-copy p { font-size: 1rem; color: #475569; margin: 0; max-width: 48rem; line-height: 1.6; }
    .sp-cat-hero-missing { font-size: 0.85rem; color: #8b93a3; margin: 0.75rem 0 0; }
    .sp-cat-hero-art { flex: 0 0 42%; max-width: 460px; width: 100%; min-height: 140px; }
    .sp-cat-hero-art img { display: block; width: 100%; height: 100%; object-fit: contain; border-radius: 14px; }
    .sp-cat-featured {
        background: #ffffff;
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        border-radius: 20px;
        padding: 1.25rem 1.5rem;
    }
    .sp-cat-featured-head h4 { margin: 0 0 0.9rem; font-size: 1.05rem; font-weight: 700; color: #0f172a; }
    .sp-cat-course-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
    .sp-cat-course-card {
        display: block; border: 1px solid var(--ot-border-basic-table, #edeef1); border-radius: 14px;
        overflow: hidden; text-decoration: none; color: inherit; transition: box-shadow .15s ease, transform .15s ease;
    }
    .sp-cat-course-card:hover { box-shadow: 0 10px 26px rgba(15, 23, 42, .08); transform: translateY(-2px); color: inherit; }
    .sp-cat-course-card-media { height: 110px; background: #eef1f6; display: flex; align-items: center; justify-content: center; }
    .sp-cat-course-card-media img { width: 100%; height: 100%; object-fit: cover; }
    .sp-cat-course-card-body { padding: 0.85rem 1rem 1rem; }
    .sp-cat-course-card-body h5 { font-size: 0.95rem; font-weight: 700; margin: 0 0 0.4rem; color: #0f172a; }
    .sp-cat-course-meta { font-size: 0.82rem; color: #8b93a3; margin: 0 0 0.5rem; display: flex; gap: 0.4rem; }
    .sp-cat-course-pills { display: flex; flex-wrap: wrap; gap: 0.4rem; }
    .sp-cat-course-badge { display: inline-block; font-size: 0.72rem; font-weight: 700; background: #e1f6fa; color: #007585; padding: 0.2rem 0.65rem; border-radius: 999px; }
    .sp-cat-status-pill { display: inline-block; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.65rem; border-radius: 999px; }
    .sp-cat-status-pill--ongoing { background: #dcfce7; color: #15803d; }
    .sp-cat-status-pill--upcoming { background: #dbeafe; color: #1d4ed8; }
    .sp-cat-status-pill--coming_soon { background: #f1f5f9; color: #64748b; }
    .sp-cat-status-pill--enrolling { background: #ece4fd; color: #5e17eb; }
</style>
@endpush
