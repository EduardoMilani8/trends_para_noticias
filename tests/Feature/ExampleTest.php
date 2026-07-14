<?php

namespace Tests\Feature;

use App\Livewire\TrendsIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        Livewire::test(TrendsIndex::class)
            ->assertStatus(200);
    }
}
