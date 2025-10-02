<?php

namespace TresPontosTech\Appointments\Filament\App\Resources\Appointments\Schemas;

use App\Enums\VoucherStatusEnum;
use App\Filament\Shared\Fields\AppointmentCategorySelector;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\IntegrationHighlevel\HighLevelClient;
use TresPontosTech\IntegrationHighlevel\Requests\FetchCalendarSlotsDTO;
use TresPontosTech\Vouchers\Models\Voucher;

class AppointmentWizard
{
    public static function make(): Wizard
    {
        return Wizard::make([
            Step::make('Consultant')
                ->icon(Heroicon::User)
                ->schema([
                    AppointmentCategorySelector::make('category_type')
                        ->label('Choose your consultant')
                        ->required(),
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
                        ->afterStateUpdated(fn (callable $set) => $set('appointment_at', null)),

                    ViewField::make('appointment_at')
                        ->label('Horários Disponíveis')
                        ->view('forms.fields.available-times', [
                            'slots' => fn (Get $get): array => static::availableSlots($get('date')),
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
                ->beforeValidation(function (Get $get): void {
                    $voucher = $get('voucher_id');
                    if (is_null($voucher)) {
                        Notification::make()
                            ->warning()
                            ->title('Voucher is not active')
                            ->send();

                        throw new Halt;
                    }
                })
                ->schema([
                    ViewField::make('voucher_id')
                        ->formatStateUsing(fn () => Voucher::query()
                            ->where('company_id', filament()->getTenant()->getKey())
                            ->where('user_id', auth()->user()->getKey())
                            ->where('status', VoucherStatusEnum::Pending)
                            ->first()
                            ?->getKey()
                        )
                        ->view('forms.fields.available-voucher'),
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
            ->submitAction(Action::make('submit')
                ->label('Start researching')
                ->icon('heroicon-m-arrow-right')
                ->iconPosition('after')
                ->action('start'));
    }

    public static function availableSlots(?string $date): array
    {
        if (is_null($date)) {
            return [];
        }

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
                ->mapWithKeys(fn ($slot): array => [
                    $slot => Carbon::parse($slot)->format('H:i'),
                ])->toArray();

        });

    }
}
