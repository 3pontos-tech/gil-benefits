<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Discounts\Actions\RevokeDiscountGrant;
use TresPontosTech\Discounts\Enums\DiscountGrantRevocationReasonEnum;
use TresPontosTech\Discounts\Enums\DiscountGrantStatusEnum;
use TresPontosTech\Discounts\Models\DiscountGrant;

class GrantsRelationManager extends RelationManager
{
    protected static string $relationship = 'grants';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Concessões';
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Nome'),

                TextColumn::make('status')
                    ->label('Situação')
                    ->state(fn (DiscountGrant $record): DiscountGrantStatusEnum => $record->status())
                    ->badge(),

                TextColumn::make('source')
                    ->label('Origem'),

                TextColumn::make('expires_at')
                    ->label('Vale até')
                    ->date('d/m/Y'),

                TextColumn::make('claimed_at')
                    ->label('Ativado em')
                    ->dateTime('d/m/Y H:i'),

                TextColumn::make('plan_slug')
                    ->label('Plano'),

                TextColumn::make('revoked_reason')
                    ->label('Motivo da revogação'),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('Revogar')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (DiscountGrant $record): bool => $record->revoked_at === null)
                    ->action(fn (DiscountGrant $record) => resolve(RevokeDiscountGrant::class)->handle($record, DiscountGrantRevocationReasonEnum::Manual))
                    ->successNotificationTitle('Concessão revogada'),
            ]);
    }
}
