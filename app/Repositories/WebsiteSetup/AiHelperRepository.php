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
                'ai_helper_question_label',
                'ai_helper_button_text',
                'ai_helper_system_prompt',
            ];

            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $setting = $this->model::where('name', $field)->first();
                    if ($setting) {
                        $setting->value = $request->$field;
                    } else {
                        $setting        = new $this->model;
                        $setting->name  = $field;
                        $setting->value = $request->$field;
                    }
                    $setting->save();
                }
            }

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function ask(string $userInput): array
    {
        $apiKey       = setting('ai_helper_api_key');
        $model        = setting('ai_helper_model') ?: 'gemini-2.0-flash';
        $systemPrompt = setting('ai_helper_system_prompt') ?: 'You are a helpful assistant.';

        if (!$apiKey) {
            return ['ok' => false, 'message' => 'This AI helper has not been set up yet — add an API key in Website Setup → AI Helper.'];
        }

        try {
            $response = Http::timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        ['parts' => [['text' => $systemPrompt . "\n\nUser's message: " . $userInput]]],
                    ],
                ]
            );

            if (!$response->successful()) {
                Log::warning('AI Helper (Gemini) error: ' . $response->status() . ' ' . $response->body());
                return ['ok' => false, 'message' => 'The AI helper could not answer right now. Please try again shortly.'];
            }

            $text = $response->json('candidates.0.content.parts.0.text');
            if (!$text) {
                return ['ok' => false, 'message' => 'The AI helper did not return an answer. Please try again.'];
            }

            return ['ok' => true, 'message' => trim($text)];
        } catch (\Throwable $th) {
            Log::warning('AI Helper (Gemini) exception: ' . $th->getMessage());
            return ['ok' => false, 'message' => 'The AI helper is temporarily unavailable.'];
        }
    }
}
