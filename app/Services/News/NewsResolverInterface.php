<?php

namespace App\Services\News;

interface NewsResolverInterface
{
    /**
     * Find the top article for a given search term and region via Google News RSS.
     *
     * @return array{url: string, title: string, site_name: string, published_at: ?string}|null
     */
    public function findTopArticle(string $term, string $regionCode): ?array;
}
