<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PricingCalculator extends Component
{
    /**
     * @var list<array{id: int, min: int, max: int, price: float}>
     */
    public array $planTiers = [];

    public int $maxCollaborators = 500;

    public int $minCollaborators = 5;

    public int $seatsCount = 1;

    public function mount(): void
    {
        $this->planTiers = [
            ['id' => 1, 'min' => 1,   'max' => 15,  'price' => 44.90],
            ['id' => 2, 'min' => 16,  'max' => 30,  'price' => 34.90],
            ['id' => 3, 'min' => 31,  'max' => 70,  'price' => 24.90],
            ['id' => 4, 'min' => 71,  'max' => 150, 'price' => 14.90],
            ['id' => 5, 'min' => 151, 'max' => 500, 'price' => 11.90],
        ];

        $this->minCollaborators = min(array_column($this->planTiers, 'min'));
        $this->maxCollaborators = max(array_column($this->planTiers, 'max'));
    }

    public function render(): Factory|View|\Illuminate\View\View
    {
        return view('livewire.pricing-calculator');
    }
}
