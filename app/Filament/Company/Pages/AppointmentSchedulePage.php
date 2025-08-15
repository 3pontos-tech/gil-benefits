<?php

namespace App\Filament\Company\Pages;

use Filament\Schemas\Components\Wizard\Step;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class AppointmentSchedulePage extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected string $view = 'filament.company.pages.appointment-schedule-page';

    protected static ?string $navigationLabel = 'Appointment Schedule';

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::CalendarDays;

    public function testAction(): Action
    {
        return Action::make('test')
            ->requiresConfirmation()
            ->action(function (array $arguments): void {
                Notification::make()
                    ->title('AINNNNNNNNNNNNN ZÉ DA MANGA')
                    ->success()
                    ->send();
            });
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Wizard::make()
                    ->skippable()
                    ->submitAction(new HtmlString(<<<'BLADE'
                        <button wire:click="mountAction('test')" type="submit">Submit</button>
                        BLADE
                    ))
                    ->schema([
                        Step::make('Step 1')
                            ->schema([
                                TextInput::make('name')
                                    ->required(),
                            ]),
                        Step::make('Step 2')
                            ->schema([
                                TextInput::make('name')
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
