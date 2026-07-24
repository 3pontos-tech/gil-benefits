<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Section::make(__('panel-admin::resources.appointments.sections.user'))
                            ->columns(2)
                            ->schema([
                                Select::make('user_id')
                                    ->label(__('appointments::resources.appointments.table.columns.user'))
                                    ->relationship('user', 'name')
                                    ->visibleOn('create')
                                    ->searchable()
                                    ->required(),
                                Select::make('category_type')
                                    ->label(__('appointments::resources.appointments.table.columns.category_type'))
                                    ->options(fn (): array => collect(AppointmentCategoryEnum::cases())
                                        ->mapWithKeys(fn (AppointmentCategoryEnum $case): array => [$case->value => $case->getLabel()])
                                        ->all())
                                    ->required(),
                            ]),
                        Section::make(__('panel-admin::resources.appointments.sections.scheduling'))
                            ->columns(2)
                            ->schema(AppointmentScheduleFields::make()),
                        Section::make(__('panel-admin::resources.appointments.sections.details'))
                            ->visibleOn('edit')
                            ->schema([
                                TextInput::make('meeting_url')
                                    ->label(__('appointments::resources.appointments.form.meeting_url'))
                                    ->visibleOn('edit')
                                    ->dehydrateStateUsing(function (?string $state): ?string {
                                        if (blank($state)) {
                                            return $state;
                                        }

                                        $trimmed = trim($state);

                                        return str_starts_with(strtolower($trimmed), 'http')
                                            ? $trimmed
                                            : sprintf('https://%s', $trimmed);
                                    }),
                            ]),
                    ]),
            ]);
    }
}
