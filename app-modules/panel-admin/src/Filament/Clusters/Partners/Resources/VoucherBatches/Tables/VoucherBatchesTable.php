<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\Tables;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Vouchers\Jobs\GenerateVoucherBatchPdfJob;
use TresPontosTech\Vouchers\Models\VoucherBatch;

class VoucherBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount([
                'codes',
                'codes as redeemed_codes_count' => fn (Builder $codes): Builder => $codes->where('redemptions_count', '>', 0),
            ]))
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('panel-admin::resources.voucher_batches.fields.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('company.name')
                    ->label(__('panel-admin::resources.voucher_batches.fields.company'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label(__('panel-admin::resources.voucher_batches.fields.name'))
                    ->searchable(),

                TextColumn::make('codes_count')
                    ->label(__('panel-admin::resources.voucher_batches.fields.codes'))
                    ->numeric(),

                TextColumn::make('redeemed_codes_count')
                    ->label(__('panel-admin::resources.voucher_batches.fields.redeemed'))
                    ->badge()
                    ->color('success'),

                TextColumn::make('used_credits_count')
                    ->label(__('panel-admin::resources.voucher_batches.fields.used'))
                    ->state(fn (VoucherBatch $record): int => self::creditsCount($record, [UserCreditStatusEnum::InUse, UserCreditStatusEnum::Used])),

                TextColumn::make('expired_credits_count')
                    ->label(__('panel-admin::resources.voucher_batches.fields.expired'))
                    ->state(fn (VoucherBatch $record): int => self::creditsCount($record, [UserCreditStatusEnum::Expired]))
                    ->badge()
                    ->color('danger'),

                TextColumn::make('expires_at')
                    ->label(__('panel-admin::resources.voucher_batches.fields.expires_at'))
                    ->date('d/m/Y')
                    ->placeholder(__('panel-admin::resources.voucher_batches.fields.no_expiry'))
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label(__('panel-admin::resources.voucher_batches.fields.creator'))
                    ->placeholder('—')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('company')
                    ->label(__('panel-admin::resources.voucher_batches.fields.company'))
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('expired')
                    ->label(__('panel-admin::resources.voucher_batches.filters.expired'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('expires_at')->where('expires_at', '<', now()),
                        false: fn (Builder $query): Builder => $query->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now())),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                self::downloadPdfAction(),
            ]);
    }

    public static function downloadPdfAction(): Action
    {
        return Action::make('downloadPdf')
            ->label(__('panel-admin::resources.voucher_batches.actions.download_pdf'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->action(function (VoucherBatch $record): void {
                /** @var User $user */
                $user = auth()->user();

                dispatch(new GenerateVoucherBatchPdfJob($record->getKey(), $user->getKey()));

                Notification::make()
                    ->info()
                    ->title(__('panel-admin::resources.voucher_batches.actions.pdf_queued_title'))
                    ->body(__('panel-admin::resources.voucher_batches.actions.pdf_queued_body'))
                    ->send();
            });
    }

    /**
     * @param  list<UserCreditStatusEnum>  $statuses
     */
    private static function creditsCount(VoucherBatch $record, array $statuses): int
    {
        return $record->creditsQuery()->whereIn('status', $statuses)->count();
    }
}
