@extends('frontend.master')
@section('title')
    {{ $data['page']->title }}
@endsection

@section('main')

    <div class="breadcrumb_area">
        <div class="container">
            <div class="breadcam_wrap text-center">
                <h3>{{ $data['page']->title }}</h3>
                <div class="custom_breadcam">
                    <a href="{{ url('/') }}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                    <a href="{{ route('frontend.knowledge-hub') }}" class="breadcrumb-item">Knowledge Hub</a>
                    <a href="#" class="breadcrumb-item">{{ $data['page']->title }}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="section_padding">
        <div class="container" style="max-width:860px;">
            @if ($data['topics']->isEmpty())
                <p class="text-center text-secondary">Nothing here yet — check back soon.</p>
            @else
                <div class="bn-kh-accordion">
                    @foreach ($data['topics'] as $i => $topic)
                        <div class="bn-kh-topic">
                            <button type="button" class="bn-kh-topic__head" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}">
                                <span>{{ $topic->title }}</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="bn-kh-topic__body" @if ($i !== 0) hidden @endif>
                                {!! nl2br(e($topic->explanation)) !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

@endsection

@push('css')
<style>
    .bn-kh-topic{border:1px solid #d8ecf0;border-radius:12px;margin-bottom:14px;overflow:hidden;}
    .bn-kh-topic__head{width:100%;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 20px;background:#fff;border:0;text-align:left;font-size:1rem;font-weight:600;color:#0f1b3d;cursor:pointer;}
    .bn-kh-topic__head i{transition:transform .15s ease;color:#0097b2;}
    .bn-kh-topic__head[aria-expanded="true"] i{transform:rotate(180deg);}
    .bn-kh-topic__body{padding:0 20px 18px;color:#475569;line-height:1.7;}
</style>
@endpush

@push('script')
<script>
    document.querySelectorAll('.bn-kh-topic__head').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var body = btn.nextElementSibling;
            var open = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', open ? 'false' : 'true');
            body.hidden = open;
        });
    });
</script>
@endpush
