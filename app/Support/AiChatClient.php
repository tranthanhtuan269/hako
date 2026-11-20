<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class AiChatClient
{
    public function isEnabled(): bool
    {
        return filled(SiteIntegrations::aiApiKey());
    }

    public function activeProvider(): string
    {
        return SiteIntegrations::aiProvider();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function completeJson(string $prompt, string $logContext): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if ($this->activeProvider() === 'gemini' && ! config('ai.gemini.enabled', true)) {
            return null;
        }

        $provider = $this->activeProvider();
        $apiKey = SiteIntegrations::aiApiKey($provider);
        $model = SiteIntegrations::aiModel($provider);
        $timeout = SiteIntegrations::aiTimeout($provider);

        try {
            $text = match ($provider) {
                'openai' => $this->openAiCompatibleText(
                    'https://api.openai.com/v1/chat/completions',
                    $apiKey,
                    $model,
                    $prompt,
                    $timeout,
                    []
                ),
                'openrouter' => $this->openAiCompatibleText(
                    'https://openrouter.ai/api/v1/chat/completions',
                    $apiKey,
                    $model,
                    $prompt,
                    $timeout,
                    [
                        'HTTP-Referer' => rtrim((string) config('site.url', config('app.url')), '/'),
                        'X-Title' => (string) config('site.name', config('app.name')),
                    ],
                    false
                ),
                'anthropic' => $this->anthropicText($apiKey, $model, $prompt, $timeout),
                default => $this->geminiText($apiKey, $model, $prompt, $timeout),
            };

            if ($text === null || $text === '') {
                return null;
            }

            return self::decodeJsonObject($text);
        } catch (\Throwable $exception) {
            Log::warning("{$logContext} exception", [
                'provider' => $provider,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public static function decodeJsonObject(string $text): ?array
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $text, $matches)) {
            $text = trim($matches[1]);
        }

        $parsed = json_decode($text, true);

        if (is_array($parsed)) {
            return $parsed;
        }

        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $parsed = json_decode($matches[0], true);

            return is_array($parsed) ? $parsed : null;
        }

        return null;
    }

    private function geminiText(string $apiKey, string $model, string $prompt, int $timeout): ?string
    {
        $response = Http::timeout($timeout)
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => 0.55,
                        'maxOutputTokens' => 8192,
                    ],
                ]
            );

        if (! $response->successful()) {
            $this->logHttpFailure('Gemini', $response);

            return null;
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        return is_string($text) ? trim($text) : null;
    }

    /**
     * @param  array<string, string>  $extraHeaders
     */
    private function openAiCompatibleText(
        string $url,
        string $apiKey,
        string $model,
        string $prompt,
        int $timeout,
        array $extraHeaders,
        bool $forceJsonObject = true,
    ): ?string {
        $headers = array_merge([
            'Authorization' => 'Bearer '.$apiKey,
        ], $extraHeaders);

        $payload = [
            'model' => $model,
            'temperature' => 0.55,
            'max_tokens' => 8192,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You return only valid JSON objects that match the user instructions. No markdown.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        if ($forceJsonObject) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::timeout($timeout)
            ->withHeaders($headers)
            ->post($url, $payload);

        if (! $response->successful()) {
            $this->logHttpFailure('OpenAI-compatible', $response);

            return null;
        }

        $text = data_get($response->json(), 'choices.0.message.content');

        return is_string($text) ? trim($text) : null;
    }

    private function anthropicText(string $apiKey, string $model, string $prompt, int $timeout): ?string
    {
        $response = Http::timeout($timeout)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 8192,
                'temperature' => 0.55,
                'system' => 'You return only valid JSON objects that match the user instructions. No markdown.',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            $this->logHttpFailure('Anthropic', $response);

            return null;
        }

        $text = data_get($response->json(), 'content.0.text');

        return is_string($text) ? trim($text) : null;
    }

    private function logHttpFailure(string $provider, \Illuminate\Http\Client\Response $response): void
    {
        Log::warning("{$provider} AI request failed", [
            'status' => $response->status(),
            'body' => Str::limit($response->body(), 500),
        ]);
    }
}
