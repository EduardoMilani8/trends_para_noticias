<?php

namespace Tests\Unit;

use App\Services\News\FakeNewsResolver;
use PHPUnit\Framework\TestCase;

class FakeNewsResolverTest extends TestCase
{
    private FakeNewsResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new FakeNewsResolver;
    }

    public function test_find_top_article_returns_stubbed_result(): void
    {
        $article = [
            'url' => 'https://example.com/news',
            'title' => 'Big News Today',
            'site_name' => 'Example.com',
            'published_at' => '2025-07-14T12:00:00Z',
        ];

        $this->resolver->stubResult($article);

        $result = $this->resolver->findTopArticle('test', 'BR');

        $this->assertEquals($article, $result);
    }

    public function test_find_top_article_returns_null_when_empty(): void
    {
        $this->resolver->stubEmpty();

        $result = $this->resolver->findTopArticle('test', 'BR');

        $this->assertNull($result);
    }

    public function test_find_top_article_returns_null_by_default(): void
    {
        $result = $this->resolver->findTopArticle('test', 'US');

        $this->assertNull($result);
    }
}
