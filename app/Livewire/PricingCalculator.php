<?php

namespace App\Livewire;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PricingCalculator extends Component
{
    public function render(): Factory|View|\Illuminate\View\View
    {
        return view('livewire.pricing-calculator');
    }
}
