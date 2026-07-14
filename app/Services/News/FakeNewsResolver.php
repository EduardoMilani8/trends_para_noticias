<?php

namespace App\Services\News;

class FakeNewsResolver implements NewsResolverInterface
{
    private ?array $result = null;

    private bool $returnNull = true;

    public function stubResult(array $article): void
    {
        $this->result = $article;
        $this->returnNull = false;
    }

    public function stubEmpty(): void
    {
        $this->result = null;
        $this->returnNull = true;
    }

    public function findTopArticle(string $term, string $regionCode): ?array
    {
        if ($this->returnNull) {
            return null;
        }

        return $this->result;
    }
}
