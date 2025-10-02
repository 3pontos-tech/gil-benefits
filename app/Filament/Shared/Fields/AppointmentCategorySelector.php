<?php

namespace App\Filament\Shared\Fields;

use Filament\Forms\Components\Field;

class AppointmentCategorySelector extends Field
{
    protected string $view = 'livewire.appointment-selector';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(null);
    }
}
