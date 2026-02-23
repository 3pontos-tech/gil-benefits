<?php

namespace TresPontosTech\Consultants\Filament\Admin\Resources\Consultants\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Zap\Enums\Frequency;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $title = 'Schedules';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('schedule_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (ScheduleTypes $state): string => match ($state) {
                        ScheduleTypes::AVAILABILITY => 'success',
                        ScheduleTypes::BLOCKED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('frequency_config')
                    ->label('Days')
                    ->state(fn (Schedule $record): string => collect($record->frequency_config?->days ?? [])
                        ->map(fn (string $day): string => ucfirst(substr($day, 0, 3)))
                        ->join(', ')
                    ),
                TextColumn::make('periods_summary')
                    ->label('Periods')
                    ->state(fn (Schedule $record): string => $record->periods
                        ->map(fn ($p): string => $p->start_time . ' - ' . $p->end_time)
                        ->join(', ')
                    ),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->headerActions([
                self::createAvailabilityAction(),
                self::createBlockedAction(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }

    private static function createAvailabilityAction(): CreateAction
    {
        return CreateAction::make('create_availability')
            ->label('Add Availability')
            ->form([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->placeholder('e.g. Office Hours'),

                CheckboxList::make('frequency_config.days')
                    ->label('Days of Week')
                    ->options([
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                        'sunday' => 'Sunday',
                    ])
                    ->required()
                    ->columns(4),

                Repeater::make('periods')
                    ->label('Time Periods')
                    ->schema([
                        TextInput::make('start_time')
                            ->label('Start')
                            ->type('time')
                            ->required(),
                        TextInput::make('end_time')
                            ->label('End')
                            ->type('time')
                            ->required(),
                    ])
                    ->columns(2)
                    ->defaultItems(1)
                    ->minItems(1)
                    ->required(),
            ])
            ->mutateFormDataUsing(function (array $data): array {
                $data['_periods'] = $data['periods'] ?? [];
                unset($data['periods']);

                $data['schedule_type'] = ScheduleTypes::AVAILABILITY->value;
                $data['start_date'] = now()->toDateString();
                $data['is_recurring'] = true;
                $data['frequency'] = Frequency::WEEKLY->value;
                $data['is_active'] = true;

                return $data;
            })
            ->after(function (Schedule $record, array $data): void {
                foreach ($data['_periods'] ?? [] as $period) {
                    $record->periods()->create([
                        'start_time' => $period['start_time'],
                        'end_time' => $period['end_time'],
                        'date' => $record->start_date,
                        'is_available' => true,
                    ]);
                }
            });
    }

    private static function createBlockedAction(): CreateAction
    {
        return CreateAction::make('create_blocked')
            ->label('Add Blocked Time')
            ->form([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->placeholder('e.g. Vacation, Holiday'),

                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->required()
                    ->native(false),

                DatePicker::make('end_date')
                    ->label('End Date')
                    ->native(false)
                    ->placeholder('Leave empty for single day'),

                Repeater::make('periods')
                    ->label('Time Periods (leave empty to block full day)')
                    ->schema([
                        TextInput::make('start_time')
                            ->label('Start')
                            ->type('time')
                            ->required(),
                        TextInput::make('end_time')
                            ->label('End')
                            ->type('time')
                            ->required(),
                    ])
                    ->columns(2)
                    ->defaultItems(0),
            ])
            ->mutateFormDataUsing(function (array $data): array {
                $data['_periods'] = $data['periods'] ?? [];
                unset($data['periods']);

                if (empty($data['_periods'])) {
                    $data['_periods'] = [['start_time' => '00:00', 'end_time' => '23:59']];
                }

                $data['schedule_type'] = ScheduleTypes::BLOCKED->value;
                $data['end_date'] = $data['end_date'] ?? $data['start_date'];
                $data['is_recurring'] = false;
                $data['is_active'] = true;

                return $data;
            })
            ->after(function (Schedule $record, array $data): void {
                foreach ($data['_periods'] ?? [] as $period) {
                    $record->periods()->create([
                        'start_time' => $period['start_time'],
                        'end_time' => $period['end_time'],
                        'date' => $record->start_date,
                        'is_available' => false,
                    ]);
                }
            });
    }
}
