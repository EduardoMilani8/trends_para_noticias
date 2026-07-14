<?php

namespace App\Services\Trends;

interface TrendsClientInterface
{
    /**
     * Fetch trending topics for a given region from Google Trends.
     *
     * @return array<array{term: string, rank: int, search_volume: ?int}>
     *
     * @throws SerpApiRequestException
     */
    public function trendingNow(string $regionCode): array;
}
