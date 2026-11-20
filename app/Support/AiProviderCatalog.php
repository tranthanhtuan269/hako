<?php

namespace App\Support;

final class AiProviderCatalog
{
    public const DEFAULT = 'gemini';

    /**
     * @return array<string, array{
     *     label: string,
     *     hint: string,
     *     docs_url: string,
     *     default_model: string,
     *     model_hint: string,
     *     key_env: string,
     *     key_setting: string,
     *     model_setting: string
     * }>
     */
    public static function all(): array
    {
        return [
            'gemini' => [
                'label' => 'Google Gemini',
                'hint' => 'Google AI Studio key. Used for blog, store descriptions, and product picking.',
                'docs_url' => 'https://aistudio.google.com/apikey',
                'default_model' => 'gemini-2.5-flash',
                'model_hint' => 'e.g. gemini-2.5-flash, gemini-2.5-pro',
                'key_env' => 'GEMINI_API_KEY',
                'key_setting' => 'gemini_api_key',
                'model_setting' => 'ai_gemini_model',
            ],
            'openai' => [
                'label' => 'OpenAI',
                'hint' => 'Platform API key for GPT models (Chat Completions).',
                'docs_url' => 'https://platform.openai.com/api-keys',
                'default_model' => 'gpt-4o-mini',
                'model_hint' => 'e.g. gpt-4o-mini, gpt-4o, gpt-4.1',
                'key_env' => 'OPENAI_API_KEY',
                'key_setting' => 'openai_api_key',
                'model_setting' => 'ai_openai_model',
            ],
            'anthropic' => [
                'label' => 'Anthropic Claude',
                'hint' => 'Claude Messages API key for Sonnet / Haiku / Opus.',
                'docs_url' => 'https://console.anthropic.com/settings/keys',
                'default_model' => 'claude-sonnet-4-5',
                'model_hint' => 'e.g. claude-sonnet-4-5, claude-haiku-4-5',
                'key_env' => 'ANTHROPIC_API_KEY',
                'key_setting' => 'anthropic_api_key',
                'model_setting' => 'ai_anthropic_model',
            ],
            'openrouter' => [
                'label' => 'OpenRouter',
                'hint' => 'One key for many models (OpenAI, Claude, Llama, Gemini, and more).',
                'docs_url' => 'https://openrouter.ai/keys',
                'default_model' => 'openai/gpt-4o-mini',
                'model_hint' => 'e.g. openai/gpt-4o-mini, anthropic/claude-sonnet-4.5, google/gemini-2.5-flash',
                'key_env' => 'OPENROUTER_API_KEY',
                'key_setting' => 'openrouter_api_key',
                'model_setting' => 'ai_openrouter_model',
            ],
        ];
    }

    public static function ids(): array
    {
        return array_keys(self::all());
    }

    public static function exists(string $id): bool
    {
        return array_key_exists($id, self::all());
    }

    /**
     * @return array{label: string, hint: string, docs_url: string, default_model: string, model_hint: string, key_env: string, key_setting: string, model_setting: string}|null
     */
    public static function get(string $id): ?array
    {
        return self::all()[$id] ?? null;
    }

    public static function normalize(string $id): string
    {
        $id = strtolower(trim($id));

        return self::exists($id) ? $id : self::DEFAULT;
    }
}
