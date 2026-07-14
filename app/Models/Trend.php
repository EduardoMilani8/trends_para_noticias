<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trend extends Model
{
    protected $fillable = [
        'term',
        'normalized_term',
        'region_id',
        'category_id',
        'period',
        'rank',
        'search_volume',
        'first_seen_at',
        'last_seen_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'is_active' => 'boolean',
            'search_volume' => 'integer',
            'rank' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function trendArticles(): HasMany
    {
        return $this->hasMany(TrendArticle::class);
    }

    public function topArticle(): BelongsTo
    {
        return $this->belongsTo(TrendArticle::class, 'trend_id')
            ->where('position', 1);
    }
}
