<?php

namespace App\Repositories\WebsiteSetup;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

            return true;
        } catch (\Throwable $th) {
            return false;
        }
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

        try {
            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                $body
            );

            if (!$response->successful()) {
                Log::warning('AI Helper (Gemini) error: ' . $response->status() . ' ' . $response->body());
                // TEMPORARY while testing: show the real error so it can be diagnosed
                // without server/log access. Revert to a generic message before this
                // page is exposed to real visitors.
                return ['ok' => false, 'message' => 'DEBUG (' . $response->status() . '): ' . $response->body()];
            }

            $text = $response->json('candidates.0.content.parts.0.text');
            if (!$text) {
                return ['ok' => false, 'message' => 'The AI helper did not return an answer. Please try again.'];
            }

            return ['ok' => true, 'text' => trim($text)];
        } catch (\Throwable $th) {
            Log::warning('AI Helper (Gemini) exception: ' . $th->getMessage());
            return ['ok' => false, 'message' => 'The AI helper is temporarily unavailable.'];
        }
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
}
