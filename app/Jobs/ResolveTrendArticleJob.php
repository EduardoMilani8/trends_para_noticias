<?php

namespace App\Jobs;

use App\Models\Trend;
use App\Models\TrendArticle;
use App\Services\News\NewsResolverInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ResolveTrendArticleJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Trend $trend,
        public string $regionCode,
    ) {
        $this->onQueue('default');
    }

    public function handle(NewsResolverInterface $newsResolver): void
    {
        if ($this->trend->trendArticles()->count() > 0) {
            return;
        }

        try {
            $article = $newsResolver->findTopArticle($this->trend->term, $this->regionCode);
        } catch (\Throwable $e) {
            Log::warning("Article resolution failed for '{$this->trend->term}': {$e->getMessage()}");
            return;
        }

        if ($article === null) {
            Log::info("No article found for '{$this->trend->term}'.");
            return;
        }

        TrendArticle::create([
            'trend_id' => $this->trend->id,
            'url' => $article['url'],
            'site_name' => $article['site_name'],
            'title' => $article['title'],
            'published_at' => $article['published_at'] ?? null,
            'position' => 1,
            'fetched_at' => now(),
        ]);
    }
}
