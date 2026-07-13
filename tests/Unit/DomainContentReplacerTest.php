<?php

namespace Tests\Unit;

use App\Support\DomainContentReplacer;
use PHPUnit\Framework\TestCase;

class DomainContentReplacerTest extends TestCase
{
    public function test_replace_updates_http_and_https_links(): void
    {
        $html = '<a href="https://old.example.com/deals">Deal</a> and http://www.old.example.com/sale';

        $result = DomainContentReplacer::replace($html, 'old.example.com', 'new.example.com');

        $this->assertSame(2, $result['count']);
        $this->assertStringContainsString('https://new.example.com/deals', $result['text']);
        $this->assertStringContainsString('http://www.new.example.com/sale', $result['text']);
    }

    public function test_replace_preserves_unrelated_domains(): void
    {
        $text = 'Visit https://amazon.com and https://old.example.com/page';

        $result = DomainContentReplacer::replace($text, 'old.example.com', 'new.example.com');

        $this->assertSame(1, $result['count']);
        $this->assertStringContainsString('https://amazon.com', $result['text']);
        $this->assertStringContainsString('https://new.example.com/page', $result['text']);
    }

    public function test_normalize_host_strips_protocol_and_www(): void
    {
        $this->assertSame('example.com', DomainContentReplacer::normalizeHost('https://www.example.com/path'));
    }
}
