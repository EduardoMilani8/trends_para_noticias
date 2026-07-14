<?php

namespace Tests\Unit;

use App\Exceptions\SerpApiRequestException;
use App\Services\Trends\SerpApiTrendsClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SerpApiTrendsClientTest extends TestCase
{
    private SerpApiTrendsClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.serpapi.key' => 'test-key']);
        $this->client = new SerpApiTrendsClient;
    }

    public function test_trending_now_returns_parsed_results(): void
    {
        Http::fake([
            'https://serpapi.com/search.json*' => Http::response([
                'trending_searches' => [
                    ['query' => 'Flamengo', 'rank' => 1, 'search_volume' => 100000],
                    ['query' => 'Lula', 'rank' => 2, 'search_volume' => null],
                ],
            ], 200),
        ]);

        $result = $this->client->trendingNow('BR');

        $this->assertCount(2, $result);
        $this->assertEquals('Flamengo', $result[0]['term']);
        $this->assertEquals(1, $result[0]['rank']);
        $this->assertEquals(100000, $result[0]['search_volume']);
        $this->assertEquals('Lula', $result[1]['term']);
        $this->assertNull($result[1]['search_volume']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'serpapi.com/search.json')
                && str_contains($request->url(), 'geo=BR')
                && str_contains($request->url(), 'api_key=test-key');
        });
    }

    public function test_trending_now_throws_on_non_200_response(): void
    {
        Http::fake([
            'https://serpapi.com/search.json*' => Http::response('Unauthorized', 401),
        ]);

        $this->expectException(SerpApiRequestException::class);
        $this->expectExceptionMessage('status 401');

        $this->client->trendingNow('US');
    }

    public function test_trending_now_throws_when_key_not_configured(): void
    {
        config(['services.serpapi.key' => null]);

        $this->expectException(SerpApiRequestException::class);
        $this->expectExceptionMessage('SERPAPI_KEY is not configured');

        $this->client->trendingNow('BR');
    }
}
