<?php

namespace Tests\Unit;

use App\Support\AiChatClient;
use App\Support\AiProviderCatalog;
use PHPUnit\Framework\TestCase;

class AiProviderCatalogTest extends TestCase
{
    public function test_catalog_includes_gemini_and_extra_providers(): void
    {
        $ids = AiProviderCatalog::ids();

        $this->assertContains('gemini', $ids);
        $this->assertContains('openai', $ids);
        $this->assertContains('anthropic', $ids);
        $this->assertContains('openrouter', $ids);
    }

    public function test_normalize_falls_back_to_gemini(): void
    {
        $this->assertSame('openai', AiProviderCatalog::normalize('OpenAI'));
        $this->assertSame('gemini', AiProviderCatalog::normalize('unknown'));
        $this->assertSame('gemini', AiProviderCatalog::normalize(''));
    }

    public function test_decode_json_object_handles_fences_and_prose(): void
    {
        $this->assertSame(
            ['title' => 'Hello'],
            AiChatClient::decodeJsonObject("```json\n{\"title\":\"Hello\"}\n```")
        );

        $this->assertSame(
            ['content' => 'ok'],
            AiChatClient::decodeJsonObject('Here you go: {"content":"ok"} thanks')
        );

        $this->assertNull(AiChatClient::decodeJsonObject('not json'));
    }
}
