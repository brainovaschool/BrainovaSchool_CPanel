{{--
    Renders a horizontal row of admin-added videos (see Website Setup ->
    Home Videos). Landscape and portrait cards sit in the same row, each
    sized to its own shape via CSS aspect-ratio, so nothing gets stretched
    or letterboxed.

    Nothing heavy loads up front: only a real <iframe>/<video> is ever
    autoplay isn't requested (or isn't supported — Instagram never
    autoplays), a thumbnail + play button sits in its place until clicked.

    Usage: @include('frontend.partials.home.videos', ['videos' => $videos])
--}}
@if (($videos ?? collect())->count())
<section class="bn-video-section">
    <div class="container">
        <p class="bn-eyebrow">See Brainova in action</p>
        <h2 class="bn-video-heading">Watch Brainova</h2>
    </div>
    <div class="bn-video-row">
        @foreach ($videos as $video)
            @php
                $embed = $video->resolveEmbed();
                $isPortrait = $video->orientation === 'portrait';
                $wantsAutoplay = $video->autoplay && $video->autoplaySupported();
                $autoplaySrc = $embed['src'];
                if ($wantsAutoplay && $embed['type'] !== 'file') {
                    $sep = (strpos($autoplaySrc, '?') !== false) ? '&' : '?';
                    $autoplaySrc .= match ($embed['type']) {
                        'youtube' => $sep . 'autoplay=1&mute=1&playsinline=1',
                        'facebook' => $sep . 'autoplay=true',
                        default => $sep . 'autoplay=1',
                    };
                }
            @endphp
            <div class="bn-video-card {{ $isPortrait ? 'bn-video-card--portrait' : 'bn-video-card--landscape' }}">
                <div class="bn-video-frame">
                    @if ($embed['type'] === 'file')
                        <video
                            {{ $wantsAutoplay ? 'autoplay muted playsinline loop' : 'controls' }}
                            preload="metadata" playsinline
                            src="{{ $embed['src'] }}"></video>
                    @elseif ($wantsAutoplay)
                        <iframe src="{{ $autoplaySrc }}" loading="lazy" allow="autoplay; encrypted-media; picture-in-picture"
                            allowfullscreen frameborder="0"></iframe>
                    @else
                        <button type="button" class="bn-video-facade" data-embed-src="{{ $embed['src'] }}" data-embed-type="{{ $embed['type'] }}"
                            aria-label="Play video{{ $video->title ? ': ' . $video->title : '' }}"
                            @if ($embed['thumb']) style="background-image:url('{{ $embed['thumb'] }}')" @endif>
                            <span class="bn-video-facade__play"><i class="fas fa-play"></i></span>
                            @unless ($embed['thumb'])
                                <span class="bn-video-facade__platform">{{ ucfirst($embed['type']) }}</span>
                            @endunless
                        </button>
                    @endif
                </div>
                @if ($video->title)
                    <p class="bn-video-caption">{{ $video->title }}</p>
                @endif
            </div>
        @endforeach
    </div>
</section>

@once
    @push('css')
    <style>
        .bn-video-section{padding:56px 0;}
        .bn-video-heading{margin-bottom:22px;}
        .bn-video-row{display:flex;gap:18px;overflow-x:auto;padding:4px 20px 16px;scroll-snap-type:x proximity;-webkit-overflow-scrolling:touch;}
        .bn-video-row::-webkit-scrollbar{height:6px;}
        .bn-video-row::-webkit-scrollbar-thumb{background:#d8ecf0;border-radius:6px;}
        .bn-video-card{flex:0 0 auto;scroll-snap-align:start;height:320px;}
        .bn-video-card--landscape .bn-video-frame{aspect-ratio:16/9;height:320px;width:auto;}
        .bn-video-card--portrait .bn-video-frame{aspect-ratio:9/16;height:320px;width:auto;}
        .bn-video-frame{position:relative;border-radius:14px;overflow:hidden;background:#0f1b3d;box-shadow:0 6px 20px rgba(15,27,61,.12);}
        .bn-video-frame iframe,.bn-video-frame video{width:100%;height:100%;border:0;display:block;object-fit:cover;}
        .bn-video-facade{position:absolute;inset:0;width:100%;height:100%;border:0;cursor:pointer;background-color:#0f1b3d;background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;}
        .bn-video-facade__play{width:58px;height:58px;border-radius:50%;background:rgba(255,255,255,.92);display:flex;align-items:center;justify-content:center;color:#0097b2;font-size:20px;transition:transform .15s ease;}
        .bn-video-facade:hover .bn-video-facade__play{transform:scale(1.08);}
        .bn-video-facade__platform{position:absolute;bottom:10px;left:12px;color:#fff;font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;font-weight:600;opacity:.85;}
        .bn-video-caption{margin:10px 0 0;font-size:.88rem;color:#334155;max-width:320px;}
        @media (max-width:576px){
            .bn-video-card,.bn-video-card--landscape .bn-video-frame,.bn-video-card--portrait .bn-video-frame{height:220px;}
        }
    </style>
    @endpush

    @push('script')
    <script>
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.bn-video-facade');
            if (!btn) return;
            var src = btn.getAttribute('data-embed-src');
            var type = btn.getAttribute('data-embed-type');
            var frame = btn.parentElement;
            var el;
            if (type === 'file') {
                el = document.createElement('video');
                el.src = src;
                el.controls = true;
                el.autoplay = true;
                el.playsInline = true;
            } else {
                el = document.createElement('iframe');
                el.src = src + (src.indexOf('?') !== -1 ? '&' : '?') + 'autoplay=1' + (type === 'youtube' ? '&mute=0' : '');
                el.setAttribute('allow', 'autoplay; encrypted-media; picture-in-picture');
                el.setAttribute('allowfullscreen', '');
                el.setAttribute('frameborder', '0');
            }
            frame.innerHTML = '';
            frame.appendChild(el);
        });
    </script>
    @endpush
@endonce
@endif
