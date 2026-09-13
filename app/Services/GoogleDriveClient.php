<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal Google Drive v3 client using a service account, built entirely on
 * plain HTTP calls + PHP's built-in openssl extension — no Google SDK
 * package required, so it doesn't depend on anything that needs a fresh
 * `composer install` on the server.
 */
class GoogleDriveClient
{
    private array $credentials;

    public function __construct()
    {
        $json = setting('ai_helper_drive_service_account_json');
        $this->credentials = $json ? (json_decode($json, true) ?: []) : [];
    }

    public function isConfigured(): bool
    {
        return !empty($this->credentials['client_email']) && !empty($this->credentials['private_key']);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function getAccessToken(): ?string
    {
        $cacheKey = 'google_drive_token_' . md5($this->credentials['client_email'] ?? '');

        return Cache::remember($cacheKey, 3000, function () {
            $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $now    = time();
            $claims = $this->base64UrlEncode(json_encode([
                'iss'   => $this->credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/drive',
                'aud'   => $this->credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));

            $signInput = $header . '.' . $claims;
            $signature = '';
            openssl_sign($signInput, $signature, $this->credentials['private_key'], 'sha256');
            $jwt = $signInput . '.' . $this->base64UrlEncode($signature);

            $response = Http::asForm()->post($this->credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if (!$response->successful()) {
                Log::warning('Google Drive auth failed: ' . $response->body());
                return null;
            }

            return $response->json('access_token');
        });
    }

    /** Search only — will NOT create it if missing, since a root folder the
     *  service account creates itself would be invisible to the human owner. */
    private function findRootFolder(string $name, string $token): ?string
    {
        $escaped = str_replace("'", "\\'", $name);
        $search  = Http::withToken($token)->get('https://www.googleapis.com/drive/v3/files', [
            'q'      => "name='{$escaped}' and mimeType='application/vnd.google-apps.folder' and trashed=false",
            'fields' => 'files(id,name)',
        ]);

        if ($search->successful() && !empty($search->json('files'))) {
            return $search->json('files.0.id');
        }

        return null;
    }

    /** Safe to auto-create: always nested inside a folder the service
     *  account already has access to, so anything it creates here is
     *  automatically visible to the human who shared that parent folder. */
    private function findOrCreateFolder(string $name, string $parentId, string $token): ?string
    {
        $escaped = str_replace("'", "\\'", $name);
        $search  = Http::withToken($token)->get('https://www.googleapis.com/drive/v3/files', [
            'q'      => "name='{$escaped}' and mimeType='application/vnd.google-apps.folder' and trashed=false and '{$parentId}' in parents",
            'fields' => 'files(id,name)',
        ]);

        if ($search->successful() && !empty($search->json('files'))) {
            return $search->json('files.0.id');
        }

        $create = Http::withToken($token)->post('https://www.googleapis.com/drive/v3/files', [
            'name'     => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents'  => [$parentId],
        ]);

        if (!$create->successful()) {
            Log::warning('Google Drive folder create failed: ' . $create->body());
            return null;
        }

        return $create->json('id');
    }

    /**
     * $folderPath: folder names from top to bottom, e.g.
     * ['Brainova Lessons', 'Grade 4', 'Science', 'Term 1', 'Space', 'Celestial Bodies', 'Black Hole']
     * The FIRST entry must already exist and be shared with the service account.
     */
    public function uploadToPath(array $folderPath, string $filename, string $fileContent, string $mimeType = 'application/pdf'): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'message' => 'Google Drive has not been set up yet — add the service account JSON in Website Setup → AI Helper.'];
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return ['ok' => false, 'message' => 'Could not authenticate with Google Drive. Please check the service account JSON.'];
        }

        try {
            $rootName = array_shift($folderPath);
            $parentId = $this->findRootFolder($rootName, $token);
            if (!$parentId) {
                return ['ok' => false, 'message' => 'Could not find the "' . $rootName . '" folder in Google Drive. Make sure it exists and is shared with the service account (Editor access).'];
            }

            foreach ($folderPath as $folderName) {
                $parentId = $this->findOrCreateFolder($folderName, $parentId, $token);
                if (!$parentId) {
                    return ['ok' => false, 'message' => 'Could not create the folder "' . $folderName . '" in Google Drive.'];
                }
            }

            $boundary = '-------314159265358979323846';
            $metadata = json_encode(['name' => $filename, 'parents' => [$parentId]]);

            $body  = "--{$boundary}\r\n";
            $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
            $body .= $metadata . "\r\n";
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: {$mimeType}\r\n\r\n";
            $body .= $fileContent . "\r\n";
            $body .= "--{$boundary}--";

            $upload = Http::withToken($token)
                ->withBody($body, "multipart/related; boundary={$boundary}")
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');

            if (!$upload->successful()) {
                Log::warning('Google Drive upload failed: ' . $upload->body());
                return ['ok' => false, 'message' => 'Could not upload the file to Google Drive.'];
            }

            return ['ok' => true];
        } catch (\Throwable $th) {
            Log::warning('Google Drive exception: ' . $th->getMessage());
            return ['ok' => false, 'message' => 'Google Drive is temporarily unavailable.'];
        }
    }
}
