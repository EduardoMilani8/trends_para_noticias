<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['code' => 'BR', 'name' => 'Brasil'],
            ['code' => 'US', 'name' => 'Estados Unidos'],
            ['code' => 'PT', 'name' => 'Portugal'],
            ['code' => 'AR', 'name' => 'Argentina'],
        ];

        foreach ($regions as $region) {
            Region::updateOrCreate(
                ['code' => $region['code']],
                $region,
            );
        }
    }
}
