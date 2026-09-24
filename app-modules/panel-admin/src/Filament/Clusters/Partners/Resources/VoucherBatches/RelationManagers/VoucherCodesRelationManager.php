<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Vouchers\Models\VoucherCode;

class VoucherCodesRelationManager extends RelationManager
{
    protected static string $relationship = 'codes';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('panel-admin::resources.voucher_batches.codes.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
                TextColumn::make('code')
                    ->label(__('panel-admin::resources.voucher_batches.codes.code'))
                    ->copyable()
                    ->searchable()
                    ->fontFamily('mono'),

                TextColumn::make('redemptions_count')
                    ->label(__('panel-admin::resources.voucher_batches.codes.redemptions'))
                    ->formatStateUsing(fn (int $state, VoucherCode $record): string => sprintf('%d/%d', $state, $record->max_redemptions)),

                TextColumn::make('redemptions.user.name')
                    ->label(__('panel-admin::resources.voucher_batches.codes.redeemed_by'))
                    ->placeholder(__('panel-admin::resources.voucher_batches.codes.not_redeemed'))
                    ->badge(),

                TextColumn::make('redemptions.redeemed_at')
                    ->label(__('panel-admin::resources.voucher_batches.codes.redeemed_at'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->filters([
                TernaryFilter::make('redeemed')
                    ->label(__('panel-admin::resources.voucher_batches.codes.filter_redeemed'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where('redemptions_count', '>', 0),
                        false: fn (Builder $query): Builder => $query->where('redemptions_count', 0),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->defaultSort('code')
            ->paginated([25, 50, 100]);
    }
}
