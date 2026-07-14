<?php

namespace App\Console\Commands;

use App\Models\Region;
use App\Models\Trend;
use App\Models\TrendArticle;
use App\Services\News\NewsResolverInterface;
use App\Services\Trends\TrendsClientInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CollectTrends extends Command
{
    protected $signature = 'trends:collect {--region= : Region code to collect for (default: all)}';

    protected $description = 'Collect trending topics from Google Trends via SerpApi';

    private const PERIODS = ['4h', '24h', '48h', '7d'];

    private int $created = 0;

    private int $updated = 0;

    private int $articlesResolved = 0;

    private int $failed = 0;

    public function handle(
        TrendsClientInterface $trendsClient,
        NewsResolverInterface $newsResolver,
    ): int {
        $this->created = 0;
        $this->updated = 0;
        $this->articlesResolved = 0;
        $this->failed = 0;

        $regionQuery = Region::query();

        if ($code = $this->option('region')) {
            $regionQuery->where('code', $code);
        }

        $regions = $regionQuery->get();

        if ($regions->isEmpty()) {
            $this->warn('No regions found.');
            return self::SUCCESS;
        }

        foreach ($regions as $region) {
            foreach (self::PERIODS as $period) {
                $this->collectForRegion($region, $period, $trendsClient, $newsResolver);
            }
        }

        $this->info("Done. Created: {$this->created}, Updated: {$this->updated}, Articles: {$this->articlesResolved}, Failed: {$this->failed}");

        return self::SUCCESS;
    }

    private function collectForRegion(
        Region $region,
        string $period,
        TrendsClientInterface $trendsClient,
        NewsResolverInterface $newsResolver,
    ): void {
        try {
            $items = $trendsClient->trendingNow($region->code);
        } catch (\Throwable $e) {
            $this->error("Failed to fetch trends for {$region->code}/{$period}: {$e->getMessage()}");
            $this->failed++;
            return;
        }

        $incomingTerms = [];

        foreach ($items as $item) {
            $normalizedTerm = Str::of($item['term'])->lower()->ascii()->toString();

            $incomingTerms[] = $normalizedTerm;

            $existing = Trend::where('normalized_term', $normalizedTerm)
                ->where('region_id', $region->id)
                ->where('period', $period)
                ->first();

            if ($existing) {
                $existing->update([
                    'rank' => $item['rank'],
                    'search_volume' => $item['search_volume'] ?? null,
                    'last_seen_at' => now(),
                    'is_active' => true,
                ]);
                $this->updated++;
                $trend = $existing;
            } else {
                $trend = Trend::create([
                    'term' => $item['term'],
                    'normalized_term' => $normalizedTerm,
                    'region_id' => $region->id,
                    'period' => $period,
                    'rank' => $item['rank'],
                    'search_volume' => $item['search_volume'] ?? null,
                    'first_seen_at' => now(),
                    'last_seen_at' => now(),
                    'is_active' => true,
                ]);
                $this->created++;
            }

            if ($trend->trendArticles()->count() === 0) {
                $this->resolveArticle($trend, $region->code, $newsResolver);
            }
        }

        Trend::where('region_id', $region->id)
            ->where('period', $period)
            ->whereNotIn('normalized_term', $incomingTerms)
            ->update(['is_active' => false]);
    }

    private function resolveArticle(
        Trend $trend,
        string $regionCode,
        NewsResolverInterface $newsResolver,
    ): void {
        try {
            $article = $newsResolver->findTopArticle($trend->term, $regionCode);
        } catch (\Throwable $e) {
            $this->warn("Article resolution failed for '{$trend->term}': {$e->getMessage()}");
            return;
        }

        if ($article === null) {
            $this->warn("No article found for '{$trend->term}'.");
            return;
        }

        TrendArticle::create([
            'trend_id' => $trend->id,
            'url' => $article['url'],
            'site_name' => $article['site_name'],
            'title' => $article['title'],
            'published_at' => $article['published_at'] ?? null,
            'position' => 1,
            'fetched_at' => now(),
        ]);

        $this->articlesResolved++;
    }
}
