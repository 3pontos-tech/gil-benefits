<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Widgets;

use App\Models\Users\User;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;
use TresPontosTech\PanelApp\Actions\BuildUserJourneyAction;
use TresPontosTech\PanelApp\Filament\Pages\AnamneseWizardPage;

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
            'displayName' => str($user->name)->trim()->value(),
            'anamneseUrl' => AnamneseWizardPage::getUrl(),
        ];
    }

    #[On('appointment-cancelled')]
    public function refresh(): void {}
}
