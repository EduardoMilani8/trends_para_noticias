<?php

namespace Tests\Unit;

use App\Services\News\GoogleNewsResolver;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleNewsResolverTest extends TestCase
{
    private GoogleNewsResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new GoogleNewsResolver;
    }

    public function test_find_top_article_parses_first_item(): void
    {
        $xml = file_get_contents(__DIR__ . '/../Fixtures/google_news_rss.xml');

        Http::fake([
            'https://news.google.com/rss/search*' => Http::response($xml, 200, ['Content-Type' => 'application/rss+xml']),
        ]);

        $result = $this->resolver->findTopArticle('Flamengo', 'BR');

        $this->assertNotNull($result);
        $this->assertEquals('https://globo.com/flamengo-campeao', $result['url']);
        $this->assertEquals('Flamengo vence o Brasileirão após grande campanha', $result['title']);
        $this->assertEquals('Globo', $result['site_name']);
        $this->assertEquals('Mon, 14 Jul 2025 12:00:00 GMT', $result['published_at']);

        Http::assertSent(function ($request) {
            $url = $request->url();
            return str_contains($url, 'hl=pt-BR')
                && str_contains($url, 'gl=BR')
                && str_contains($url, 'ceid=BR');
        });
    }

    public function test_find_top_article_uses_english_for_us_region(): void
    {
        $xml = file_get_contents(__DIR__ . '/../Fixtures/google_news_rss_empty.xml');

        Http::fake([
            'https://news.google.com/rss/search*' => Http::response($xml, 200),
        ]);

        $this->resolver->findTopArticle('Bitcoin', 'US');

        Http::assertSent(function ($request) {
            $url = $request->url();
            return str_contains($url, 'hl=en-US')
                && str_contains($url, 'gl=US')
                && str_contains($url, 'ceid=US');
        });
    }

    public function test_find_top_article_returns_null_when_no_items(): void
    {
        $xml = file_get_contents(__DIR__ . '/../Fixtures/google_news_rss_empty.xml');

        Http::fake([
            'https://news.google.com/rss/search*' => Http::response($xml, 200),
        ]);

        $result = $this->resolver->findTopArticle('Neymar', 'BR');

        $this->assertNull($result);
    }

    public function test_find_top_article_returns_null_on_http_error(): void
    {
        Http::fake([
            'https://news.google.com/rss/search*' => Http::response('Server Error', 500),
        ]);

        $result = $this->resolver->findTopArticle('Neymar', 'BR');

        $this->assertNull($result);
    }

    public function test_find_top_article_returns_null_on_invalid_xml(): void
    {
        Http::fake([
            'https://news.google.com/rss/search*' => Http::response('not xml at all', 200),
        ]);

        $result = $this->resolver->findTopArticle('Neymar', 'BR');

        $this->assertNull($result);
    }
}
