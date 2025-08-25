<?php

namespace App\Filament\Wizard;

use App\Models\Consultant;
use App\Models\Voucher;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;

class AppointmentWizard
{
    public static function make(): Wizard
    {
        return Wizard::make([

            Step::make('Choose Consultant')
                ->icon(Heroicon::User)
                ->description('Select your preferred financial advisor')
                ->schema([
                    TextColumn::make('Choose Consultant'),
                    Select::make('consultant_id')
                        ->label('Consultant')
                        ->options(Consultant::pluck('name', 'id'))
                        ->required(),
                ]),

            Step::make('Pick Date & Time')
                ->description("Choose when you'd like to meet")
                ->schema([
                    DatePicker::make('date')
                        ->label('Date')
                        ->required()
                        ->afterOrEqual(today())
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('time', null)),

                    // slots de 1h -> você pode gerar dinamicamente
                    Radio::make('time')
                        ->label('Available Times')
                        ->options(fn ($get) => static::availableSlots($get('date')))
                        ->required(),

                    ViewField::make('duration')
                        ->view('forms.fields.fixed-duration') // blade com "60 minutes"
                        ->dehydrated(false),
                ]),

            Step::make('Apply Voucher')
                ->description('Use a voucher or pay later')
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
                ->description('Confirm your appointment details')
                ->schema([
                    ViewField::make('summary')
                        ->view('forms.fields.appointment-summary'), // Blade que mostra dados escolhidos
                    Textarea::make('note')->label('Notes')->rows(3),
                ]),
        ]);
    }

    protected static function availableSlots(?string $date): array
    {
        if (! $date) {
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
