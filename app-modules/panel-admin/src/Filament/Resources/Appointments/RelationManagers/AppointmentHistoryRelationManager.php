<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\Appointments\RelationManagers;

use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\View;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;
use TresPontosTech\Appointments\Models\AppointmentHistory;
use TresPontosTech\Consultants\Models\Consultant;

class AppointmentHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    protected static string|BackedEnum|null $icon = Heroicon::ClipboardDocumentList;

    /**
     * Request-scoped cache of consultant id => resolved name (or the unknown-consultant fallback),
     * so a consultant referenced across many history rows is only queried once per render.
     *
     * @var array<string, string>
     */
    private array $consultantNames = [];

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return (string) __('panel-admin::resources.appointments.history.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->heading(__('panel-admin::resources.appointments.history.title'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('action_type')
                    ->label(__('panel-admin::resources.appointments.history.columns.action_type'))
                    ->badge(),
                TextColumn::make('summary')
                    ->label(__('panel-admin::resources.appointments.history.columns.summary'))
                    ->state(fn (AppointmentHistory $record): string => $this->summarize($record))
                    ->wrap(),
                TextColumn::make('author.name')
                    ->label(__('panel-admin::resources.appointments.history.columns.author'))
                    ->description(fn (AppointmentHistory $record): string => $record->actor_type->getLabel())
                    ->searchable()
                    ->placeholder($this->emptyPlaceholder()),
                TextColumn::make('created_at')
                    ->label(__('panel-admin::resources.appointments.history.columns.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                $this->makeViewHistoryAction(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }

    private function makeViewHistoryAction(): ViewAction
    {
        return ViewAction::make()
            ->modalHeading(fn (AppointmentHistory $record): string => $record->action_type->getLabel())
            ->modalIcon(fn (AppointmentHistory $record): Heroicon => $record->action_type->getIcon())
            ->modalIconColor(fn (AppointmentHistory $record): array => $record->action_type->getColor())
            ->schema(fn (AppointmentHistory $record): array => $this->buildViewSchema($record));
    }

    /**
     * @return array<int, View>
     */
    private function buildViewSchema(AppointmentHistory $record): array
    {
        return [
            View::make('panel-admin::components.appointments.history-detail')
                ->viewData([
                    'actionLabel' => $record->action_type->getLabel(),
                    'actionIcon' => $record->action_type->getIcon(),
                    'actionColor' => $record->action_type->getColor(),
                    'adminName' => $record->author->name,
                    'happenedAt' => $record->created_at?->format('d/m/Y H:i') ?? $this->emptyPlaceholder(),
                    'changes' => $this->buildChangeRows($record),
                ]),
        ];
    }

    /**
     * Builds the localised label/value rows that describe what actually changed, resolving
     * consultant ids to names here (in PHP) so the Blade view stays free of queries.
     *
     * @return array<int, array{label: string, value: string}>
     */
    private function buildChangeRows(AppointmentHistory $record): array
    {
        $old = $record->old_values ?? [];
        $new = $record->new_values ?? [];

        return match ($record->action_type) {
            AppointmentHistoryActionType::ConsultantAssigned => [
                $this->row('consultant', $this->resolveConsultantName($new['consultant_id'] ?? null)),
            ],
            AppointmentHistoryActionType::ConsultantLeft => [
                $this->row('consultant', $this->resolveConsultantName($old['consultant_id'] ?? null)),
            ],
            AppointmentHistoryActionType::ConsultantChanged => [
                $this->row('previous_consultant', $this->resolveConsultantName($old['consultant_id'] ?? null)),
                $this->row('current_consultant', $this->resolveConsultantName($new['consultant_id'] ?? null)),
            ],
            AppointmentHistoryActionType::ReScheduled => [
                $this->row('previous_date', $this->formatDate($old['appointment_at'] ?? null)),
                $this->row('new_date', $this->formatDate($new['appointment_at'] ?? null)),
            ],
        };
    }

    private function summarize(AppointmentHistory $record): string
    {
        $old = $record->old_values ?? [];
        $new = $record->new_values ?? [];

        return match ($record->action_type) {
            AppointmentHistoryActionType::ConsultantAssigned => $this->resolveConsultantName($new['consultant_id'] ?? null),
            AppointmentHistoryActionType::ConsultantLeft => $this->resolveConsultantName($old['consultant_id'] ?? null),
            AppointmentHistoryActionType::ConsultantChanged => sprintf(
                '%s → %s',
                $this->resolveConsultantName($old['consultant_id'] ?? null),
                $this->resolveConsultantName($new['consultant_id'] ?? null),
            ),
            AppointmentHistoryActionType::ReScheduled => sprintf(
                '%s → %s',
                $this->formatDate($old['appointment_at'] ?? null),
                $this->formatDate($new['appointment_at'] ?? null),
            ),
        };
    }

    /**
     * @return array{label: string, value: string}
     */
    private function row(string $labelKey, string $value): array
    {
        return [
            'label' => (string) __('panel-admin::resources.appointments.history.labels.' . $labelKey),
            'value' => $value,
        ];
    }

    private function resolveConsultantName(mixed $consultantId): string
    {
        if (! is_string($consultantId) || blank($consultantId)) {
            return $this->emptyPlaceholder();
        }

        return $this->consultantNames[$consultantId] ??= (function () use ($consultantId): string {
            $consultant = Consultant::query()->whereKey($consultantId)->first();

            return $consultant instanceof Consultant
                ? $consultant->name
                : (string) __('panel-admin::resources.appointments.history.placeholders.unknown_consultant');
        })();
    }

    private function formatDate(mixed $value): string
    {
        if (! is_string($value) || blank($value)) {
            return $this->emptyPlaceholder();
        }

        return Date::parse($value)->format('d/m/Y H:i');
    }

    private function emptyPlaceholder(): string
    {
        return (string) __('panel-admin::resources.appointments.history.placeholders.empty');
    }
}
