<?php

namespace App\Services\Ai\Providers;

/**
 * Shared HTTP-status classification for every provider adapter: "transient"
 * is worth retrying the same account for, "rate_limit"/"permanent" mean move
 * to the next configured account immediately instead of burning the retry
 * budget on a key that will keep failing the same way.
 */
trait ClassifiesAiHttpErrors
{
    private function classifyHttpError(int $status, string $body, string $providerLabel): array
    {
        if ($status === 429) {
            return ['ok' => false, 'kind' => 'rate_limit', 'message' => "{$providerLabel} rate limit reached."];
        }

        if ($status === 401 || $status === 403) {
            return ['ok' => false, 'kind' => 'permanent', 'message' => "{$providerLabel} rejected the API key."];
        }

        if ($status === 400 || $status === 404) {
            return ['ok' => false, 'kind' => 'permanent', 'message' => "{$providerLabel} rejected the request (check the model name): " . \Illuminate\Support\Str::limit($body, 200)];
        }

        if ($status >= 500) {
            return ['ok' => false, 'kind' => 'transient', 'message' => "{$providerLabel} is temporarily overloaded (HTTP {$status})."];
        }

        return ['ok' => false, 'kind' => 'transient', 'message' => "{$providerLabel} error (HTTP {$status})."];
    }
}
