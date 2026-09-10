<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\Pages;

use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\VoucherBatchResource;

class ViewVoucherBatch extends ViewRecord
{
    protected static string $resource = VoucherBatchResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label(__('panel-admin::resources.voucher_batches.fields.company')),

                TextEntry::make('name')
                    ->label(__('panel-admin::resources.voucher_batches.fields.name')),

                TextEntry::make('quantity')
                    ->label(__('panel-admin::resources.voucher_batches.fields.codes')),

                TextEntry::make('expires_at')
                    ->label(__('panel-admin::resources.voucher_batches.fields.expires_at'))
                    ->date('d/m/Y')
                    ->placeholder(__('panel-admin::resources.voucher_batches.fields.no_expiry')),

                TextEntry::make('creator.name')
                    ->label(__('panel-admin::resources.voucher_batches.fields.creator'))
                    ->placeholder('—'),

                TextEntry::make('notes')
                    ->label(__('panel-admin::resources.voucher_batches.form.notes'))
                    ->placeholder('—')
                    ->columnSpanFull(),
            ]);
    }
}
