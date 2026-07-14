<?php

namespace App\Services\Trends;

use App\Exceptions\SerpApiRequestException;
use Illuminate\Support\Facades\Http;

class SerpApiTrendsClient implements TrendsClientInterface
{
    public function trendingNow(string $regionCode): array
    {
        $key = config('services.serpapi.key');

        if (! $key) {
            throw new SerpApiRequestException('SERPAPI_KEY is not configured.');
        }

        $response = Http::timeout(15)
            ->get('https://serpapi.com/search.json', [
                'engine' => 'google_trends_trending_now',
                'geo' => $regionCode,
                'api_key' => $key,
            ]);

        if (! $response->successful()) {
            throw new SerpApiRequestException(
                "SerpApi request failed with status {$response->status()}.",
                $response->status(),
            );
        }

        $data = $response->json('trending_searches', []);

        return array_map(fn (array $item) => [
            'term' => $item['query'],
            'rank' => $item['rank'],
            'search_volume' => $item['search_volume'] ?? null,
        ], $data);
    }
}
