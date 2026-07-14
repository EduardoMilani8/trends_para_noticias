<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];

    public function trends(): HasMany
    {
        return $this->hasMany(Trend::class);
    }
}
