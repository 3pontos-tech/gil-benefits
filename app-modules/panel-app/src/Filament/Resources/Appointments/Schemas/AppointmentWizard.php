<?php

namespace TresPontosTech\PanelApp\Filament\Resources\Appointments\Schemas;

use App\Filament\Shared\Fields\AppointmentCategorySelector;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Actions\GetAvailableSlotsAction;

class AppointmentWizard
{
    public static function make(): Wizard
    {
        return Wizard::make([
            Step::make(__('appointments::resources.appointments.wizard.steps.category_type'))
                ->icon(Heroicon::User)
                ->schema([
                    AppointmentCategorySelector::make('category_type')
                        ->label(__('appointments::resources.appointments.wizard.labels.category_type'))
                        ->required(),
                ]),
            Step::make(__('appointments::resources.appointments.wizard.steps.pick_datetime'))
                ->icon(Heroicon::Calendar)
                ->schema([
                    DatePicker::make('date')
                        ->label(__('appointments::resources.appointments.wizard.labels.date'))
                        ->required()
                        ->native(false)
                        ->minDate(now()->addDays(2)->format('Y-m-d'))
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('appointment_at', null)),

                    ViewField::make('appointment_at')
                        ->label(__('appointments::resources.appointments.wizard.labels.available_times'))
                        ->view('forms.fields.available-times', [
                            'slots' => fn (Get $get): array => static::availableSlots($get('date')),
                        ])
                        ->required()
                        ->reactive(),

                    TextInput::make('duration')
                        ->label(__('appointments::resources.appointments.wizard.labels.duration'))
                        ->default(__('appointments::resources.appointments.wizard.labels.duration_default'))
                        ->disabled()
                        ->dehydrated(false),
                ]),

            Step::make(__('appointments::resources.appointments.wizard.steps.review_confirm'))
                ->icon(Heroicon::CheckCircle)
                ->schema([
                    ViewField::make('summary')
                        ->label(__('appointments::resources.appointments.wizard.labels.summary'))
                        ->view('forms.fields.appointment-summary'),
                    Textarea::make('notes')->label(__('appointments::resources.appointments.wizard.labels.notes'))->rows(3),
                ]),
        ])
            ->columnSpanFull()
            ->submitAction(Action::make('submit')
                ->label(__('appointments::resources.appointments.wizard.actions.submit'))
                ->icon('heroicon-m-arrow-right')
                ->iconPosition('after')
                ->action('start'));
    }

    /**
     * @return array<string, string>
     */
    public static function availableSlots(?string $date): array
    {
        if (is_null($date)) {
            return [];
        }

        $startDate = Date::parse($date);

        if ($startDate->startOfDay()->isPast()) {
            return [];
        }

        return self::getAvailableTimeSlots($startDate);
    }

    /**
     * @return array<string, string>
     */
    private static function getAvailableTimeSlots(Carbon $startDate): array
    {
        return resolve(GetAvailableSlotsAction::class)->handle($startDate);
    }
}
