<?php

declare(strict_types=1);

namespace TresPontosTech\App\Filament\Widgets;

use App\Models\Users\User;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;
use TresPontosTech\App\Actions\BuildUserJourneyAction;

class JourneyHeroWidget extends Widget
{
    protected string $view = 'filament.app.widgets.journey-hero';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        return [
            'journey' => resolve(BuildUserJourneyAction::class)($user),
            'firstName' => str($user->name)->trim()->before(' ')->value(),
        ];
    }

    #[On('appointment-cancelled')]
    public function refresh(): void {}
}
