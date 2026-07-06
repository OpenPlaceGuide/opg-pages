<?php

namespace Tests\Unit;

use App\Services\TagRenderer;
use PHPUnit\Framework\TestCase;

class TagRendererTest extends TestCase
{
    public function test_safe_website_url_keeps_http_and_https(): void
    {
        $this->assertSame('https://example.com/menu', TagRenderer::safeWebsiteUrl('https://example.com/menu'));
        $this->assertSame('http://example.com', TagRenderer::safeWebsiteUrl('http://example.com'));
        $this->assertSame('HTTPS://example.com', TagRenderer::safeWebsiteUrl('HTTPS://example.com'));
    }

    public function test_safe_website_url_rejects_dangerous_schemes(): void
    {
        $this->assertNull(TagRenderer::safeWebsiteUrl('javascript:alert(document.cookie)'));
        $this->assertNull(TagRenderer::safeWebsiteUrl(' JavaScript:alert(1)'));
        $this->assertNull(TagRenderer::safeWebsiteUrl('data:text/html,<script>alert(1)</script>'));
        $this->assertNull(TagRenderer::safeWebsiteUrl('vbscript:msgbox'));
    }

    public function test_safe_website_url_defaults_schemeless_values_to_https(): void
    {
        // OSM website tags frequently omit the scheme.
        $this->assertSame('https://example.com', TagRenderer::safeWebsiteUrl('example.com'));
        $this->assertSame('https://example.com/about', TagRenderer::safeWebsiteUrl('//example.com/about'));
    }

    public function test_safe_website_url_handles_empty_values(): void
    {
        $this->assertNull(TagRenderer::safeWebsiteUrl(null));
        $this->assertNull(TagRenderer::safeWebsiteUrl(''));
        $this->assertNull(TagRenderer::safeWebsiteUrl('   '));
    }
}
