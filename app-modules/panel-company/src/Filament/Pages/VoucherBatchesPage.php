<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Pages;

use App\Models\Users\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\PanelCompany\Filament\Concerns\ShowsVoucherProgram;
use TresPontosTech\PanelCompany\Support\PartnerVoucherUrls;
use TresPontosTech\Vouchers\Actions\RequestVoucherBatchPdf;
use TresPontosTech\Vouchers\Models\VoucherBatch;

/**
 * As campanhas da parceira, com quanto de cada uma já foi para a rua e quanto ainda sobra.
 *
 * O PDF segue o mesmo esquema do admin: se já existe, baixa; se não, entra na fila e a
 * pessoa é avisada quando ficar pronto. Refazer é coisa do admin — a parceira não muda
 * nada que exija refazer a folha.
 */
class VoucherBatchesPage extends Page implements HasTable
{
    use InteractsWithTable;
    use ShowsVoucherProgram;

    protected static ?string $slug = 'voucher-campaigns';

    protected string $view = 'company-voucher-campaigns';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::Megaphone;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('panel-company::resources.pages.voucher_campaigns.navigation_label');
    }

    public function getTitle(): string
    {
        return __('panel-company::resources.pages.voucher_campaigns.title');
    }

    public function getSubheading(): ?string
    {
        return __('panel-company::resources.pages.voucher_campaigns.subheading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->batches())
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('panel-company::resources.pages.voucher_campaigns.columns.name'))
                    ->searchable(),
                TextColumn::make('expires_at')
                    ->label(__('panel-company::resources.pages.voucher_campaigns.columns.expires_at'))
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('codes_count')
                    ->label(__('panel-company::resources.pages.voucher_campaigns.columns.codes'))
                    ->alignEnd(),
                TextColumn::make('redeemed_count')
                    ->label(__('panel-company::resources.pages.voucher_campaigns.columns.redeemed'))
                    ->alignEnd(),
                TextColumn::make('available_count')
                    ->label(__('panel-company::resources.pages.voucher_campaigns.columns.available'))
                    ->state(fn (VoucherBatch $record): int => (int) $record->getAttribute('codes_count') - (int) $record->getAttribute('redeemed_count'))
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->alignEnd(),
            ])
            ->recordActions([
                Action::make('viewCodes')
                    ->label(__('panel-company::resources.pages.voucher_campaigns.actions.view_codes'))
                    ->icon(Heroicon::OutlinedQrCode)
                    ->color('gray')
                    ->url(fn (VoucherBatch $record): string => VoucherCodesPage::getUrl([
                        'tableFilters' => ['batch' => ['value' => $record->getKey()]],
                    ])),
                Action::make('downloadPdf')
                    ->label(fn (VoucherBatch $record): string => $record->pdf() instanceof Media
                        ? __('vouchers::vouchers.pdf.download')
                        : __('vouchers::vouchers.pdf.generate'))
                    ->icon(fn (VoucherBatch $record): Heroicon => $record->pdf() instanceof Media
                        ? Heroicon::OutlinedArrowDownTray
                        : Heroicon::OutlinedDocumentArrowDown)
                    ->action(function (VoucherBatch $record) {
                        if ($record->pdf() instanceof Media) {
                            return redirect()->away(PartnerVoucherUrls::pdf($record));
                        }

                        /** @var User $user */
                        $user = auth()->user();

                        resolve(RequestVoucherBatchPdf::class)->handle($record, $user);

                        return null;
                    }),
            ]);
    }

    /**
     * @return Builder<VoucherBatch>
     */
    private function batches(): Builder
    {
        return VoucherBatch::query()
            ->where('company_id', self::partner()->getKey())
            ->withCount([
                'codes',
                'codes as redeemed_count' => fn (Builder $query): Builder => $query->where('redemptions_count', '>', 0),
            ]);
    }
}
