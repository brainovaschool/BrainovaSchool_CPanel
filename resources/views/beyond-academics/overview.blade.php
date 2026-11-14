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
                        <li class="breadcrumb-item">Beyond Academics</li>
                        <li class="breadcrumb-item">Overview</li>
                    </ol>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <a href="{{ route('sections.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-pen"></i> Edit this banner in Website Setup
                    </a>
                </div>
            </div>
        </div>

        <div class="ba-hero">
            <span class="ba-hero-badge">Overview</span>
            <div class="ba-hero-grid">
                <div class="ba-hero-copy">
                    <h2>{{ $data['hero']->name ?? 'Learn New Skills, Explore New Possibilities' }}</h2>
                    <p>{{ $data['hero']->description ?? 'Short courses • Practical Skills • Lifelong Learning' }}</p>
                </div>
                @if ($data['hero']?->upload)
                    <div class="ba-hero-art">
                        <img src="{{ globalAsset($data['hero']->upload->path) }}" alt="">
                    </div>
                @endif
            </div>
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
                                @if ($program->badge)
                                    <span class="ba-course-badge">{{ $program->badge }}</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Each category just opens the public Our Programs page already
             filtered to it - that page already shows every course in the
             category properly (images, pagination, full cards), so there's
             no reason to rebuild a smaller version of it here. --}}
        <div class="ba-category-grid">
            @foreach ($data['categories'] as $cat)
                <a href="{{ route('frontend.courses', ['category' => $cat->slug]) }}" target="_blank" rel="noopener" class="ba-category-btn">
                    @if ($cat->image)
                        <span class="ba-category-thumb" style="background-image: url('{{ $cat->image }}');"></span>
                    @else
                        <span class="ba-category-thumb ba-category-thumb--empty"><i class="las la-graduation-cap"></i></span>
                    @endif
                    <span class="ba-category-label">{{ $cat->name }}</span>
                </a>
            @endforeach
        </div>

    </div>
@endsection

@push('css')
<style>
    .ba-hero {
        position: relative;
        background: linear-gradient(120deg, #efe7ff 0%, #e4ecff 60%, #eef2ff 100%);
        border-radius: 20px;
        padding: 28px 32px 32px;
        margin-bottom: 20px;
        overflow: hidden;
    }
    .ba-hero-badge {
        display: inline-block;
        background: #5e17eb;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        padding: 6px 16px;
        border-radius: 999px;
        margin-bottom: 16px;
    }
    .ba-hero-grid {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        flex-wrap: wrap;
    }
    .ba-hero-copy h2 {
        font-size: 28px;
        font-weight: 800;
        color: #1f2430;
        margin: 0 0 10px;
        line-height: 1.25;
    }
    .ba-hero-copy p {
        font-size: 15px;
        color: #475065;
        margin: 0;
    }
    .ba-hero-art {
        flex: 0 0 auto;
    }
    .ba-hero-art img {
        max-height: 160px;
        width: auto;
        display: block;
    }

    .ba-featured {
        background: var(--ot-bg-table-card, #fff);
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        border-radius: 16px;
        padding: 20px 22px;
        margin-bottom: 20px;
    }
    .ba-featured-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .ba-featured-head h4 {
        margin: 0;
        font-size: 17px;
        font-weight: 700;
        color: #1f2430;
    }
    .ba-featured-head a {
        font-size: 13px;
        font-weight: 600;
        color: #5e17eb;
        text-decoration: none;
    }
    .ba-course-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
    }
    .ba-course-card {
        display: block;
        border: 1px solid var(--ot-border-basic-table, #edeef1);
        border-radius: 14px;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        transition: box-shadow .12s ease, transform .12s ease;
    }
    .ba-course-card:hover {
        box-shadow: 0 6px 18px rgba(30, 30, 60, .08);
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
        padding: 12px 14px 14px;
    }
    .ba-course-card-body h5 {
        font-size: 14px;
        font-weight: 700;
        margin: 0 0 6px;
        color: #1f2430;
    }
    .ba-course-meta {
        font-size: 12px;
        color: #8b93a3;
        margin: 0 0 8px;
    }
    .ba-course-badge {
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        background: #e1f6fa;
        color: #007585;
        padding: 3px 10px;
        border-radius: 999px;
    }

    .ba-category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
        gap: 16px;
    }
    .ba-category-btn {
        width: 100%;
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        background: var(--ot-bg-table-card, #fff);
        border-radius: 16px;
        padding: 14px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        transition: transform .12s ease;
    }
    .ba-category-btn:hover {
        transform: translateY(-2px);
    }
    .ba-category-thumb {
        width: 100%;
        height: 80px;
        border-radius: 10px;
        background-size: cover;
        background-position: center;
        background-color: #eef1f6;
    }
    .ba-category-thumb--empty {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        color: #8b93a3;
    }
    .ba-category-label {
        font-size: 14px;
        font-weight: 700;
        color: #1f2430;
        text-align: left;
    }

    @media (max-width: 575px) {
        .ba-hero-art { display: none; }
    }
</style>
@endpush
