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

    public function test_social_url_builds_profile_links_from_bare_usernames(): void
    {
        // The common case in Ethiopia: OSM stores just the handle.
        $this->assertSame('https://www.tiktok.com/@_yenuyabi', TagRenderer::socialUrl('tiktok', '_yenuyabi'));
        $this->assertSame('https://www.tiktok.com/@_yenuyabi', TagRenderer::socialUrl('tiktok', '@_yenuyabi'));
        $this->assertSame('https://www.instagram.com/yene.habesha', TagRenderer::socialUrl('instagram', 'yene.habesha'));
        $this->assertSame('https://t.me/yene_habesha', TagRenderer::socialUrl('telegram', 'yene_habesha'));
    }

    public function test_social_url_passes_through_full_urls_via_the_sanitiser(): void
    {
        $this->assertSame('https://www.tiktok.com/@x', TagRenderer::socialUrl('tiktok', 'https://www.tiktok.com/@x'));
        $this->assertSame('https://t.me/yene_habesha', TagRenderer::socialUrl('telegram', 'https://t.me/yene_habesha'));
        // Scheme-less path values still get https, not a bogus handle link.
        $this->assertSame('https://tiktok.com/@x', TagRenderer::socialUrl('tiktok', 'tiktok.com/@x'));
    }

    public function test_social_url_never_produces_a_dangerous_scheme(): void
    {
        // A "javascript:..." value has no slash, so it is treated as a handle and
        // fully percent-encoded into an inert path rather than a javascript: href.
        $url = TagRenderer::socialUrl('tiktok', 'javascript:alert(1)');
        $this->assertStringStartsWith('https://www.tiktok.com/@', $url);
        $this->assertStringNotContainsString('javascript:', $url);
        $this->assertNull(TagRenderer::socialUrl('tiktok', ''));
        $this->assertNull(TagRenderer::socialUrl('unknown', 'handle'));
    }

    public function test_phones_splits_on_semicolons_and_commas(): void
    {
        $tags = (object) ['phone' => '+251 91 111 1111; +251 92 222 2222, +251 93 333 3333'];
        $this->assertSame(
            ['+251 91 111 1111', '+251 92 222 2222', '+251 93 333 3333'],
            TagRenderer::phones($tags)
        );
        $this->assertSame([], TagRenderer::phones((object) []));
        // Falls back to the contact: namespace.
        $this->assertSame(['+251 90 000 0000'], TagRenderer::phones((object) ['contact:phone' => '+251 90 000 0000']));
    }

    public function test_social_links_collapse_across_branches_keeping_the_first(): void
    {
        $branchA = (object) ['contact:tiktok' => '___yenuyabi'];
        $branchB = (object) ['contact:tiktok' => 'ignored_second', 'contact:telegram' => 'yene_habesha'];

        $this->assertSame([
            'tiktok' => 'https://www.tiktok.com/@___yenuyabi',
            'telegram' => 'https://t.me/yene_habesha',
        ], TagRenderer::socialLinks([$branchA, $branchB]));
    }

    public function test_address_parts_are_ordered_and_labelled(): void
    {
        $tags = (object) [
            'addr:housename' => 'Kirkos Shopping Center',
            'addr:housenumber' => '12',
            'addr:street' => 'Kirkos',
            'addr:unit' => 'C301',
            'level' => '3',
        ];
        $this->assertSame(
            ['Kirkos Shopping Center', '12 Kirkos', 'Unit C301', 'Level 3'],
            TagRenderer::addressParts($tags)
        );
        $this->assertSame([], TagRenderer::addressParts((object) []));
        // addr:floor wins over level when both are present.
        $this->assertSame(['Level G'], TagRenderer::addressParts((object) ['addr:floor' => 'G', 'level' => '0']));
    }
}
