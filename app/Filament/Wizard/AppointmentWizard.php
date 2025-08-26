<?php

namespace App\Filament\Wizard;

use App\Livewire\ConsultantSelector;
use App\Models\Consultant;
use App\Models\Voucher;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;

class AppointmentWizard
{
    public static function make(): Wizard
    {
        return Wizard::make([
            Step::make('Consultant')
                ->icon(Heroicon::User)
                ->schema([
                    View::make('filament.app.layout.choose-consultant-step-layout')
                        ->schema([
                            ConsultantSelector::make('consultant_id')
                                ->label('Choose your consultant')
                                ->required()
                                ->consultants(function () {
                                    return Consultant::all()
                                        ->map(function ($consultant): array {
                                            return [
                                                'id' => $consultant->id,
                                                'name' => $consultant->name,
                                                'description' => $consultant->description,
                                                'phone' => $consultant->phone,
                                                'email' => $consultant->email,
                                            ];
                                        })
                                        ->toArray();
                                }),
                        ]),
                ]),
            Step::make('Pick Date & Time')
                ->icon(Heroicon::Calendar)
                ->schema([
                    DatePicker::make('date')
                        ->label('Date')
                        ->required()
                        ->afterOrEqual(today())
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('time', null)),

                    ViewField::make('time')
                        ->label('Available Times')
                        ->view('forms.fields.available-times', [
                            'slots' => fn (Get $get): array => static::availableSlots($get('date')),
                        ])
                        ->dehydrated(true),

                    TextInput::make('duration')
                        ->label('Duration')
                        ->default('60 minutes')
                        ->disabled()
                        ->dehydrated(false),
                ]),

            Step::make('Apply Voucher')
                ->icon(Heroicon::Ticket)
                ->schema([
                    Select::make('voucher_id')
                        ->label('Voucher')
                        ->options(function () {
                            return Voucher::query()
                                ->where('user_id', auth()->user()->id)
                                ->where('status', 'active')
                                ->whereDate('valid_until', '>=', today())
                                ->pluck('code', 'id');
                        })
                        ->searchable()
                        ->placeholder('No voucher, pay later'),
                ]),

            Step::make('Review & Confirm')
                ->icon(Heroicon::CheckCircle)
                ->schema([
                    ViewField::make('summary')
                        ->view('forms.fields.appointment-summary'),
                    Textarea::make('note')->label('Notes')->rows(3),
                ]),
        ])
            ->submitAction(Action::make('vai-caralho')->action(fn (): Notification => Notification::make()->title('Appointment booked successfully!')->success()->send()))
            ->hiddenHeader(true);
    }

    public static function availableSlots(?string $date): array
    {
        if ($date === null || $date === '' || $date === '0') {
            return [];
        }

        $start = now()->setTime(9, 0);
        $end = now()->setTime(17, 0);

        $slots = [];
        while ($start < $end) {
            $time = $start->format('H:i');
            // Aqui você poderia checar se já existe Appointment marcado nesse horário
            $slots[$time] = $time;
            $start->addHour();
        }

        return $slots;
    }
}
