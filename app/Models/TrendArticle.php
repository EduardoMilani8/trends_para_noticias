<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrendArticle extends Model
{
    protected $fillable = [
        'trend_id',
        'url',
        'site_name',
        'title',
        'published_at',
        'position',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'fetched_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    public function trend(): BelongsTo
    {
        return $this->belongsTo(Trend::class);
    }
}
