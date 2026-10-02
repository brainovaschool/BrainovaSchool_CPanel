{{--
    A 3-at-a-time carousel of Watch & Learn videos, all one orientation —
    unlike the homepage's Home Videos row, every card here shares the
    same aspect ratio (landscape OR portrait, never mixed in one call),
    and each video can sit inside an admin-picked frame image.

    Usage: @include('frontend.partials.watch-learn-carousel', [
        'videos' => $data['landscape'], 'orientation' => 'landscape', 'heading' => 'Landscape videos',
    ])
--}}
@if (($videos ?? collect())->count())
<div class="bn-wl-zone bn-wl-zone--{{ $orientation }}">
    <h3 class="bn-wl-zone-heading">{{ $heading }}</h3>
    <div class="bn-wl-carousel">
        <button type="button" class="bn-wl-arrow bn-wl-arrow--prev" aria-label="Previous videos">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div class="bn-wl-viewport">
            <div class="bn-wl-track">
                @foreach ($videos as $video)
                    @php $embed = $video->resolveEmbed(); @endphp
                    <div class="bn-wl-card">
                        <div class="bn-wl-frame">
                            @if ($embed['type'] === 'file')
                                <video controls preload="metadata" playsinline src="{{ $embed['src'] }}"></video>
                            @else
                                <button type="button" class="bn-wl-facade" data-embed-src="{{ $embed['src'] }}" data-embed-type="{{ $embed['type'] }}"
                                    aria-label="Play video{{ $video->title ? ': ' . $video->title : '' }}"
                                    @if ($embed['thumb']) style="background-image:url('{{ $embed['thumb'] }}')" @endif>
                                    <span class="bn-wl-facade__play"><i class="fas fa-play"></i></span>
                                    @unless ($embed['thumb'])
                                        <span class="bn-wl-facade__platform">{{ ucfirst($embed['type']) }}</span>
                                    @endunless
                                </button>
                            @endif
                            @if ($video->template && $video->template->upload)
                                <img class="bn-wl-tile" src="{{ globalAsset($video->template->upload->path) }}" alt="" aria-hidden="true">
                            @endif
                        </div>
                        @if ($video->title)
                            <p class="bn-wl-caption">{{ $video->title }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        <button type="button" class="bn-wl-arrow bn-wl-arrow--next" aria-label="More videos">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>

@once
    @push('css')
    <style>
        .bn-wl-zone{margin-bottom:48px;}
        .bn-wl-zone-heading{text-align:center;margin-bottom:22px;}
        .bn-wl-carousel{position:relative;display:flex;align-items:center;gap:8px;max-width:1200px;margin:0 auto;padding:0 20px;}
        .bn-wl-viewport{overflow:hidden;flex:1 1 auto;}
        .bn-wl-track{display:flex;gap:18px;transition:transform .35s ease;}
        .bn-wl-zone--landscape .bn-wl-card{flex:0 0 calc((100% - 2 * 18px) / 3);}
        .bn-wl-zone--portrait .bn-wl-card{flex:0 0 calc((100% - 4 * 18px) / 5);}
        .bn-wl-zone--landscape .bn-wl-frame{aspect-ratio:16/9;}
        .bn-wl-zone--portrait .bn-wl-frame{aspect-ratio:9/16;}
        .bn-wl-frame{position:relative;border-radius:14px;overflow:hidden;background:#0f1b3d;box-shadow:0 6px 20px rgba(15,27,61,.12);}
        .bn-wl-frame iframe,.bn-wl-frame video{width:100%;height:100%;border:0;display:block;object-fit:contain;background:#0f1b3d;}
        .bn-wl-tile{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;pointer-events:none;}
        .bn-wl-facade{position:absolute;inset:0;width:100%;height:100%;border:0;cursor:pointer;background-color:#0f1b3d;background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;}
        .bn-wl-facade__play{width:52px;height:52px;border-radius:50%;background:rgba(255,255,255,.92);display:flex;align-items:center;justify-content:center;color:#0097b2;font-size:18px;transition:transform .15s ease;}
        .bn-wl-facade:hover .bn-wl-facade__play{transform:scale(1.08);}
        .bn-wl-facade__platform{position:absolute;bottom:10px;left:12px;color:#fff;font-size:.7rem;letter-spacing:.06em;text-transform:uppercase;font-weight:600;opacity:.85;}
        .bn-wl-caption{margin:10px 0 0;font-size:.86rem;color:#334155;text-align:center;}
        .bn-wl-arrow{flex:0 0 auto;width:44px;height:44px;border-radius:50%;border:1px solid #d8ecf0;background:#fff;color:#0097b2;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 10px rgba(15,27,61,.08);transition:opacity .15s ease,transform .15s ease;}
        .bn-wl-arrow:hover{transform:scale(1.06);}
        .bn-wl-arrow[disabled]{opacity:.35;cursor:default;pointer-events:none;}
        @media (max-width:992px){
            .bn-wl-zone--landscape .bn-wl-card{flex-basis:calc((100% - 18px) / 2);}
            .bn-wl-zone--portrait .bn-wl-card{flex-basis:calc((100% - 2 * 18px) / 3);}
        }
        @media (max-width:576px){
            .bn-wl-zone--landscape .bn-wl-card{flex-basis:100%;}
            .bn-wl-zone--portrait .bn-wl-card{flex-basis:calc((100% - 18px) / 2);}
            .bn-wl-arrow{width:36px;height:36px;}
        }
    </style>
    @endpush

    @push('script')
    <script>
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.bn-wl-facade');
            if (!btn) return;
            var src = btn.getAttribute('data-embed-src');
            var type = btn.getAttribute('data-embed-type');
            var frame = btn.parentElement;
            var tile = frame.querySelector('.bn-wl-tile');
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
            btn.remove();
            frame.insertBefore(el, tile || null);
        });

        document.querySelectorAll('.bn-wl-carousel').forEach(function (carousel) {
            var viewport = carousel.querySelector('.bn-wl-viewport');
            var track    = carousel.querySelector('.bn-wl-track');
            var prevBtn  = carousel.querySelector('.bn-wl-arrow--prev');
            var nextBtn  = carousel.querySelector('.bn-wl-arrow--next');
            var cards    = Array.prototype.slice.call(track.children);
            var index    = 0;

            function perView() {
                return cards.length ? Math.max(1, Math.round(viewport.clientWidth / cards[0].getBoundingClientRect().width)) : 1;
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

            prevBtn.addEventListener('click', function () { index = Math.max(0, index - 1); update(); });
            nextBtn.addEventListener('click', function () { index = Math.min(maxIndex(), index + 1); update(); });
            window.addEventListener('resize', function () { index = Math.min(index, maxIndex()); update(); });

            update();
        });
    </script>
    @endpush
@endonce
@endif
