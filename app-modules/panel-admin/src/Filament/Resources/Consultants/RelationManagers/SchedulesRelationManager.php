<?php

namespace TresPontosTech\Admin\Filament\Resources\Consultants\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Zap\Enums\Frequency;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $title = null;

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('consultants::resources.schedules.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('schedule_type', ScheduleTypes::AVAILABILITY->value))
            ->columns([
                TextColumn::make('name')
                    ->label(__('consultants::resources.schedules.table.columns.name'))
                    ->searchable(),
                TextColumn::make('frequency_config')
                    ->label(__('consultants::resources.schedules.table.columns.days'))
                    ->state(fn (Schedule $record): string => collect($record->frequency_config?->days ?? [])
                        ->map(fn (string $day): string => __('consultants::resources.schedules.days.' . $day))
                        ->join(', ')
                    ),
                TextColumn::make('periods_summary')
                    ->label(__('consultants::resources.schedules.table.columns.periods'))
                    ->state(fn (Schedule $record): string => $record->periods
                        ->map(fn ($p): string => $p->start_time . ' - ' . $p->end_time)
                        ->join(', ')
                    ),
                IconColumn::make('is_active')
                    ->label(__('consultants::resources.schedules.table.columns.active'))
                    ->boolean(),
            ])
            ->headerActions([
                $this->createAvailabilityAction(),
                $this->createBlockedAction(),
            ])
            ->recordActions([
                EditAction::make()
                    ->form($this->availabilityFormSchema())
                    ->mutateRecordDataUsing(function (array $data, Schedule $record): array {
                        $data['frequency_config'] = [
                            'days' => $record->frequency_config?->days ?? [],
                        ];

                        $data['periods'] = $record->periods
                            ->map(fn ($p): array => [
                                'start_time' => $p->start_time,
                                'end_time' => $p->end_time,
                            ])
                            ->values()
                            ->all();

                        return $data;
                    })
                    ->using(function (Schedule $record, array $data): void {
                        $periods = $data['periods'] ?? [];
                        unset($data['periods']);

                        $data['is_recurring'] = true;
                        $data['frequency'] = Frequency::WEEKLY->value;

                        $record->update($data);

                        $record->periods()->delete();

                        foreach ($periods as $period) {
                            $record->periods()->create([
                                'start_time' => $period['start_time'],
                                'end_time' => $period['end_time'],
                                'date' => $record->start_date,
                                'is_available' => true,
                            ]);
                        }
                    }),
                DeleteAction::make(),
            ]);
    }

    private function availabilityFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label(__('consultants::resources.schedules.form.name'))
                ->required()
                ->placeholder(__('consultants::resources.schedules.form.placeholder_name_availability')),

            CheckboxList::make('frequency_config.days')
                ->label(__('consultants::resources.schedules.form.days_of_week'))
                ->options(collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
                    ->mapWithKeys(fn (string $day): array => [$day => __('consultants::resources.schedules.days.' . $day)])
                    ->all())
                ->required()
                ->columns(4),

            Repeater::make('periods')
                ->label(__('consultants::resources.schedules.form.time_periods'))
                ->schema([
                    TextInput::make('start_time')
                        ->label(__('consultants::resources.schedules.form.start'))
                        ->type('time')
                        ->required(),
                    TextInput::make('end_time')
                        ->label(__('consultants::resources.schedules.form.end'))
                        ->type('time')
                        ->required(),
                ])
                ->columns(2)
                ->defaultItems(1)
                ->minItems(1)
                ->required(),
        ];
    }

    private function createAvailabilityAction(): CreateAction
    {
        return CreateAction::make('create_availability')
            ->label(__('consultants::resources.schedules.actions.add_availability'))
            ->form($this->availabilityFormSchema())
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

    private function createBlockedAction(): CreateAction
    {
        return CreateAction::make('create_blocked')
            ->label(__('consultants::resources.schedules.actions.add_blocked'))
            ->form([
                TextInput::make('name')
                    ->label(__('consultants::resources.schedules.form.name'))
                    ->required()
                    ->placeholder(__('consultants::resources.schedules.form.placeholder_name_blocked')),

                DatePicker::make('start_date')
                    ->label(__('consultants::resources.schedules.form.start_date'))
                    ->required()
                    ->native(false),

                DatePicker::make('end_date')
                    ->label(__('consultants::resources.schedules.form.end_date'))
                    ->native(false)
                    ->placeholder(__('consultants::resources.schedules.form.placeholder_end_date')),

                Repeater::make('periods')
                    ->label(__('consultants::resources.schedules.form.time_periods_blocked'))
                    ->schema([
                        TextInput::make('start_time')
                            ->label(__('consultants::resources.schedules.form.start'))
                            ->type('time')
                            ->required(),
                        TextInput::make('end_time')
                            ->label(__('consultants::resources.schedules.form.end'))
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
