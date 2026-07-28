<?php
/*
Behzad Ghabaei
CS 85 PHP
Module 12 Assignment 12A
Instructor Seno
7/27/2026
*/

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiContentService
{
    /**
     * Generate a draft from a title, content type, and tone.
     */
    public function generateDraft(
        string $title,
        string $type = 'blog post',
        string $tone = 'professional'
    ): string {

        // Set the AI role based on tone
        $role = match ($tone) {
            'casual' => 'You are a friendly and conversational writer.',
            'humorous' => 'You are a witty copywriter with a sense of humor.',
            default => 'You are a professional tech blogger who writes clearly and accurately.',
        };

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.key'),
            'Content-Type'  => 'application/json',
        ])->post(config('services.openai.url') . '/chat/completions', [
            'model' => config('services.openai.model'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $role,
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($title, $type, $tone),
                ],
            ],
            'temperature' => 0.7,
            'max_tokens' => 500,
        ]);

        if (! $response->successful()) {
            Log::error('OpenAI API call failed.', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            //throw new Exception('The AI request failed.');
            throw new Exception(
    "Status: {$response->status()} Body: {$response->body()}"
);
        }

        return $response['choices'][0]['message']['content']
            ?? 'No output received';
    }

    /**
     * Build the prompt sent to the AI.
     */
    private function buildPrompt(string $title, string $type, string $tone): string
    {
        $task = match ($type) {
            'blog post' =>
                "Write a blog post titled \"$title\". Include an engaging introduction, several body paragraphs, and a short conclusion. Aim for about 400-500 words.",

            'meta description' =>
                "Write a single SEO-friendly meta description for \"$title\". Keep it around 155 characters.",

            'email subject line' =>
                "Write one short, attention-grabbing email subject line for \"$title\". Keep it under 10 words.",

            default =>
                "Write content about \"$title\".",
        };

        $toneInstruction = match ($tone) {
            'casual' =>
                'Use a casual, friendly, conversational tone.',

            'humorous' =>
                'Use a humorous and playful tone while remaining appropriate.',

            default =>
                'Use a professional, informative tone.',
        };

        return $task . " " . $toneInstruction;
    }
}