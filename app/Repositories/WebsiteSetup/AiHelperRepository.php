<?php

namespace App\Repositories\WebsiteSetup;

use App\Models\Setting;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WebsiteSetup\AiHelperLog;

class AiHelperRepository
{
    private $model;

    public function __construct(Setting $model)
    {
        $this->model = $model;
    }

    public function updateSettings($request)
    {
        try {
            $fields = [
                'ai_helper_api_key',
                'ai_helper_model',
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

            $this->uploadMascot($request, 'ai_helper_teacher_mascot');
            $this->uploadMascot($request, 'ai_helper_student_mascot');

            return true;
        } catch (\Throwable $th) {
            return false;
        }
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
     * Raw call to Gemini. Returns ['ok' => true, 'text' => string] or ['ok' => false, 'message' => string].
     */
    private function callGemini(string $prompt, bool $forceJson = false): array
    {
        $apiKey = setting('ai_helper_api_key');
        $model  = setting('ai_helper_model') ?: 'gemini-3.6-flash';

        if (!$apiKey) {
            return ['ok' => false, 'message' => 'This AI helper has not been set up yet — add an API key in Website Setup → AI Helper.'];
        }

        $body = [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
        ];
        if ($forceJson) {
            $body['generationConfig'] = ['responseMimeType' => 'application/json'];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        // Gemini occasionally returns 503 "model is overloaded" under heavy public
        // demand — that's transient on Google's side, so retry a couple of times
        // with a short pause before giving up, instead of failing on the first hit.
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout(60)->post($url, $body);

                if ($response->successful()) {
                    $text = $response->json('candidates.0.content.parts.0.text');
                    if (!$text) {
                        return ['ok' => false, 'message' => 'The AI helper did not return an answer. Please try again.'];
                    }
                    return ['ok' => true, 'text' => trim($text)];
                }

                Log::warning('AI Helper (Gemini) error: ' . $response->status() . ' ' . $response->body());

                if ($response->status() === 503 && $attempt < $maxAttempts) {
                    sleep(2);
                    continue;
                }

                if ($response->status() === 503) {
                    return ['ok' => false, 'message' => 'The AI is currently handling a lot of requests. Please wait a minute and try again.'];
                }

                return ['ok' => false, 'message' => 'The AI helper could not generate an answer right now. Please try again shortly.'];
            } catch (\Throwable $th) {
                Log::warning('AI Helper (Gemini) exception: ' . $th->getMessage());
                return ['ok' => false, 'message' => 'The AI helper is temporarily unavailable.'];
            }
        }

        return ['ok' => false, 'message' => 'The AI is currently handling a lot of requests. Please wait a minute and try again.'];
    }

    /**
     * $fields keys: grade, subject, term, unit, module, lesson_title.
     * Returns ['ok' => true, 'text' => $lessonPlanText] or ['ok' => false, 'message' => $error].
     */
    public function generateLessonPlan(array $fields): array
    {
        $systemPrompt = setting('ai_helper_system_prompt') ?: 'You are a helpful assistant.';
        return $this->callGemini($systemPrompt . $this->inputBlock($fields));
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

        $result = $this->callGemini($systemPrompt . $this->inputBlock($fields) . $schemaInstruction, true);
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

        return ['ok' => true, 'data' => $merged];
    }

    /**
     * Student homework/study helper — a single free-text question, one plain
     * answer back. Returns ['ok' => true, 'text' => string] or ['ok' => false, 'message' => string].
     */
    public function askStudentHelper(string $question): array
    {
        $systemPrompt = setting('ai_help_student_system_prompt')
            ?: 'You are a friendly, patient study helper for a school student. Explain things simply, step by step, and never just give a final homework answer without helping the student understand it.';

        return $this->callGemini($systemPrompt . "\n\nStudent's question: " . $question);
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

        $result = $this->callGemini($prompt, true);
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

    public function logUsage(?int $userId, ?string $userName, string $role, string $tool, string $summary): void
    {
        try {
            AiHelperLog::create([
                'user_id'   => $userId,
                'user_name' => $userName,
                'user_role' => $role,
                'tool'      => $tool,
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
