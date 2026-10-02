<?php

namespace App\Traits;

/** Shared by any model that stores a single admin-pasted video link
 *  (HomeVideo, WatchLearnVideo) — turns it into something the frontend
 *  can actually render without needing to know the platform itself.
 *  Requires the using model to expose: video_url, and optionally
 *  upload_id/upload (an uploaded file always wins over a link). */
trait ResolvesVideoEmbed
{
    /** True only for platforms whose embed genuinely supports autoplay.
     *  Instagram's embed widget has no autoplay parameter at all — the
     *  admin checkbox is ignored for it rather than silently failing. */
    public function autoplaySupported(): bool
    {
        return $this->resolveEmbed()['type'] !== 'instagram';
    }

    public function resolveEmbed(): array
    {
        if (!empty($this->upload_id) && method_exists($this, 'upload') && $this->upload) {
            return ['type' => 'file', 'src' => globalAsset($this->upload->path), 'thumb' => null];
        }

        $url  = trim((string) $this->video_url);
        $host = parse_url($url, PHP_URL_HOST) ?: '';

        // YouTube — the only platform with a reliable public thumbnail,
        // so it's the only one shown as a real image before it's played.
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/i', $url, $m)) {
            $id = $m[1];
            return [
                'type'  => 'youtube',
                'src'   => "https://www.youtube-nocookie.com/embed/{$id}?rel=0",
                'thumb' => "https://img.youtube.com/vi/{$id}/hqdefault.jpg",
            ];
        }

        if (stripos($host, 'facebook.com') !== false || stripos($host, 'fb.watch') !== false) {
            return [
                'type'  => 'facebook',
                'src'   => 'https://www.facebook.com/plugins/video.php?href=' . urlencode($url) . '&show_text=false',
                'thumb' => null,
            ];
        }

        if (stripos($host, 'instagram.com') !== false) {
            // Public post/reel embeds work without any API key by just
            // appending /embed to the post's own URL.
            if (preg_match('#instagram\.com/(p|reel|tv)/([A-Za-z0-9_-]+)#i', $url, $m)) {
                return [
                    'type'  => 'instagram',
                    'src'   => "https://www.instagram.com/{$m[1]}/{$m[2]}/embed",
                    'thumb' => null,
                ];
            }
        }

        // A direct file the admin hosts themselves (their own CDN, a
        // Drive/Dropbox direct-download link, etc.) — plays natively,
        // no third-party branding at all.
        if (preg_match('/\.(mp4|webm|ogg|mov)(\?.*)?$/i', $url)) {
            return ['type' => 'file', 'src' => $url, 'thumb' => null];
        }

        // Canva's normal watch/view/share pages refuse to be framed on
        // another site (plain iframe = blank box) — they only allow it
        // with an `embed` flag on the URL, which is what Canva's own
        // "Share -> Embed" button adds automatically. Adding it here
        // means the admin can just paste the plain share link.
        if (stripos($host, 'canva.com') !== false || stripos($host, 'canva.link') !== false) {
            $sep = (strpos($url, '?') !== false) ? '&' : '?';
            return ['type' => 'canva', 'src' => $url . $sep . 'embed', 'thumb' => null];
        }

        // Anything else (Vimeo, etc.) — best effort as a plain iframe
        // using the link exactly as given.
        return ['type' => 'embed', 'src' => $url, 'thumb' => null];
    }
}
