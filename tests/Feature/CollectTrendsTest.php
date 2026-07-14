<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\Trend;
use App\Models\TrendArticle;
use App\Services\News\FakeNewsResolver;
use App\Services\News\NewsResolverInterface;
use App\Services\Trends\FakeTrendsClient;
use App\Services\Trends\TrendsClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectTrendsTest extends TestCase
{
    use RefreshDatabase;

    private FakeTrendsClient $fakeTrends;

    private FakeNewsResolver $fakeNews;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeTrends = new FakeTrendsClient;
        $this->fakeNews = new FakeNewsResolver;

        $this->app->bind(TrendsClientInterface::class, fn () => $this->fakeTrends);
        $this->app->bind(NewsResolverInterface::class, fn () => $this->fakeNews);
    }

    public function test_first_run_creates_all_trends_as_active(): void
    {
        $region = Region::create(['code' => 'BR', 'name' => 'Brasil']);

        $this->fakeTrends->stubSuccessForRegion('BR', [
            ['term' => 'Flamengo', 'rank' => 1, 'search_volume' => 100000],
            ['term' => 'Carnaval', 'rank' => 2, 'search_volume' => 50000],
        ]);

        $this->fakeNews->stubResult([
            'url' => 'https://example.com/news',
            'title' => 'Flamengo vence',
            'site_name' => 'Example.com',
            'published_at' => '2025-07-14T12:00:00Z',
        ]);

        $this->artisan('trends:collect', ['--region' => 'BR'])
            ->assertExitCode(0);

        $trends = Trend::where('region_id', $region->id)->get();

        $this->assertCount(8, $trends);
        $this->assertTrue($trends->every(fn (Trend $t) => $t->is_active));

        $flamengo = Trend::where('normalized_term', 'flamengo')
            ->where('region_id', $region->id)
            ->where('period', '4h')
            ->first();

        $this->assertNotNull($flamengo);
        $this->assertEquals('Flamengo', $flamengo->term);
        $this->assertEquals(1, $flamengo->rank);
        $this->assertEquals(100000, $flamengo->search_volume);
        $this->assertNotNull($flamengo->first_seen_at);
        $this->assertNotNull($flamengo->last_seen_at);

        $this->assertCount(1, TrendArticle::where('trend_id', $flamengo->id)->get());
    }

    public function test_second_run_updates_existing_and_deactivates_missing(): void
    {
        $region = Region::create(['code' => 'BR', 'name' => 'Brasil']);

        $this->fakeTrends->stubSuccessForRegion('BR', [
            ['term' => 'Flamengo', 'rank' => 1, 'search_volume' => 100000],
            ['term' => 'Carnaval', 'rank' => 2, 'search_volume' => 50000],
        ]);
        $this->fakeNews->stubEmpty();

        $this->artisan('trends:collect', ['--region' => 'BR']);

        $flamengo4h = Trend::where('normalized_term', 'flamengo')
            ->where('region_id', $region->id)
            ->where('period', '4h')
            ->first();
        $carnaval4h = Trend::where('normalized_term', 'carnaval')
            ->where('region_id', $region->id)
            ->where('period', '4h')
            ->first();
        $this->assertTrue($flamengo4h->is_active);
        $this->assertTrue($carnaval4h->is_active);

        $this->fakeTrends->stubSuccessForRegion('BR', [
            ['term' => 'Flamengo', 'rank' => 3, 'search_volume' => 80000],
            ['term' => 'Eleições', 'rank' => 1, 'search_volume' => 200000],
        ]);

        $this->artisan('trends:collect', ['--region' => 'BR']);

        $flamengo4h->refresh();
        $this->assertTrue($flamengo4h->is_active);
        $this->assertEquals(3, $flamengo4h->rank);
        $this->assertEquals(80000, $flamengo4h->search_volume);

        $carnaval4h->refresh();
        $this->assertFalse($carnaval4h->is_active);

        $eleicoes4h = Trend::where('normalized_term', 'eleicoes')
            ->where('region_id', $region->id)
            ->where('period', '4h')
            ->first();
        $this->assertNotNull($eleicoes4h);
        $this->assertTrue($eleicoes4h->is_active);
        $this->assertEquals('Eleições', $eleicoes4h->term);
    }

    public function test_article_not_duplicated_on_second_run(): void
    {
        $region = Region::create(['code' => 'BR', 'name' => 'Brasil']);

        $this->fakeTrends->stubSuccessForRegion('BR', [
            ['term' => 'Flamengo', 'rank' => 1, 'search_volume' => 100000],
        ]);
        $this->fakeNews->stubResult([
            'url' => 'https://example.com/news',
            'title' => 'Flamengo vence',
            'site_name' => 'Example.com',
            'published_at' => null,
        ]);

        $this->artisan('trends:collect', ['--region' => 'BR']);

        $this->fakeTrends->stubSuccessForRegion('BR', [
            ['term' => 'Flamengo', 'rank' => 2, 'search_volume' => 90000],
        ]);

        $this->artisan('trends:collect', ['--region' => 'BR']);

        $flamengo = Trend::where('normalized_term', 'flamengo')
            ->where('region_id', $region->id)
            ->first();

        $this->assertCount(1, TrendArticle::where('trend_id', $flamengo->id)->get());
    }
}
