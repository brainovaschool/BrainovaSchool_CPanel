@php $embed = $video->resolveEmbed(); @endphp
@if ($embed['type'] === 'file')
    <video controls preload="metadata" playsinline style="max-width:100%;border-radius:8px;" src="{{ $embed['src'] }}"></video>
@else
    <div style="position:relative;padding-top:56.25%;">
        <iframe src="{{ $embed['src'] }}" loading="lazy" allow="autoplay; encrypted-media; picture-in-picture"
            allowfullscreen frameborder="0" style="position:absolute;top:0;left:0;width:100%;height:100%;border-radius:8px;"></iframe>
    </div>
@endif
