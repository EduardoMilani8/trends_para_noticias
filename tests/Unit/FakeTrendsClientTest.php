<?php

namespace Tests\Unit;

use App\Exceptions\SerpApiRequestException;
use App\Services\Trends\FakeTrendsClient;
use PHPUnit\Framework\TestCase;

class FakeTrendsClientTest extends TestCase
{
    private FakeTrendsClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new FakeTrendsClient;
    }

    public function test_trending_now_returns_stubbed_results(): void
    {
        $stub = [
            ['term' => 'Bitcoin', 'rank' => 1, 'search_volume' => 50000],
            ['term' => 'Neymar', 'rank' => 2, 'search_volume' => null],
        ];

        $this->client->stubSuccess($stub);

        $result = $this->client->trendingNow('BR');

        $this->assertEquals($stub, $result);
    }

    public function test_trending_now_throws_when_stubbed_with_error(): void
    {
        $this->client->stubError('Connection timed out');

        $this->expectException(SerpApiRequestException::class);
        $this->expectExceptionMessage('Connection timed out');

        $this->client->trendingNow('US');
    }

    public function test_trending_now_returns_empty_array_by_default(): void
    {
        $result = $this->client->trendingNow('PT');

        $this->assertSame([], $result);
    }
}
