<?php

namespace TresPontosTech\Appointments\Filament\App\Resources\Appointments\Schemas;

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
use TresPontosTech\IntegrationHighlevel\HighLevelClient;
use TresPontosTech\IntegrationHighlevel\Requests\FetchCalendarSlotsDTO;

class AppointmentWizard
{
    public static function make(): Wizard
    {
        return Wizard::make([
            Step::make(__('appointments::resources.appointments.wizard.steps.consultant'))
                ->icon(Heroicon::User)
                ->schema([
                    AppointmentCategorySelector::make('category_type')
                        ->label(__('appointments::resources.appointments.wizard.labels.choose_consultant'))
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
                    Textarea::make('note')->label(__('appointments::resources.appointments.wizard.labels.notes'))->rows(3),
                ]),
        ])
            ->columnSpanFull()
            ->submitAction(Action::make('submit')
                ->label(__('appointments::resources.appointments.wizard.actions.submit'))
                ->icon('heroicon-m-arrow-right')
                ->iconPosition('after')
                ->action('start'));
    }

    public static function availableSlots(?string $date): array
    {
        if (is_null($date)) {
            return [];
        }

        $startDate = Date::parse($date);

        if ($startDate->diffInDays(now()) > 0) {

            return [];
        }

        sprintf('available_slots_for_%s_%s', $date, auth()->user()->id);

        return self::getAvailableTimeSlots($startDate);

    }

    private static function getAvailableTimeSlots(Carbon $startDate): array
    {
        $endDate = $startDate->clone()->endOfDay();
        $response = app(HighLevelClient::class)
            ->getCalendarFreeSlots(FetchCalendarSlotsDTO::make($startDate, $endDate));

        $formattedDate = $startDate->format('Y-m-d');

        $response = $response[$formattedDate]['slots'];

        return collect($response)
            ->mapWithKeys(fn ($slot): array => [
                $slot => Date::parse($slot)->format('H:i'),
            ])->all();
    }
}
