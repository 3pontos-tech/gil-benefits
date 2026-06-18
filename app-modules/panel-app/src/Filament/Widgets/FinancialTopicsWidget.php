<?php

declare(strict_types=1);

namespace TresPontosTech\App\Filament\Widgets;

use App\Models\Users\User;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;
use TresPontosTech\App\Actions\BuildUserJourneyAction;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;

class FinancialTopicsWidget extends Widget
{
    protected string $view = 'filament.app.widgets.financial-topics';

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 3];

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        return [
            'categories' => AppointmentCategoryEnum::cases(),
            'journey' => resolve(BuildUserJourneyAction::class)($user),
        ];
    }

    #[On('appointment-cancelled')]
    public function refresh(): void {}
}
