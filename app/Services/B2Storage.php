<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal Backblaze B2 client using B2's own native API (not the
 * S3-compatible layer), so uploading/serving class content never needs the
 * league/flysystem-aws-s3-v3 package installed on the server — just plain
 * HTTP calls, same reasoning as GoogleDriveClient.
 *
 * The bucket is private: files are never reachable by a bare URL. Every
 * download goes through temporaryDownloadUrl(), a link that only works for
 * a few minutes, generated fresh each time a page that's already checked
 * the viewer is allowed to see that content renders the "download" link.
 */
class B2Storage
{
    /** Reads straight from env() rather than config('services.b2.*') -
     *  config() went stale on this host (a copy of config/services.php
     *  with no 'b2' key kept being served from PHP's opcode cache even
     *  after clearing Laravel's own config cache and resetting opcache),
     *  while env() read the real, current .env value every time. */
    private function credential(string $key): string
    {
        return (string) env($key);
    }

    public function isConfigured(): bool
    {
        return $this->credential('B2_KEY_ID') !== ''
            && $this->credential('B2_APPLICATION_KEY') !== ''
            && $this->credential('B2_BUCKET_ID') !== ''
            && $this->credential('B2_BUCKET_NAME') !== '';
    }

    /** Never caches a failure - only ever caches a successful session, so
     *  fixing a credentials typo takes effect on the very next upload
     *  instead of staying "broken" for up to 20 hours. */
    private function authorize(): ?array
    {
        $cached = Cache::get('b2_auth_session');
        if ($cached) {
            return $cached;
        }

        $keyId = $this->credential('B2_KEY_ID');
        $appKey = $this->credential('B2_APPLICATION_KEY');

        $session = (function () use ($keyId, $appKey) {
            $response = Http::withBasicAuth($keyId, $appKey)
                ->get('https://api.backblazeb2.com/b2api/v3/b2_authorize_account');

            if (!$response->successful()) {
                Log::warning('B2 authorize failed: ' . $response->body());
                return null;
            }

            return [
                'apiUrl' => $response->json('apiInfo.storageApi.apiUrl'),
                'downloadUrl' => $response->json('apiInfo.storageApi.downloadUrl'),
                'authorizationToken' => $response->json('authorizationToken'),
            ];
        })();

        if ($session) {
            Cache::put('b2_auth_session', $session, 3600 * 20);
        }

        return $session;
    }

    /** $folder: e.g. "class-content-materials". Returns the stored key (full
     *  path inside the bucket) and the original filename, for display. */
    public function upload(UploadedFile $file, string $folder): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'message' => 'File storage isn\'t set up yet - the B2_... lines are missing from .env.'];
        }

        $session = $this->authorize();
        if (!$session) {
            return ['ok' => false, 'message' => 'Could not connect to file storage - check the B2 credentials in .env are correct.'];
        }

        try {
            // withHeaders(), not withToken() - B2 wants the raw token as-is,
            // not Laravel's default "Bearer <token>" formatting.
            $getUploadUrl = Http::withHeaders(['Authorization' => $session['authorizationToken']])
                ->post($session['apiUrl'] . '/b2api/v3/b2_get_upload_url', [
                    'bucketId' => $this->credential('B2_BUCKET_ID'),
                ]);

            if (!$getUploadUrl->successful()) {
                Log::warning('B2 get_upload_url failed: ' . $getUploadUrl->body());
                return ['ok' => false, 'message' => 'Could not prepare the upload (' . $getUploadUrl->status() . ': ' . substr($getUploadUrl->body(), 0, 200) . ').'];
            }

            $storageKey = $folder . '/' . now()->format('YmdHis') . '_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
            $contents = file_get_contents($file->getRealPath());

            $upload = Http::withHeaders([
                    'Authorization' => $getUploadUrl->json('authorizationToken'),
                    'X-Bz-File-Name' => rawurlencode($storageKey),
                    'X-Bz-Content-Sha1' => sha1($contents),
                ])
                ->withBody($contents, $file->getMimeType() ?: 'application/octet-stream')
                ->post($getUploadUrl->json('uploadUrl'));

            if (!$upload->successful()) {
                Log::warning('B2 upload failed: ' . $upload->body());
                return ['ok' => false, 'message' => 'Upload rejected (' . $upload->status() . ': ' . substr($upload->body(), 0, 200) . ').'];
            }

            return ['ok' => true, 'storage_key' => $storageKey, 'original_filename' => $file->getClientOriginalName()];
        } catch (\Throwable $th) {
            Log::warning('B2 upload exception: ' . $th->getMessage());
            return ['ok' => false, 'message' => 'File storage is temporarily unavailable.'];
        }
    }

    public function temporaryDownloadUrl(string $storageKey, int $ttlSeconds = 600): ?string
    {
        $session = $this->authorize();
        if (!$session) {
            return null;
        }

        try {
            $auth = Http::withHeaders(['Authorization' => $session['authorizationToken']])
                ->post($session['apiUrl'] . '/b2api/v3/b2_get_download_authorization', [
                    'bucketId' => $this->credential('B2_BUCKET_ID'),
                    'fileNamePrefix' => $storageKey,
                    'validDurationInSeconds' => $ttlSeconds,
                ]);

            if (!$auth->successful()) {
                Log::warning('B2 get_download_authorization failed: ' . $auth->body());
                return null;
            }

            $bucketName = $this->credential('B2_BUCKET_NAME');
            return $session['downloadUrl'] . '/file/' . $bucketName . '/' . implode('/', array_map('rawurlencode', explode('/', $storageKey)))
                . '?Authorization=' . $auth->json('authorizationToken');
        } catch (\Throwable $th) {
            Log::warning('B2 download-url exception: ' . $th->getMessage());
            return null;
        }
    }
}
