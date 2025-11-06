<?php

namespace App\Livewire;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PricingCalculator extends Component
{
    public $collaborators = 1;

    public $planTiers = [];

    public $maxCollaborators = 0;

    public $minCollaborators = 0;

    public function mount(): void
    {
        $this->planTiers = [
            ['id' => 1, 'min' => 1,   'max' => 15,  'price' => 44.90],
            ['id' => 2, 'min' => 16,  'max' => 30,  'price' => 34.90],
            ['id' => 3, 'min' => 31,  'max' => 70,  'price' => 24.90],
            ['id' => 4, 'min' => 71,  'max' => 150, 'price' => 14.90],
            ['id' => 5, 'min' => 151, 'max' => 151, 'price' => 11.90],
        ];

        $this->minCollaborators = collect($this->planTiers)
            ->pluck('min')
            ->min();

        $this->maxCollaborators = collect($this->planTiers)
            ->pluck('max')
            ->max();
    }

    public function render(): Factory|View|\Illuminate\View\View
    {
        return view('livewire.pricing-calculator');
    }
}
