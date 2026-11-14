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

        <div class="ba-category-grid">
            @foreach ($data['categories'] as $i => $cat)
                <div class="ba-category">
                    <button type="button" class="ba-category-btn" style="background: {{ $cat['color'] }};" data-ba-toggle="ba-panel-{{ $i }}">
                        <i class="las {{ $cat['icon'] }}"></i>
                        <span>{{ $cat['label'] }}</span>
                    </button>
                    <div class="ba-category-panel" id="ba-panel-{{ $i }}" hidden>
                        <p class="text-secondary mb-0" style="font-size: 13px;">Course content for "{{ $cat['label'] }}" is coming soon.</p>
                    </div>
                </div>
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

    .ba-category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
        gap: 16px;
    }
    .ba-category-btn {
        width: 100%;
        border: none;
        border-radius: 16px;
        padding: 20px 16px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        cursor: pointer;
        transition: transform .12s ease;
    }
    .ba-category-btn:hover {
        transform: translateY(-2px);
    }
    .ba-category-btn i {
        font-size: 24px;
        color: #334058;
    }
    .ba-category-btn span {
        font-size: 14px;
        font-weight: 700;
        color: #1f2430;
        text-align: left;
    }
    .ba-category-panel {
        background: var(--ot-bg-table-card, #fff);
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        border-top: none;
        border-radius: 0 0 12px 12px;
        margin-top: -6px;
        padding: 14px 16px 16px;
    }

    @media (max-width: 575px) {
        .ba-hero-art { display: none; }
    }
</style>
@endpush

@push('script')
<script>
(function () {
    document.querySelectorAll('[data-ba-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var panel = document.getElementById(btn.getAttribute('data-ba-toggle'));
            if (panel) {
                panel.hidden = !panel.hidden;
            }
        });
    });
})();
</script>
@endpush
