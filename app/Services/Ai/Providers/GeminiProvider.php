<?php

namespace App\Services\Ai\Providers;

use Illuminate\Support\Facades\Http;

class GeminiProvider
{
    use ClassifiesAiHttpErrors;

    /**
     * Returns ['ok'=>true,'text'=>string] on success, or
     * ['ok'=>false,'kind'=>'transient'|'rate_limit'|'permanent','message'=>string] on failure.
     */
    public function call(string $apiKey, string $model, string $prompt, bool $forceJson, int $timeoutSeconds): array
    {
        $body = [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
        ];
        if ($forceJson) {
            $body['generationConfig'] = ['responseMimeType' => 'application/json'];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        try {
            $response = Http::timeout($timeoutSeconds)->post($url, $body);
        } catch (\Throwable $e) {
            return ['ok' => false, 'kind' => 'transient', 'message' => 'Gemini request failed: ' . $e->getMessage()];
        }

        if ($response->successful()) {
            $text = $response->json('candidates.0.content.parts.0.text');
            if (!$text) {
                return ['ok' => false, 'kind' => 'transient', 'message' => 'Gemini did not return an answer.'];
            }
            return ['ok' => true, 'text' => trim($text)];
        }

        return $this->classifyHttpError($response->status(), $response->body(), 'Gemini');
    }
}
