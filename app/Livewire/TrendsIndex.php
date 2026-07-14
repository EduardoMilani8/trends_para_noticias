<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Region;
use App\Models\Trend;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class TrendsIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $period = '24h';

    #[Url]
    public string $region = '';

    #[Url]
    public string $category = '';

    public function updatedPeriod(): void
    {
        $this->resetPage();
    }

    public function updatedRegion(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Trend::active()
            ->with(['region', 'category', 'topArticle'])
            ->where('period', $this->period);

        if ($this->region !== '') {
            $query->whereHas('region', fn ($q) => $q->where('code', $this->region));
        }

        if ($this->category !== '') {
            $query->whereHas('category', fn ($q) => $q->where('slug', $this->category));
        }

        $trends = $query->orderBy('rank')
            ->paginate(15);

        return view('livewire.trends-index', [
            'trends' => $trends,
            'regions' => Region::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }
}
