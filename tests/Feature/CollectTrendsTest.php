<?php

namespace Tests\Feature;

use App\Jobs\ResolveTrendArticleJob;
use App\Models\Region;
use App\Models\Trend;
use App\Models\TrendArticle;
use App\Services\Trends\FakeTrendsClient;
use App\Services\Trends\TrendsClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CollectTrendsTest extends TestCase
{
    use RefreshDatabase;

    private FakeTrendsClient $fakeTrends;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeTrends = new FakeTrendsClient;
        $this->app->bind(TrendsClientInterface::class, fn () => $this->fakeTrends);

        Queue::fake();
    }

    public function test_first_run_creates_all_trends_as_active_and_dispatches_jobs(): void
    {
        $region = Region::create(['code' => 'BR', 'name' => 'Brasil']);

        $this->fakeTrends->stubSuccessForRegion('BR', [
            ['term' => 'Flamengo', 'rank' => 1, 'search_volume' => 100000],
            ['term' => 'Carnaval', 'rank' => 2, 'search_volume' => 50000],
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

        Queue::assertPushed(ResolveTrendArticleJob::class, 8);

        Queue::assertPushed(ResolveTrendArticleJob::class, function (ResolveTrendArticleJob $job) use ($flamengo) {
            return $job->trend->id === $flamengo->id
                && $job->regionCode === 'BR';
        });
    }

    public function test_second_run_updates_existing_and_deactivates_missing(): void
    {
        $region = Region::create(['code' => 'BR', 'name' => 'Brasil']);

        $this->fakeTrends->stubSuccessForRegion('BR', [
            ['term' => 'Flamengo', 'rank' => 1, 'search_volume' => 100000],
            ['term' => 'Carnaval', 'rank' => 2, 'search_volume' => 50000],
        ]);

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

        Queue::fake();

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

    public function test_job_not_dispatched_again_for_trends_with_articles(): void
    {
        $region = Region::create(['code' => 'BR', 'name' => 'Brasil']);

        $this->fakeTrends->stubSuccessForRegion('BR', [
            ['term' => 'Flamengo', 'rank' => 1, 'search_volume' => 100000],
        ]);

        $this->artisan('trends:collect', ['--region' => 'BR']);

        $flamengo = Trend::where('normalized_term', 'flamengo')
            ->where('region_id', $region->id)
            ->first();

        Queue::assertPushed(ResolveTrendArticleJob::class, 4);
        Queue::assertPushed(ResolveTrendArticleJob::class, function (ResolveTrendArticleJob $job) use ($flamengo) {
            return $job->trend->id === $flamengo->id && $job->regionCode === 'BR';
        });

        Trend::where('region_id', $region->id)->each(function (Trend $t) {
            TrendArticle::create([
                'trend_id' => $t->id,
                'url' => 'https://example.com/news',
                'site_name' => 'Example.com',
                'title' => 'Article for ' . $t->term,
                'position' => 1,
                'fetched_at' => now(),
            ]);
        });

        Queue::fake();

        $this->fakeTrends->stubSuccessForRegion('BR', [
            ['term' => 'Flamengo', 'rank' => 2, 'search_volume' => 90000],
        ]);

        $this->artisan('trends:collect', ['--region' => 'BR']);

        Queue::assertPushed(ResolveTrendArticleJob::class, 0);
    }
}
