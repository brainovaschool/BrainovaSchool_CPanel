@extends('frontend.master')

@section('title')
    {{ ___('frontend.Courses') }}
@endsection

@push('css')
<link rel="stylesheet" href="{{ global_asset('frontend') }}/css/frontend-courses.css">
@endpush

@section('main')
@php
    $hero = $data['hero'] ?? [];
    $courses = $data['courses'] ?? [];
    $faqs = $data['faqs'] ?? [];
    $trust = $data['trust'] ?? [];
    $categories = $data['categories'] ?? [];
    if (empty($categories)) {
        $categories = [
            ['slug' => 'all', 'label' => 'All programs'],
            ['slug' => 'stem', 'label' => 'STEM & computing'],
            ['slug' => 'math', 'label' => 'Mathematics'],
            ['slug' => 'language', 'label' => 'Languages'],
            ['slug' => 'skills', 'label' => 'Life skills'],
        ];
    }
@endphp

<div class="fe-courses-page">
    <div class="fe-courses-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-10 col-xl-8">
                    <h1>{{ $hero['title'] ?? 'Explore our courses' }}</h1>
                    <p class="fe-courses-hero-lead">{{ $hero['subtitle'] ?? '' }}</p>
                    <div class="fe-courses-hero-cta">
                        @if(!empty($hero['primary_cta']['route']))
                            <a href="{{ route($hero['primary_cta']['route']) }}" class="fe-btn-pill fe-btn-primary">
                                {{ $hero['primary_cta']['label'] ?? 'Contact us' }}
                            </a>
                        @endif
                        @if(!empty($hero['secondary_cta']['route']))
                            <a href="{{ route($hero['secondary_cta']['route']) }}" class="fe-btn-pill fe-btn-ghost">
                                {{ $hero['secondary_cta']['label'] ?? 'Admission' }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="fe-courses-filters section_padding pt-4 pb-0">
        <div class="container">
            <div class="fe-filter-pills" id="feCourseFilters" role="tablist" aria-label="Course categories">
                @foreach ($categories as $idx => $cat)
                    @php $slug = $cat['slug'] ?? 'all'; @endphp
                    <button type="button"
                        class="fe-filter-pill {{ $idx === 0 ? 'fe-is-active' : '' }}"
                        data-fe-filter="{{ $slug }}"
                        role="tab"
                        aria-selected="{{ $idx === 0 ? 'true' : 'false' }}">{{ $cat['label'] ?? $slug }}</button>
                @endforeach
            </div>
        </div>
    </section>

    <section class="fe-courses-grid section_padding pt-4">
        <div class="container">
            <div class="fe-courses-toolbar">
                <p class="fe-courses-count mb-0"><span id="feCoursesVisible">{{ count($courses) }}</span> programs shown</p>
                <a href="{{ route('frontend.contact') }}" class="fe-btn-pill fe-btn-ghost fe-mini d-none d-sm-inline-flex">Ask a question</a>
            </div>

            <div class="row fe-courses-row" id="feCoursesGrid">
                @foreach ($courses as $course)
                    @php
                        $cat = $course['category'] ?? '';
                        $accent = $course['accent'] ?? 'indigo';
                    @endphp
                    <div class="col-xl-4 col-lg-4 col-md-6 mb-4 fe-course-wrap" data-fe-course-category="{{ $cat }}">
                        <article class="fe-course-card">
                            <div class="fe-course-card-media fe-accent-{{ $accent }} {{ empty($course['image']) ? 'fe-course-card-media--placeholder' : '' }}">
                                @if(!empty($course['image']))
                                    <img src="{{ $course['image'] }}" alt="{{ $course['title'] ?? '' }}">
                                @endif
                                <span class="fe-course-card__badge">{{ $course['badge'] ?? 'Course' }}</span>
                            </div>
                            <div class="fe-course-card-body">
                                <h3 class="fe-course-card-title">{{ $course['title'] ?? '' }}</h3>
                                <p class="fe-course-card-desc">{{ \Illuminate\Support\Str::limit(strip_tags($course['description'] ?? ''), 120) }}</p>

                                @if(!empty($course['price']))
                                    <div class="fe-course-card-price-wrap" aria-label="Course fee">
                                        <span class="fe-course-card-price-label">Fee</span>
                                        <div class="fe-course-card-price">{{ $course['price'] }}</div>
                                    </div>
                                @endif

                                <div class="fe-course-meta">
                                    <span><i class="fas fa-user-graduate"></i>{{ $course['age_range'] ?? '' }}</span>
                                    <span><i class="fas fa-school"></i>{{ $course['grade'] ?? '' }}</span>
                                    <span><i class="fas fa-book-open"></i>{{ $course['lessons'] ?? '' }}</span>
                                    <span><i class="far fa-clock"></i>{{ $course['duration'] ?? '' }}</span>
                                </div>

                                @if(!empty($course['enrolled']))
                                    <div class="fe-course-enrolled">
                                        <i class="fas fa-users me-1"></i>{{ $course['enrolled'] }}
                                    </div>
                                @endif

                                <div class="fe-course-actions">
                                    @if(!empty($course['slug']))
                                        <a href="{{ route('frontend.course-detail', $course['slug']) }}" class="fe-btn-pill fe-btn-primary fe-mini">View course</a>
                                    @endif
                                    <a href="{{ route('frontend.contact') }}" class="fe-btn-pill fe-btn-ghost fe-mini">Enquire</a>
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>

            <p class="fe-courses-empty" id="feCoursesEmpty">No programs match this filter yet—try another category or contact us for custom options.</p>
        </div>
    </section>

    @if(!empty($trust['headline']))
        <section class="fe-courses-trust">
            <div class="container">
                <h2>{{ $trust['headline'] }}</h2>
                @if(!empty($trust['body']))
                    <p class="mb-0">{{ $trust['body'] }}</p>
                @endif
            </div>
        </section>
    @endif

    @if(count($faqs))
        <section class="fe-courses-faq">
            <div class="container col-lg-10 col-xl-8 px-lg-0">
                <h2>Frequently asked questions</h2>
                <div id="feFaqList">
                    @foreach ($faqs as $faq)
                        <div class="fe-faq-item">
                            <button type="button" class="fe-faq-q">
                                {{ $faq['q'] ?? '' }}
                                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                            </button>
                            <div class="fe-faq-a">
                                <div class="fe-faq-a-inner">{{ $faq['a'] ?? '' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var grid = document.getElementById('feCoursesGrid');
    var filters = document.getElementById('feCourseFilters');
    var countEl = document.getElementById('feCoursesVisible');
    var emptyEl = document.getElementById('feCoursesEmpty');

    if (!grid || !filters) {
        return;
    }

    var categoryAliases = {
        maths: 'math',
        mathematics: 'math',
        english: 'language',
        languages: 'language',
        'stem & computing': 'stem',
        'life skills': 'skills'
    };

    function normalizeCategory(value) {
        var slug = String(value || '').toLowerCase().trim();
        return categoryAliases[slug] || slug;
    }

    function getCards() {
        return grid.querySelectorAll('.fe-course-wrap');
    }

    function applyFilter(slug) {
        var filter = normalizeCategory(slug || 'all');
        if (!filter) {
            filter = 'all';
        }

        var visible = 0;
        getCards().forEach(function (card) {
            var cat = normalizeCategory(card.getAttribute('data-fe-course-category'));
            var show = filter === 'all' || cat === filter;
            card.classList.toggle('d-none', !show);
            if (show) {
                visible++;
            }
        });

        if (countEl) {
            countEl.textContent = String(visible);
        }
        if (emptyEl) {
            emptyEl.classList.toggle('fe-is-visible', visible === 0);
        }
    }

    filters.addEventListener('click', function (event) {
        var pill = event.target.closest('[data-fe-filter]');
        if (!pill) {
            return;
        }

        var slug = pill.getAttribute('data-fe-filter') || 'all';
        filters.querySelectorAll('[data-fe-filter]').forEach(function (btn) {
            btn.classList.remove('fe-is-active');
            btn.setAttribute('aria-selected', 'false');
        });
        pill.classList.add('fe-is-active');
        pill.setAttribute('aria-selected', 'true');
        applyFilter(slug);
    });

    applyFilter('all');

    document.querySelectorAll('.fe-faq-q').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var item = btn.closest('.fe-faq-item');
            var panel = item.querySelector('.fe-faq-a');
            var inner = panel.querySelector('.fe-faq-a-inner');
            var open = !item.classList.contains('fe-open');
            document.querySelectorAll('.fe-faq-item.fe-open').forEach(function (o) {
                if (o === item) {
                    return;
                }
                o.classList.remove('fe-open');
                o.querySelector('.fe-faq-a').style.maxHeight = '0';
            });
            if (open) {
                item.classList.add('fe-open');
                panel.style.maxHeight = inner.scrollHeight + 24 + 'px';
            } else {
                item.classList.remove('fe-open');
                panel.style.maxHeight = '0';
            }
        });
    });
});
</script>
@endpush
