<?php

namespace App\Services\Ai\Providers;

use Illuminate\Support\Facades\Http;

class AnthropicProvider
{
    use ClassifiesAiHttpErrors;

    /**
     * Returns ['ok'=>true,'text'=>string] on success, or
     * ['ok'=>false,'kind'=>'transient'|'rate_limit'|'permanent','message'=>string] on failure.
     * Anthropic has no forced-JSON response mode — callers that need JSON
     * already ask for it in the prompt text itself.
     */
    public function call(string $apiKey, string $model, string $prompt, bool $forceJson, int $timeoutSeconds): array
    {
        $body = [
            'model'      => $model,
            'max_tokens' => 4096,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ];

        try {
            $response = Http::timeout($timeoutSeconds)
                ->withHeaders([
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                ])
                ->post('https://api.anthropic.com/v1/messages', $body);
        } catch (\Throwable $e) {
            return ['ok' => false, 'kind' => 'transient', 'message' => 'Anthropic request failed: ' . $e->getMessage()];
        }

        if ($response->successful()) {
            $text = $response->json('content.0.text');
            if (!$text) {
                return ['ok' => false, 'kind' => 'transient', 'message' => 'Anthropic did not return an answer.'];
            }
            return ['ok' => true, 'text' => trim($text)];
        }

        return $this->classifyHttpError($response->status(), $response->body(), 'Anthropic');
    }
}
