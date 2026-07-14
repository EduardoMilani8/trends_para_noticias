<?php

namespace App\Services\News;

use Illuminate\Support\Facades\Http;

class GoogleNewsResolver implements NewsResolverInterface
{
    public function findTopArticle(string $term, string $regionCode): ?array
    {
        $url = $this->buildFeedUrl($term, $regionCode);

        $response = Http::timeout(15)->get($url);

        if (! $response->successful()) {
            return null;
        }

        $xml = @simplexml_load_string($response->body());

        if ($xml === false) {
            return null;
        }

        $items = $xml->channel->item;

        if (! $items || $items->count() === 0) {
            return null;
        }

        $first = $items[0];
        $rawTitle = (string) $first->title;

        return [
            'url' => (string) $first->link,
            'title' => $this->extractTitle($rawTitle),
            'site_name' => $this->extractSiteName($rawTitle),
            'published_at' => isset($first->pubDate) ? (string) $first->pubDate : null,
        ];
    }

    private function buildFeedUrl(string $term, string $regionCode): string
    {
        $isUs = strtoupper($regionCode) === 'US';

        $hl = $isUs ? 'en-US' : 'pt-BR';
        $ceid = strtoupper($regionCode) . ':' . $hl;

        $query = http_build_query([
            'q' => $term,
            'hl' => $hl,
            'gl' => strtoupper($regionCode),
            'ceid' => $ceid,
        ]);

        return "https://news.google.com/rss/search?{$query}";
    }

    private function extractTitle(string $rawTitle): string
    {
        $parts = explode(' - ', $rawTitle);

        return trim($parts[0]);
    }

    private function extractSiteName(string $rawTitle): string
    {
        $parts = explode(' - ', $rawTitle);

        return trim(end($parts));
    }
}
