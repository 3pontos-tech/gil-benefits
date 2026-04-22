<?php

namespace App\Filament\Shared\Fields;

use Closure;
use Filament\Forms\Components\Field;
use TresPontosTech\Consultants\Models\Consultant;

class ConsultantSelector extends Field
{
    protected string $view = 'livewire.consultant-selector';

    /**
     * @var array<int|string, mixed>|Closure
     */
    protected array|Closure $consultants = [];

    /**
     * @param  array<int|string, mixed>|Closure  $consultants
     */
    public function consultants(array|Closure $consultants): static
    {
        $this->consultants = $consultants;

        return $this;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getConsultants(): array
    {
        $evaluated = $this->evaluate($this->consultants);

        if (is_array($evaluated)) {
            return $evaluated;
        }

        return Consultant::all()->toArray();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(null);
    }
}
