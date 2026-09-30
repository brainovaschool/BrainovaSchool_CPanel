{{--
    Renders a paginated carousel of admin-added videos (see Website Setup
    -> Home Videos) — 3 visible at a time on desktop, arrows to page
    through the rest. Every card is the same size (16:9) regardless of
    the video's own shape, so exactly 3 always fit cleanly; a portrait
    video is shown pillarboxed (letterboxed left/right) inside its slot
    rather than stretched or given a differently-sized card.

    Nothing heavy loads up front: a real <iframe>/<video> is only added
    when autoplay is requested (and supported — Instagram never
    autoplays); otherwise a thumbnail + play button sits in its place
    until clicked.

    Usage: @include('frontend.partials.home.videos', ['videos' => $videos])
--}}
@if (($videos ?? collect())->count())
<section class="bn-video-section">
    <div class="container">
        <div class="bn-video-head">
            <p class="bn-eyebrow">See Brainova in action</p>
            <h2 class="bn-video-heading">Watch Brainova</h2>
        </div>
    </div>
    <div class="bn-video-carousel">
        <button type="button" class="bn-video-arrow bn-video-arrow--prev" aria-label="Previous videos">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div class="bn-video-viewport">
            <div class="bn-video-track">
                @foreach ($videos as $video)
                    @php
                        $embed = $video->resolveEmbed();
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
                    <div class="bn-video-card">
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
        </div>
        <button type="button" class="bn-video-arrow bn-video-arrow--next" aria-label="More videos">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</section>

@once
    @push('css')
    <style>
        .bn-video-section{padding:56px 0;}
        .bn-video-head{text-align:center;}
        .bn-video-heading{margin-bottom:22px;}
        .bn-video-carousel{position:relative;display:flex;align-items:center;gap:8px;max-width:1200px;margin:0 auto;padding:0 20px;}
        .bn-video-viewport{overflow:hidden;flex:1 1 auto;}
        .bn-video-track{display:flex;gap:18px;transition:transform .35s ease;}
        .bn-video-card{flex:0 0 calc((100% - 2 * 18px) / 3);}
        .bn-video-frame{position:relative;aspect-ratio:16/9;border-radius:14px;overflow:hidden;background:#0f1b3d;box-shadow:0 6px 20px rgba(15,27,61,.12);}
        .bn-video-frame iframe,.bn-video-frame video{width:100%;height:100%;border:0;display:block;object-fit:contain;background:#0f1b3d;}
        .bn-video-facade{position:absolute;inset:0;width:100%;height:100%;border:0;cursor:pointer;background-color:#0f1b3d;background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;}
        .bn-video-facade__play{width:58px;height:58px;border-radius:50%;background:rgba(255,255,255,.92);display:flex;align-items:center;justify-content:center;color:#0097b2;font-size:20px;transition:transform .15s ease;}
        .bn-video-facade:hover .bn-video-facade__play{transform:scale(1.08);}
        .bn-video-facade__platform{position:absolute;bottom:10px;left:12px;color:#fff;font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;font-weight:600;opacity:.85;}
        .bn-video-caption{margin:10px 0 0;font-size:.88rem;color:#334155;text-align:center;}
        .bn-video-arrow{flex:0 0 auto;width:44px;height:44px;border-radius:50%;border:1px solid #d8ecf0;background:#fff;color:#0097b2;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 10px rgba(15,27,61,.08);transition:opacity .15s ease,transform .15s ease;}
        .bn-video-arrow:hover{transform:scale(1.06);}
        .bn-video-arrow[disabled]{opacity:.35;cursor:default;pointer-events:none;}
        @media (max-width:992px){
            .bn-video-card{flex-basis:calc((100% - 18px) / 2);}
        }
        @media (max-width:576px){
            .bn-video-card{flex-basis:100%;}
            .bn-video-arrow{width:36px;height:36px;}
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

        document.querySelectorAll('.bn-video-carousel').forEach(function (carousel) {
            var viewport = carousel.querySelector('.bn-video-viewport');
            var track    = carousel.querySelector('.bn-video-track');
            var prevBtn  = carousel.querySelector('.bn-video-arrow--prev');
            var nextBtn  = carousel.querySelector('.bn-video-arrow--next');
            var cards    = Array.prototype.slice.call(track.children);
            var index    = 0;

            function perView() {
                var w = viewport.clientWidth;
                if (w < 576) return 1;
                if (w < 992) return 2;
                return 3;
            }

            function maxIndex() {
                return Math.max(0, cards.length - perView());
            }

            function update() {
                var step = cards.length ? cards[0].getBoundingClientRect().width + 18 : 0;
                track.style.transform = 'translateX(-' + (index * step) + 'px)';
                prevBtn.disabled = index <= 0;
                nextBtn.disabled = index >= maxIndex();
            }

            prevBtn.addEventListener('click', function () {
                index = Math.max(0, index - 1);
                update();
            });
            nextBtn.addEventListener('click', function () {
                index = Math.min(maxIndex(), index + 1);
                update();
            });
            window.addEventListener('resize', function () {
                index = Math.min(index, maxIndex());
                update();
            });

            update();
        });
    </script>
    @endpush
@endonce
@endif
