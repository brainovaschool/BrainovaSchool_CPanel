<?php

namespace App\Services\Ai\Providers;

use Illuminate\Support\Facades\Http;

class OpenAiImageProvider
{
    use ClassifiesAiHttpErrors;

    /**
     * Returns ['ok'=>true,'base64'=>string] on success, or
     * ['ok'=>false,'kind'=>'transient'|'rate_limit'|'permanent','message'=>string] on failure.
     */
    public function generate(string $apiKey, string $prompt, string $size, int $timeoutSeconds): array
    {
        try {
            $response = Http::timeout($timeoutSeconds)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/images/generations', [
                    'model'  => 'gpt-image-1',
                    'prompt' => $prompt,
                    'size'   => $size,
                ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'kind' => 'transient', 'message' => 'OpenAI image request failed: ' . $e->getMessage()];
        }

        if ($response->successful()) {
            $base64 = $response->json('data.0.b64_json');
            if (!$base64) {
                return ['ok' => false, 'kind' => 'transient', 'message' => 'OpenAI did not return an image.'];
            }
            return ['ok' => true, 'base64' => $base64];
        }

        return $this->classifyHttpError($response->status(), $response->body(), 'OpenAI');
    }
}
