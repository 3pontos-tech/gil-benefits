<?php

namespace TresPontosTech\PanelApp\Filament\Resources\Appointments\Schemas;

use App\Filament\Shared\Fields\AppointmentCategorySelector;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Throwable;
use TresPontosTech\Appointments\Actions\GetAvailableSlotsAction;
use TresPontosTech\Appointments\Models\Appointment;

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
                        ->displayFormat('d/m/Y')
                        ->minDate(now()->addDays(Appointment::BOOKING_LEAD_DAYS)->format('Y-m-d'))
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('appointment_at', null)),

                    ViewField::make('appointment_at')
                        ->label(__('appointments::resources.appointments.wizard.labels.available_times'))
                        ->view('forms.fields.available-times', [
                            'slots' => fn (Get $get): array => static::availableSlots($get('date')),
                        ])
                        ->required()
                        ->reactive(),

                    Placeholder::make('duration')
                        ->label(__('appointments::resources.appointments.wizard.labels.duration'))
                        ->content(__('appointments::resources.appointments.wizard.labels.duration_default')),
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
        if (blank($date)) {
            return [];
        }

        try {
            $startDate = Date::parse($date);
        } catch (Throwable) {
            // A data chega do estado do cliente; lixo vira lista vazia em vez
            // de um 500 do Livewire.
            return [];
        }

        // Espelho da antecedência mínima que os pickers aplicam via minDate:
        // sem isso a regra dos :days dias só existiria no navegador.
        if ($startDate->startOfDay()->lt(now()->addDays(Appointment::BOOKING_LEAD_DAYS)->startOfDay())) {
            return [];
        }

        return self::getAvailableTimeSlots($startDate);
    }

    /**
     * Um horário só é agendável se estiver na lista que o próprio painel
     * oferece. Os argumentos das actions são forjáveis pelo cliente, então os
     * passos de confirmação validam por aqui antes de persistir.
     */
    public static function isBookableSlot(mixed $value): bool
    {
        if (! is_string($value) || blank($value)) {
            return false;
        }

        try {
            $slotAt = Date::parse($value);
        } catch (Throwable) {
            return false;
        }

        return array_key_exists(
            $slotAt->toDateTimeString(),
            self::availableSlots($slotAt->toDateString()),
        );
    }

    /**
     * @return array<string, string>
     */
    private static function getAvailableTimeSlots(Carbon $startDate): array
    {
        return resolve(GetAvailableSlotsAction::class)->handle($startDate);
    }
}
