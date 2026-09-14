<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal Google Drive v3 client using standard OAuth 2.0 user delegation
 * (the site owner authorizes once via a normal Google sign-in screen; we
 * store the resulting refresh token and use it to keep getting fresh access
 * tokens). This uploads AS the owner's real Google account, so it has real
 * storage quota — a plain service account cannot create files in a personal
 * Drive at all (Google returns storageQuotaExceeded for that), which is why
 * this isn't built as a service-account integration.
 *
 * Built entirely on plain HTTP calls — no Google SDK package required, so it
 * doesn't depend on anything that needs a fresh `composer install`.
 */
class GoogleDriveClient
{
    public function isConfigured(): bool
    {
        return (bool) setting('ai_helper_drive_client_id')
            && (bool) setting('ai_helper_drive_client_secret')
            && (bool) setting('ai_helper_drive_refresh_token');
    }

    private function getAccessToken(): ?string
    {
        $clientId     = trim((string) setting('ai_helper_drive_client_id'));
        $clientSecret = trim((string) setting('ai_helper_drive_client_secret'));
        $refreshToken = trim((string) setting('ai_helper_drive_refresh_token'));

        if (!$clientId || !$clientSecret || !$refreshToken) {
            return null;
        }

        return Cache::remember('ai_helper_drive_access_token', 3000, function () use ($clientId, $clientSecret, $refreshToken) {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type'    => 'refresh_token',
            ]);

            if (!$response->successful()) {
                Log::warning('Google Drive token refresh failed: ' . $response->body());
                return null;
            }

            return $response->json('access_token');
        });
    }

    /** Search only — will NOT create it if missing, since a root folder our
     *  app creates itself could end up somewhere the owner doesn't expect. */
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
     * The FIRST entry must already exist in the connected account's Drive.
     */
    public function uploadToPath(array $folderPath, string $filename, string $fileContent, string $mimeType = 'application/pdf'): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'message' => 'Google Drive has not been connected yet — go to Website Setup → AI Helper and click "Connect Google Drive".'];
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return ['ok' => false, 'message' => 'Could not authenticate with Google Drive. Try disconnecting and reconnecting it in Website Setup → AI Helper.'];
        }

        try {
            $rootName = array_shift($folderPath);
            $parentId = $this->findRootFolder($rootName, $token);
            if (!$parentId) {
                return ['ok' => false, 'message' => 'Could not find the "' . $rootName . '" folder in Google Drive. Make sure it exists in the Drive of the account you connected.'];
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
