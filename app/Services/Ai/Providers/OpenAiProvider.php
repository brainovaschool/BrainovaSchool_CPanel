<?php

namespace App\Services\Ai\Providers;

use Illuminate\Support\Facades\Http;

class OpenAiProvider
{
    use ClassifiesAiHttpErrors;

    /**
     * Returns ['ok'=>true,'text'=>string] on success, or
     * ['ok'=>false,'kind'=>'transient'|'rate_limit'|'permanent','message'=>string] on failure.
     */
    public function call(string $apiKey, string $model, string $prompt, bool $forceJson, int $timeoutSeconds): array
    {
        $body = [
            'model'    => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ];
        if ($forceJson) {
            $body['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::timeout($timeoutSeconds)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', $body);
        } catch (\Throwable $e) {
            return ['ok' => false, 'kind' => 'transient', 'message' => 'OpenAI request failed: ' . $e->getMessage()];
        }

        if ($response->successful()) {
            $text = $response->json('choices.0.message.content');
            if (!$text) {
                return ['ok' => false, 'kind' => 'transient', 'message' => 'OpenAI did not return an answer.'];
            }
            return ['ok' => true, 'text' => trim($text)];
        }

        return $this->classifyHttpError($response->status(), $response->body(), 'OpenAI');
    }
}
