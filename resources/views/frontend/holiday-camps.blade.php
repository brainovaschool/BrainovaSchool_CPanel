@extends('frontend.master')

@section('title')
    Summer camp
@endsection

@push('css')
<link rel="stylesheet" href="{{ global_asset('frontend') }}/css/frontend-courses.css">
@endpush

@section('main')
@php
    $hero = $data['hero'] ?? [];
    $camps = $camps ?? $data['camps'] ?? [];
    if (empty($camps) && isset($paginator) && $paginator->count() > 0) {
        $camps = $paginator->items();
    }
    $faqs = $data['faqs'] ?? [];
    $trust = $data['trust'] ?? [];
    $activeCategory = $data['active_category'] ?? 'all';
    $catalogTotal = $data['catalog_total'] ?? count($camps);
    $categories = $data['categories'] ?? [];
    if (empty($categories)) {
        $categories = [
            ['slug' => 'all', 'label' => 'All camps'],
            ['slug' => 'digital', 'label' => 'Digital skills'],
            ['slug' => 'creative', 'label' => 'Creative crafts'],
            ['slug' => 'science', 'label' => 'Science'],
            ['slug' => 'culinary', 'label' => 'Baking'],
            ['slug' => 'arts', 'label' => 'Art'],
            ['slug' => 'tech', 'label' => 'Coding'],
        ];
    }
@endphp

<div class="fe-courses-page">
    <div class="fe-courses-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-10 col-xl-8">
                    <h1>{{ $hero['title'] ?? 'Summer camp programs' }}</h1>
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
            <div class="fe-filter-pills" role="tablist" aria-label="Camp categories">
                @foreach ($categories as $cat)
                    @php
                        $slug = $cat['slug'] ?? 'all';
                        $isActive = $activeCategory === $slug;
                        $filterUrl = $slug === 'all'
                            ? route('frontend.holiday-camps')
                            : route('frontend.holiday-camps', ['category' => $slug]);
                    @endphp
                    <a href="{{ $filterUrl }}"
                        class="fe-filter-pill {{ $isActive ? 'fe-is-active' : '' }}"
                        role="tab"
                        aria-selected="{{ $isActive ? 'true' : 'false' }}">{{ $cat['label'] ?? $slug }}</a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="fe-courses-grid section_padding pt-4">
        <div class="container">
            <div class="fe-courses-toolbar">
                <p class="fe-courses-count mb-0">
                    @if(isset($paginator) && $paginator->total() > 0)
                        Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }} camps
                        @if($activeCategory !== 'all')
                            <span class="fe-courses-count-filter">({{ $catalogTotal }} total in catalog)</span>
                        @endif
                    @else
                        No camps match this filter.
                    @endif
                </p>
                <a href="{{ route('frontend.contact') }}" class="fe-btn-pill fe-btn-ghost fe-mini d-none d-sm-inline-flex">Ask a question</a>
            </div>

            @if(count($camps) > 0)
            <div class="row fe-courses-row" id="feCampsGrid">
                @foreach ($camps as $camp)
                    @php
                        $accent = $camp['accent'] ?? 'indigo';
                    @endphp
                    <div class="col-xl-4 col-lg-4 col-md-6 mb-4 fe-course-wrap">
                        <article class="fe-course-card">
                            <div class="fe-course-card-media fe-accent-{{ $accent }} {{ empty($camp['image']) ? 'fe-course-card-media--placeholder' : '' }}">
                                @if(!empty($camp['image']))
                                    <img src="{{ $camp['image'] }}" alt="{{ $camp['title'] ?? '' }}">
                                @endif
                                <span class="fe-course-card__badge">{{ $camp['badge'] ?? 'Camp' }}</span>
                            </div>
                            <div class="fe-course-card-body">
                                <h3 class="fe-course-card-title">{{ $camp['title'] ?? '' }}</h3>
                                <p class="fe-course-card-desc">{{ \Illuminate\Support\Str::limit(strip_tags($camp['description'] ?? ''), 120) }}</p>

                                @if(!empty($camp['price']))
                                    <div class="fe-course-card-price-wrap" aria-label="Camp fee">
                                        <span class="fe-course-card-price-label">Fee</span>
                                        <div class="fe-course-card-price">{{ $camp['price'] }}</div>
                                    </div>
                                @endif

                                <div class="fe-course-meta">
                                    <span><i class="fas fa-user-graduate"></i>{{ $camp['age_range'] ?? '' }}</span>
                                    <span><i class="fas fa-school"></i>{{ $camp['grade'] ?? '' }}</span>
                                    <span><i class="fas fa-book-open"></i>{{ $camp['lessons'] ?? '' }}</span>
                                    <span><i class="far fa-clock"></i>{{ $camp['duration'] ?? '' }}</span>
                                </div>

                                @if(!empty($camp['enrolled']))
                                    <div class="fe-course-enrolled">
                                        <i class="fas fa-users me-1"></i>{{ $camp['enrolled'] }}
                                    </div>
                                @endif

                                <div class="fe-course-actions">
                                    @if(!empty($camp['slug']))
                                        <a href="{{ route('frontend.holiday-camp-detail', $camp['slug']) }}" class="fe-btn-pill fe-btn-primary fe-mini">View camp</a>
                                    @endif
                                    <a href="{{ route('frontend.contact') }}" class="fe-btn-pill fe-btn-ghost fe-mini">Enquire</a>
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>

            @if(isset($paginator))
                @include('frontend.partials.courses-pagination', ['paginator' => $paginator])
            @endif
            @else
            <p class="fe-courses-empty fe-is-visible">No camps match this filter yet—try another category or <a href="{{ route('frontend.contact') }}">contact us</a>.</p>
            @endif
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
