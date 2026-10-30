<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Log;
use App\Services\Ai\Providers\GeminiProvider;
use App\Services\Ai\Providers\OpenAiProvider;
use App\Services\Ai\Providers\OpenAiImageProvider;
use App\Services\Ai\Providers\AnthropicProvider;

/**
 * Single entry point for every AI call in the app. Holds an ordered list of
 * configured accounts (provider + API key + model) and walks through them
 * in priority order: retries a single account a couple of times on
 * transient failures (timeout/5xx), but moves on to the next account
 * immediately on a rate limit or a permanent error (bad key/model) instead
 * of wasting the retry budget on something that will keep failing the same
 * way. Only reports failure once every configured account is exhausted.
 *
 * Bounded by a total wall-clock budget so a bad run of accounts can't push
 * the whole synchronous web request past the host's PHP execution-time
 * limit — callers should also raise set_time_limit() for the controller
 * action, since this budget assumes some headroom is left for the rest of
 * the request (e.g. building a PDF) after generate() returns.
 */
class AiGateway
{
    // 60s/150s (not 20s/90s) because Reel Studio's prompts can be far larger
    // than AI Helper's original lesson-plan prompts (a multi-thousand-word
    // custom style template plus structured JSON for many scenes), and
    // cover-image generation routinely takes 30-60s on its own — a
    // genuinely slower response for a big request shouldn't be misread as
    // a dead account. A higher ceiling never slows down a fast response.
    private const TIMEOUT_SECONDS_PER_ATTEMPT = 60;
    private const MAX_ATTEMPTS_PER_ACCOUNT    = 3;
    private const TOTAL_BUDGET_SECONDS        = 150;

    /** @var array<string,object> */
    private array $providers;

    public function __construct()
    {
        $this->providers = [
            'gemini'    => new GeminiProvider(),
            'openai'    => new OpenAiProvider(),
            'anthropic' => new AnthropicProvider(),
        ];
    }

    /**
     * Returns ['ok'=>true,'text'=>string,'provider'=>string,'label'=>string]
     * or ['ok'=>false,'message'=>string].
     */
    public function generate(string $prompt, bool $forceJson = false): array
    {
        $accounts = $this->accounts();

        if (empty($accounts)) {
            return ['ok' => false, 'message' => 'This AI helper has not been set up yet — add at least one AI account in Website Setup → AI Helper.'];
        }

        $result = $this->attemptWithAccounts($accounts, function (array $account, int $timeout) use ($prompt, $forceJson) {
            $provider = $this->providers[$account['provider']] ?? null;
            if (!$provider) {
                return ['ok' => false, 'kind' => 'permanent', 'message' => 'unknown provider'];
            }
            return $provider->call($account['api_key'], $account['model'], $prompt, $forceJson, $timeout);
        });

        if (!$result['ok']) {
            return $result;
        }

        $text = $forceJson ? $this->stripJsonFences($result['text']) : $result['text'];
        return [
            'ok'       => true,
            'text'     => $text,
            'provider' => $result['account']['provider'],
            'label'    => $result['account']['label'] ?: ucfirst($result['account']['provider']),
        ];
    }

    /**
     * Cover-image generation — OpenAI only (the other configured providers
     * don't expose an image endpoint through a plain API key), so this only
     * walks whichever of the configured accounts are OpenAI.
     * Returns ['ok'=>true,'base64'=>string] or ['ok'=>false,'message'=>string].
     */
    public function generateImage(string $prompt, string $size = '1024x1024'): array
    {
        $accounts = array_values(array_filter($this->accounts(), fn ($a) => $a['provider'] === 'openai'));

        if (empty($accounts)) {
            return ['ok' => false, 'message' => 'No OpenAI account is configured — add one in Website Setup → AI Helper to generate cover images.'];
        }

        $imageProvider = new OpenAiImageProvider();
        $result = $this->attemptWithAccounts($accounts, fn (array $account, int $timeout) =>
            $imageProvider->generate($account['api_key'], $prompt, $size, $timeout)
        );

        if (!$result['ok']) {
            return $result;
        }

        return ['ok' => true, 'base64' => $result['base64']];
    }

    /**
     * Shared account-walking retry loop used by both generate() and
     * generateImage() - tries each account in order, retrying a single
     * account a couple of times on a transient failure but moving on
     * immediately on a rate limit or permanent error. $call gets
     * (account, timeoutSeconds) and returns the same shape a provider's
     * call()/generate() does. On success, the winning account is merged
     * into the result as 'account' so the caller can report which one
     * answered.
     */
    private function attemptWithAccounts(array $accounts, callable $call): array
    {
        $deadline    = microtime(true) + self::TOTAL_BUDGET_SECONDS;
        $lastMessage = 'The AI helper could not complete this request right now. Please try again shortly.';
        // One line per account tried, so a final failure can show exactly
        // what happened with EVERY key, not just the last one — otherwise
        // accounts 1 and 2's real errors are silently lost, and all the
        // admin ever sees is whatever the last account happened to say.
        $accountFailures = [];

        foreach ($accounts as $account) {
            $accountLabel = ($account['label'] ?: ucfirst($account['provider'])) . " ({$account['provider']})";

            for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS_PER_ACCOUNT; $attempt++) {
                $remaining = $deadline - microtime(true);
                if ($remaining <= 1) {
                    $accountFailures[] = "{$accountLabel}: ran out of time to try";
                    return ['ok' => false, 'message' => $this->summarizeFailure($accountFailures)];
                }

                $timeout = max(5, (int) min(self::TIMEOUT_SECONDS_PER_ATTEMPT, $remaining));
                $result  = $call($account, $timeout);

                if ($result['ok']) {
                    return $result + ['account' => $account];
                }

                $lastMessage = $result['message'];
                Log::warning("AI Helper [{$account['provider']}/{$account['label']}] attempt {$attempt} failed: " . $result['message']);

                if ($result['kind'] !== 'transient') {
                    $accountFailures[] = "{$accountLabel}: {$result['message']}";
                    break; // rate-limited or permanently broken — try the next account, not this one again
                }

                $remaining = $deadline - microtime(true);
                if ($attempt < self::MAX_ATTEMPTS_PER_ACCOUNT && $remaining > 1) {
                    $backoff = min(0.5 * (2 ** ($attempt - 1)), $remaining - 0.5);
                    if ($backoff > 0) {
                        usleep((int) ($backoff * 1_000_000));
                    }
                } elseif ($attempt === self::MAX_ATTEMPTS_PER_ACCOUNT) {
                    $accountFailures[] = "{$accountLabel}: {$result['message']} (after {$attempt} tries)";
                }
            }
        }

        return ['ok' => false, 'message' => $this->summarizeFailure($accountFailures) ?: ('All configured AI accounts failed. ' . $lastMessage)];
    }

    private function summarizeFailure(array $accountFailures): string
    {
        if (empty($accountFailures)) {
            return '';
        }
        $count = count($accountFailures);
        return "All {$count} configured AI account(s) failed:\n" . implode("\n", $accountFailures);
    }

    /**
     * Ordered, enabled accounts from the ai_helper_accounts JSON setting, or
     * a single legacy Gemini account built from the old ai_helper_api_key /
     * ai_helper_model settings if that JSON list hasn't been set up yet —
     * keeps a site working after this upgrade without re-entering its key.
     * Disabled rows are intentionally dropped here (the settings screen's
     * own decoding keeps disabled rows so they still show, unchecked, in
     * the editor — this method is only for what actually gets tried).
     */
    private function accounts(): array
    {
        $accounts = [];
        $raw      = setting('ai_helper_accounts');
        $decoded  = $raw ? json_decode($raw, true) : null;

        if (is_array($decoded)) {
            foreach ($decoded as $row) {
                if (!is_array($row) || empty($row['enabled'])) {
                    continue;
                }
                $provider = $row['provider'] ?? null;
                $apiKey   = trim((string) ($row['api_key'] ?? ''));
                $model    = trim((string) ($row['model'] ?? ''));

                if (!in_array($provider, ['gemini', 'openai', 'anthropic'], true) || !$apiKey || !$model) {
                    continue;
                }

                $accounts[] = [
                    'provider' => $provider,
                    'api_key'  => $apiKey,
                    'model'    => $model,
                    'label'    => trim((string) ($row['label'] ?? '')),
                ];
            }
        }

        if (empty($accounts)) {
            $legacyKey = setting('ai_helper_api_key');
            if ($legacyKey) {
                $accounts[] = [
                    'provider' => 'gemini',
                    'api_key'  => $legacyKey,
                    'model'    => setting('ai_helper_model') ?: 'gemini-3.6-flash',
                    'label'    => 'Primary Gemini',
                ];
            }
        }

        return $accounts;
    }

    private function stripJsonFences(string $text): string
    {
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```[a-zA-Z]*\s*/', '', $text);
            $text = preg_replace('/```\s*$/', '', $text);
            $text = trim($text);
        }
        return $text;
    }
}
