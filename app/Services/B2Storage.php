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
    public function isConfigured(): bool
    {
        return (bool) config('services.b2.key_id')
            && (bool) config('services.b2.application_key')
            && (bool) config('services.b2.bucket_id')
            && (bool) config('services.b2.bucket_name');
    }

    private function authorize(): ?array
    {
        $keyId = config('services.b2.key_id');
        $appKey = config('services.b2.application_key');

        return Cache::remember('b2_auth_session', 3600 * 20, function () use ($keyId, $appKey) {
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
        });
    }

    /** $folder: e.g. "class-content-materials". Returns the stored key (full
     *  path inside the bucket) and the original filename, for display. */
    public function upload(UploadedFile $file, string $folder): array
    {
        $session = $this->authorize();
        if (!$session) {
            return ['ok' => false, 'message' => 'Could not connect to file storage. Try again in a moment.'];
        }

        try {
            $getUploadUrl = Http::withToken($session['authorizationToken'])
                ->post($session['apiUrl'] . '/b2api/v3/b2_get_upload_url', [
                    'bucketId' => config('services.b2.bucket_id'),
                ]);

            if (!$getUploadUrl->successful()) {
                Log::warning('B2 get_upload_url failed: ' . $getUploadUrl->body());
                return ['ok' => false, 'message' => 'Could not prepare the upload. Try again in a moment.'];
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
                return ['ok' => false, 'message' => 'Could not upload the file. Try again in a moment.'];
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
            $auth = Http::withToken($session['authorizationToken'])
                ->post($session['apiUrl'] . '/b2api/v3/b2_get_download_authorization', [
                    'bucketId' => config('services.b2.bucket_id'),
                    'fileNamePrefix' => $storageKey,
                    'validDurationInSeconds' => $ttlSeconds,
                ]);

            if (!$auth->successful()) {
                Log::warning('B2 get_download_authorization failed: ' . $auth->body());
                return null;
            }

            $bucketName = config('services.b2.bucket_name');
            return $session['downloadUrl'] . '/file/' . $bucketName . '/' . implode('/', array_map('rawurlencode', explode('/', $storageKey)))
                . '?Authorization=' . $auth->json('authorizationToken');
        } catch (\Throwable $th) {
            Log::warning('B2 download-url exception: ' . $th->getMessage());
            return null;
        }
    }
}
