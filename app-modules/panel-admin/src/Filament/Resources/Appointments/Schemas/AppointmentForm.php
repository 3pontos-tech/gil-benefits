<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label(__('appointments::resources.appointments.table.columns.user'))
                    ->relationship('user', 'name')
                    ->visibleOn('create')
                    ->required(),
                Select::make('category_type')
                    ->label(__('appointments::resources.appointments.table.columns.category_type'))
                    ->options(fn (): array => collect(AppointmentCategoryEnum::cases())
                        ->mapWithKeys(fn (AppointmentCategoryEnum $case): array => [$case->value => $case->getLabel()])
                        ->all())
                    ->required(),
                ...AppointmentScheduleFields::make(),
                TextInput::make('meeting_url')
                    ->label(__('appointments::resources.appointments.form.meeting_url'))
                    ->dehydrateStateUsing(function (?string $state): ?string {
                        if (blank($state)) {
                            return $state;
                        }

                        $trimmed = trim($state);

                        return str_starts_with(strtolower($trimmed), 'http')
                            ? $trimmed
                            : sprintf('https://%s', $trimmed);
                    }),
            ]);
    }
}
