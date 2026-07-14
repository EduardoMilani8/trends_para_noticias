<?php

namespace App\Services\Trends;

use App\Exceptions\SerpApiRequestException;

class FakeTrendsClient implements TrendsClientInterface
{
    private array $results = [];

    private ?SerpApiRequestException $exception = null;

    public function stubSuccess(array $results): void
    {
        $this->results = $results;
        $this->exception = null;
    }

    public function stubError(string $message = 'Fake API error'): void
    {
        $this->exception = new SerpApiRequestException($message);
        $this->results = [];
    }

    public function trendingNow(string $regionCode): array
    {
        if ($this->exception) {
            throw $this->exception;
        }

        return $this->results;
    }
}
