<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\PanelCompany\Filament\Concerns\ShowsVoucherProgram;
use TresPontosTech\PanelCompany\Filament\Widgets\VoucherCodesStatsWidget;
use TresPontosTech\PanelCompany\Support\PartnerVoucherUrls;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherCode;

/**
 * Cada código da parceira, com o QR que vai na carteirinha e quem o resgatou.
 *
 * O QR aparece só depois que o PDF foi pedido ao menos uma vez — é o mesmo job que o
 * desenha. Antes disso a coluna fica vazia, sem inventar imagem.
 */
class VoucherCodesPage extends Page implements HasTable
{
    use InteractsWithTable;
    use ShowsVoucherProgram;

    protected static ?string $slug = 'voucher-codes';

    protected string $view = 'company-voucher-codes';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::QrCode;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('panel-company::resources.pages.voucher_codes.navigation_label');
    }

    public function getTitle(): string
    {
        return __('panel-company::resources.pages.voucher_codes.title');
    }

    public function getSubheading(): ?string
    {
        return __('panel-company::resources.pages.voucher_codes.subheading');
    }

    protected function getHeaderWidgets(): array
    {
        return [VoucherCodesStatsWidget::class];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->codes())
            ->defaultSort('code')
            ->paginated([25, 50, 100])
            ->columns([
                ImageColumn::make('qr')
                    ->label(__('panel-company::resources.pages.voucher_codes.columns.qr'))
                    ->state(fn (VoucherCode $record): ?string => $record->qrCode() instanceof Media
                        ? PartnerVoucherUrls::qr($record)
                        : null)
                    ->imageSize(56)
                    ->square(),
                TextColumn::make('code')
                    ->label(__('panel-company::resources.pages.voucher_codes.columns.code'))
                    ->fontFamily('mono')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('batch.name')
                    ->label(__('panel-company::resources.pages.voucher_codes.columns.batch')),
                TextColumn::make('redemptions_count')
                    ->label(__('panel-company::resources.pages.voucher_codes.columns.status'))
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => $state > 0
                        ? __('panel-company::resources.pages.voucher_codes.status.redeemed')
                        : __('panel-company::resources.pages.voucher_codes.status.free'))
                    ->color(fn (int $state): string => $state > 0 ? 'gray' : 'success'),
                TextColumn::make('redemptions.user.name')
                    ->label(__('panel-company::resources.pages.voucher_codes.columns.redeemed_by'))
                    ->placeholder('—'),
                TextColumn::make('redemptions.redeemed_at')
                    ->label(__('panel-company::resources.pages.voucher_codes.columns.redeemed_at'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('batch')
                    ->label(__('panel-company::resources.pages.voucher_codes.filters.batch'))
                    ->options(fn (): array => VoucherBatch::query()
                        ->where('company_id', self::partner()->getKey())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => blank($data['value'] ?? null)
                        ? $query
                        : $query->where('voucher_batch_id', $data['value'])),
                TernaryFilter::make('redeemed')
                    ->label(__('panel-company::resources.pages.voucher_codes.filters.redeemed'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where('redemptions_count', '>', 0),
                        false: fn (Builder $query): Builder => $query->where('redemptions_count', 0),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ]);
    }

    /**
     * @return Builder<VoucherCode>
     */
    private function codes(): Builder
    {
        return VoucherCode::query()
            ->whereHas('batch', fn (Builder $query): Builder => $query->where('company_id', self::partner()->getKey()))
            ->with(['batch', 'media', 'redemptions.user']);
    }
}
