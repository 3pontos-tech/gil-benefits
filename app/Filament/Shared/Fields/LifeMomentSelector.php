<?php

declare(strict_types=1);

namespace App\Filament\Shared\Fields;

use Filament\Forms\Components\Field;

class LifeMomentSelector extends Field
{
    protected string $view = 'livewire.life-moment-selector';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(null);
    }
}
