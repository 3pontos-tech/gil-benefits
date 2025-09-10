<?php

namespace App\Filament\App\Resources\Appointments\Schemas;

use App\Clients\HighLevelClient;
use App\Clients\Requests\FetchCalendarSlotsDTO;
use App\Enums\VoucherStatusEnum;
use App\Livewire\ConsultantSelector;
use App\Models\Consultant;
use App\Models\Voucher;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;

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
                        ->native(false)
                        ->minDate(now()->format('Y-m-d'))
                        ->reactive()
                        ->afterStateUpdated(fn(callable $set) => $set('time', null)),

                    ViewField::make('time')
                        ->label('Horários Disponíveis')
                        ->view('forms.fields.available-times', [
                            'slots' => fn(Get $get): array => static::availableSlots($get('date')),
                        ])
                        ->required()
                        ->reactive(),

                    TextInput::make('duration')
                        ->label('Duration')
                        ->default('60 minutes')
                        ->disabled()
                        ->dehydrated(false),
                ]),

            Step::make('Apply Voucher')
                ->icon(Heroicon::Ticket)
                ->beforeValidation(function (Get $get) {
                    $voucher = Voucher::query()->find($get('voucher_id'));
                    if (!$voucher) {
                        Notification::make()
                            ->warning()
                            ->title('Voucher is not active')
                            ->send();

                        throw new Halt();
                    }
                })
                ->schema([
                    Hidden::make('voucher_id')
                        ->label('Voucher')
                        ->afterStateUpdated(fn(Set $set) => $set('time', null))
                        ->default(Voucher::query()
                            ->where('company_id', filament()->getTenant()->getKey())
                            ->where('user_id', auth()->user()->getKey())
                            ->where('status', VoucherStatusEnum::Active)
                            ->whereDate('valid_until', '>=', today())
                            ->first()
                            ?->getKey() ?? null),
                    ViewField::make('voucher')
                        ->view('forms.fields.available-voucher', [
                            'voucher' => fn(Get $get): ?Voucher => Voucher::query()->find($get('voucher_id')),
                        ])
                ]),

            Step::make('Review & Confirm')
                ->icon(Heroicon::CheckCircle)
                ->schema([
                    ViewField::make('summary')
                        ->view('forms.fields.appointment-summary'),
                    Textarea::make('note')->label('Notes')->rows(3),
                ]),
        ])
            ->columnSpanFull()
            ->statePath('formData')
            ->submitAction(Action::make('submit')
                ->label('Start researching')
                ->icon('heroicon-m-arrow-right')
                ->iconPosition('after')
                ->action('start'))
            ->hiddenHeader();
    }

    public static function availableSlots(?string $date): array
    {
        if (is_null($date)) return [];


        $startDate = Carbon::parse($date);

        if ($startDate->diffInDays(now()) > 0) {

            return [];
        }


        $key = sprintf('available_slots_for_%s_%s', $date, auth()->user()->id);
        return Cache::remember($key, now()->addMinutes(10), function () use ($startDate) {
            $response = app(HighLevelClient::class)
                ->getCalendarFreeSlots(FetchCalendarSlotsDTO::make($startDate, $startDate));

            $formattedDate = $startDate->format('Y-m-d');
            $response = $response[$formattedDate]['slots'];

            return collect($response)
                ->mapWithKeys(fn($slot) => [
                    $slot => Carbon::parse($slot)->format('H:i'),
                ])->toArray();

        });

    }
}
