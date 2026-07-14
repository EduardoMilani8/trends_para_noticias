<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'geral', 'name' => 'Geral'],
            ['slug' => 'esportes', 'name' => 'Esportes'],
            ['slug' => 'politica', 'name' => 'Política'],
            ['slug' => 'entretenimento', 'name' => 'Entretenimento'],
            ['slug' => 'tecnologia', 'name' => 'Tecnologia'],
            ['slug' => 'economia', 'name' => 'Economia'],
            ['slug' => 'saude', 'name' => 'Saúde'],
            ['slug' => 'ciencia', 'name' => 'Ciência'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
