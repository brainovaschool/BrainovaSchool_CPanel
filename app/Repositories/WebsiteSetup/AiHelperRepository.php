<?php

namespace App\Repositories\WebsiteSetup;

use App\Models\Setting;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\WebsiteSetup\AiHelperLog;
use App\Services\Ai\AiGateway;

class AiHelperRepository
{
    private $model;

    public function __construct(Setting $model)
    {
        $this->model = $model;
    }

    /** Returns ['status' => bool, 'warning' => ?string] — the warning (e.g.
     *  about duplicate-model fallback accounts) is informational only and
     *  doesn't block the save. */
    public function updateSettings($request): array
    {
        try {
            $fields = [
                'ai_helper_page_title',
                'ai_helper_button_text',
                'ai_helper_system_prompt',
                'ai_helper_delivery_mode',
                'ai_helper_drive_root_folder',
                'ai_helper_drive_client_id',
                'ai_helper_drive_client_secret',
                'ai_help_student_page_title',
                'ai_help_student_button_text',
                'ai_help_student_question_label',
                'ai_help_student_system_prompt',
                'ai_helper_teacher_limit_period',
                'ai_helper_teacher_limit_count',
                'ai_help_student_limit_period',
                'ai_help_student_limit_count',
            ];

            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $value = $request->$field;
                    if (in_array($field, ['ai_helper_drive_client_id', 'ai_helper_drive_client_secret'], true)) {
                        $value = trim((string) $value);
                    }
                    $this->setSetting($field, $value);
                }
            }

            $warning = null;
            if ($request->has('ai_helper_accounts')) {
                $warning = $this->saveAccounts($request->input('ai_helper_accounts'));
            }

            $this->uploadMascot($request, 'ai_helper_teacher_mascot');
            $this->uploadMascot($request, 'ai_helper_student_mascot');

            return ['status' => true, 'warning' => $warning];
        } catch (\Throwable $th) {
            return ['status' => false, 'warning' => null];
        }
    }

    /**
     * Validates and re-encodes the AI accounts list submitted as JSON from
     * the settings form (one row per API key, built client-side) — drops
     * any row missing a provider/key/model so a stray empty row can't
     * silently sit in the fallback chain and always fail. Returns a
     * warning string if two or more ENABLED accounts share the exact same
     * provider+model — a key falling back to another key configured with
     * the identical model gives no real protection against that specific
     * model being overloaded/unavailable, which is the single most common
     * real-world failure (Gemini's "HTTP 503 temporarily overloaded").
     */
    private function saveAccounts(?string $raw): ?string
    {
        $decoded = json_decode((string) $raw, true);
        $clean   = [];

        if (is_array($decoded)) {
            foreach ($decoded as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $provider = in_array($row['provider'] ?? null, ['gemini', 'openai', 'anthropic'], true) ? $row['provider'] : null;
                $apiKey   = trim((string) ($row['api_key'] ?? ''));
                $model    = trim((string) ($row['model'] ?? ''));

                if (!$provider || !$apiKey || !$model) {
                    continue;
                }

                $clean[] = [
                    'provider' => $provider,
                    'api_key'  => $apiKey,
                    'model'    => $model,
                    'label'    => trim((string) ($row['label'] ?? '')),
                    'enabled'  => !empty($row['enabled']),
                ];
            }
        }

        $this->setSetting('ai_helper_accounts', json_encode($clean));

        return $this->duplicateModelWarning($clean);
    }

    private function duplicateModelWarning(array $accounts): ?string
    {
        $seen = [];
        $duplicateKeys = [];

        foreach ($accounts as $row) {
            if (empty($row['enabled'])) {
                continue;
            }
            $key = $row['provider'] . '/' . strtolower($row['model']);
            if (isset($seen[$key]) && !in_array($key, $duplicateKeys, true)) {
                $duplicateKeys[] = $key;
            }
            $seen[$key] = true;
        }

        if (empty($duplicateKeys)) {
            return null;
        }

        $examples = implode(', ', array_map(fn ($k) => str_replace('/', ' — ', $k), $duplicateKeys));
        return "Heads up: more than one enabled account uses the exact same model ({$examples}). If that specific model is busy or unavailable, falling back to another account using the SAME model won't help — for real protection, give your accounts different models (or different providers).";
    }

    private function uploadMascot($request, string $field): void
    {
        if (!$request->hasFile($field) || !$request->file($field)->isValid()) {
            return;
        }

        $path      = 'backend/uploads/settings';
        $file      = $request->file($field);
        $extension = $file->guessExtension();
        $filename  = Str::random(6) . '_' . time() . '.' . $extension;

        if (setting('file_system') == 's3') {
            $value = s3Upload($path, $file);
        } else {
            $file->move($path, $filename);
            $value = $path . '/' . $filename;
        }

        $this->setSetting($field, $value);
    }

    public function setSetting(string $name, ?string $value): void
    {
        $setting = $this->model::where('name', $name)->first();
        if ($setting) {
            $setting->value = $value;
        } else {
            $setting        = new $this->model;
            $setting->name  = $name;
            $setting->value = $value;
        }
        $setting->save();
    }

    private function inputBlock(array $fields): string
    {
        return "\n\nACTUAL INPUT PROVIDED BY THE USER (use this real data, not any example placeholders above):\n"
            . 'Grade: ' . $fields['grade'] . "\n"
            . 'Subject: ' . $fields['subject'] . "\n"
            . 'Term: ' . $fields['term'] . "\n"
            . 'Unit: ' . $fields['unit'] . "\n"
            . 'Module: ' . $fields['module'] . "\n"
            . 'Lesson Title: ' . $fields['lesson_title'];
    }

    /**
     * Delegates to the multi-provider, multi-account AiGateway (retries,
     * exponential backoff, and automatic fallback across every configured
     * AI account are handled there). Returns
     * ['ok'=>true,'text'=>string,'provider'=>string,'label'=>string] or
     * ['ok'=>false,'message'=>string].
     */
    private function callAi(string $prompt, bool $forceJson = false): array
    {
        return (new AiGateway())->generate($prompt, $forceJson);
    }

    /**
     * $fields keys: grade, subject, term, unit, module, lesson_title.
     * Returns ['ok' => true, 'text' => $lessonPlanText, 'provider' => string, 'label' => string]
     * or ['ok' => false, 'message' => $error].
     */
    public function generateLessonPlan(array $fields): array
    {
        $systemPrompt = setting('ai_helper_system_prompt') ?: 'You are a helpful assistant.';
        return $this->callAi($systemPrompt . $this->inputBlock($fields));
    }

    /**
     * Same as generateLessonPlan() but asks Gemini for strict JSON matching the
     * template used by the branded visual PDF, so each field can be placed into
     * its own box instead of one long block of text.
     * Returns ['ok' => true, 'data' => array] or ['ok' => false, 'message' => string].
     */
    public function generateLessonPlanStructured(array $fields): array
    {
        $systemPrompt = setting('ai_helper_system_prompt') ?: 'You are a helpful assistant.';

        $schemaInstruction = "\n\nRespond with ONLY valid JSON (no markdown, no commentary, no code fences) matching exactly this schema — every value must be plain text (no formatting), and every array must have exactly the number of items shown. Keep every value SHORT: this must all fit on a single printed page, so write single short sentences or short phrases only, never multi-sentence paragraphs, for every field:\n"
            . '{'
            . '"learning_goals":["...","...","...","..."],'
            . '"essential_question":"...",'
            . '"why_matters":"...",'
            . '"checkpoints":["...","...","..."],'
            . '"reflection":["...","...","..."],'
            . '"educator_notes":"...",'
            . '"wonder_hook":"...",'
            . '"wonder_bullets":["...","...","..."],'
            . '"wonder_allow":"...",'
            . '"discover_bullets":["...","...","..."],'
            . '"discover_vocabulary":["...","...","..."],'
            . '"explore_bullets":["...","...","...","..."],'
            . '"explore_share":"...",'
            . '"create_option_a":"...",'
            . '"create_option_b":"...",'
            . '"create_option_c":"...",'
            . '"create_option_d":"...",'
            . '"beyond_classrooms":["...","...","..."],'
            . '"misconceptions":"..."'
            . '}';

        $result = $this->callAi($systemPrompt . $this->inputBlock($fields) . $schemaInstruction, true);
        if (!$result['ok']) {
            return $result;
        }

        $decoded = json_decode($result['text'], true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'message' => 'The AI helper returned an answer that could not be read. Please try again.'];
        }

        $defaults = [
            'learning_goals'      => [],
            'essential_question'  => '',
            'why_matters'         => '',
            'checkpoints'         => [],
            'reflection'          => [],
            'educator_notes'      => '',
            'wonder_hook'         => '',
            'wonder_bullets'      => [],
            'wonder_allow'        => '',
            'discover_bullets'    => [],
            'discover_vocabulary' => [],
            'explore_bullets'     => [],
            'explore_share'       => '',
            'create_option_a'     => '',
            'create_option_b'     => '',
            'create_option_c'     => '',
            'create_option_d'     => '',
            'beyond_classrooms'   => [],
            'misconceptions'      => '',
        ];

        $merged = array_merge($defaults, $decoded);

        // The AI doesn't always strictly honour "must be an array"/"must be text" —
        // normalise types here so a wrong shape can't crash the PDF template.
        foreach ($defaults as $key => $default) {
            if (is_array($default) && !is_array($merged[$key])) {
                $merged[$key] = $merged[$key] === null || $merged[$key] === '' ? [] : [(string) $merged[$key]];
            } elseif (is_string($default) && is_array($merged[$key])) {
                $merged[$key] = implode(', ', $merged[$key]);
            } elseif (is_string($default) && $merged[$key] === null) {
                $merged[$key] = '';
            }
        }

        return ['ok' => true, 'data' => $merged, 'provider' => $result['provider'] ?? null, 'label' => $result['label'] ?? null];
    }

    /**
     * Student homework/study helper — a single free-text question, one plain
     * answer back. Returns ['ok' => true, 'text' => string, 'provider' => string, 'label' => string]
     * or ['ok' => false, 'message' => string].
     */
    public function askStudentHelper(string $question): array
    {
        $systemPrompt = setting('ai_help_student_system_prompt')
            ?: 'You are a friendly, patient study helper for a school student. Explain things simply, step by step, and never just give a final homework answer without helping the student understand it.';

        return $this->callAi($systemPrompt . "\n\nStudent's question: " . $question);
    }

    /**
     * "Teach Kea" (Phase 4): the student explains a skill in their own words
     * and Kea checks it for gaps, in Kea's own encouraging, curious-bird
     * voice. Structured JSON (not free text) so the UI can show a clear
     * "Kea's got it!" state instead of parsing prose.
     * Returns ['ok' => true, 'understood' => bool, 'feedback' => string] or ['ok' => false, 'message' => string].
     */
    public function teachKea(string $skillTitle, string $explanation): array
    {
        $prompt = <<<PROMPT
You are Kea, a curious, friendly bird mascot on a school learning app. A student is trying to teach YOU about a skill, in their own words, because explaining something out loud is one of the best ways to find out if you really understand it.

Skill they are explaining: "{$skillTitle}"

What the student said:
"{$explanation}"

Read their explanation like a curious learner, not a strict examiner. Decide if the core idea is basically correct (small wording issues are fine — focus on whether the underlying understanding is right). Then reply as Kea would: warm, encouraging, a little playful, never condescending. If something important is missing or wrong, say so kindly and name what it is — don't just say "good job" if it's incomplete. Keep the feedback to 2-3 short sentences, talking directly to the student ("you").

Respond with ONLY valid JSON (no markdown, no code fences) matching exactly this schema:
{"understood": true or false, "feedback": "..."}
PROMPT;

        $result = $this->callAi($prompt, true);
        if (!$result['ok']) {
            return $result;
        }

        $decoded = json_decode($result['text'], true);
        if (!is_array($decoded) || !array_key_exists('feedback', $decoded)) {
            return ['ok' => false, 'message' => 'Kea got a little confused reading that. Please try again.'];
        }

        return [
            'ok'        => true,
            'understood' => (bool) ($decoded['understood'] ?? false),
            'feedback'  => (string) $decoded['feedback'],
            'provider'  => $result['provider'] ?? null,
            'label'     => $result['label'] ?? null,
        ];
    }

    /**
     * AI Fact-Checker (Phase 4): the student pastes a claim they read or
     * heard somewhere, and Brainbot — methodical, precise, per his own
     * established character — checks it instead of the student trusting it
     * outright. Deliberately a 3-way verdict, not yes/no: "unclear" is a
     * real, honest answer for a claim that can't be confidently checked,
     * and teaches the actual habit (verify, don't just believe) rather than
     * training the student to expect a confident answer every time.
     * Returns ['ok' => true, 'verdict' => string, 'explanation' => string] or ['ok' => false, 'message' => string].
     */
    public function factCheck(string $claim): array
    {
        $prompt = <<<PROMPT
You are Brainbot, a precise, methodical robot mascot on a school learning app. A student has pasted a claim they read or heard somewhere. Your job is to model good fact-checking habits for the student — not to just be a search engine.

The claim:
"{$claim}"

Decide one of three verdicts:
- "likely_true": well-established, verifiable fact
- "likely_false": contradicts well-established facts
- "unclear": opinion, unverifiable, depends on context, or you are not confident enough either way

Then explain your reasoning in 2-3 short sentences, talking directly to the student ("you"), in Brainbot's calm, methodical voice. If the claim is unclear or unverifiable, say so plainly and suggest what kind of source would help check it (never invent a fake source or fake statistic).

Respond with ONLY valid JSON (no markdown, no code fences) matching exactly this schema:
{"verdict": "likely_true" or "likely_false" or "unclear", "explanation": "..."}
PROMPT;

        $result = $this->callAi($prompt, true);
        if (!$result['ok']) {
            return $result;
        }

        $decoded = json_decode($result['text'], true);
        if (!is_array($decoded) || !array_key_exists('explanation', $decoded)) {
            return ['ok' => false, 'message' => 'Brainbot got a little confused reading that. Please try again.'];
        }

        $verdict = in_array($decoded['verdict'] ?? null, ['likely_true', 'likely_false', 'unclear'], true)
            ? $decoded['verdict']
            : 'unclear';

        return [
            'ok'          => true,
            'verdict'     => $verdict,
            'provider'    => $result['provider'] ?? null,
            'label'       => $result['label'] ?? null,
            'explanation' => (string) $decoded['explanation'],
        ];
    }

    /**
     * $role: 'teacher' or 'student'. Reads that role's configured limit and
     * counts how many requests this user has already made in the current
     * period. Returns ['allowed' => true] or ['allowed' => false, 'message' => string].
     * An empty/zero limit setting means unlimited.
     */
    public function checkLimit(int $userId, string $role): array
    {
        $count  = (int) ($role === 'teacher' ? setting('ai_helper_teacher_limit_count') : setting('ai_help_student_limit_count'));
        $period = $role === 'teacher' ? setting('ai_helper_teacher_limit_period') : setting('ai_help_student_limit_period');
        $period = $period === 'week' ? 'week' : 'day';

        if ($count <= 0) {
            return ['allowed' => true];
        }

        $since = $period === 'week' ? Carbon::now()->startOfWeek() : Carbon::now()->startOfDay();

        $used = AiHelperLog::where('user_id', $userId)
            ->where('user_role', $role)
            ->where('created_at', '>=', $since)
            ->count();

        if ($used >= $count) {
            $periodLabel = $period === 'week' ? 'this week' : 'today';
            return ['allowed' => false, 'message' => "You've reached your limit of {$count} AI requests {$periodLabel}. Please try again " . ($period === 'week' ? 'next week.' : 'tomorrow.')];
        }

        return ['allowed' => true];
    }

    public function logUsage(?int $userId, ?string $userName, string $role, string $tool, string $summary, ?string $provider = null): void
    {
        try {
            AiHelperLog::create([
                'user_id'   => $userId,
                'user_name' => $userName,
                'user_role' => $role,
                'tool'      => $tool,
                'provider'  => $provider,
                'summary'   => \Illuminate\Support\Str::limit($summary, 500),
            ]);
        } catch (\Throwable $th) {
            Log::warning('AI Helper usage log failed: ' . $th->getMessage());
        }
    }

    public function bulkDestroyLogs(array $ids): int
    {
        return AiHelperLog::whereIn('id', $ids)->delete();
    }
}
