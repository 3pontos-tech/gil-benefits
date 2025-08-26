<?php

namespace App\Livewire;

use App\Models\Consultant;
use Closure;
use Filament\Forms\Components\Field;

class ConsultantSelector extends Field
{
    protected string $view = 'livewire.consultant-selector';

    protected array|Closure $consultants = [];

    public function consultants(array|Closure $consultants): static
    {
        $this->consultants = $consultants;

        return $this;
    }

    public function getConsultants(): array
    {
        return $this->evaluate($this->consultants) ?? Consultant::all()->toArray();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(null);
    }
}
