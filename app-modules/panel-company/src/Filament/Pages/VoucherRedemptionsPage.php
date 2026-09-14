<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Pages;

use App\Models\Users\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherRedemption;

/**
 * Quem resgatou os vouchers da campanha desta empresa.
 *
 * Não reaproveita as páginas de Métricas de propósito: elas filtram
 * `appointments.company_id`, que num resgate aponta para o tenant padrão, e agrupam por
 * departamento, que resgatador nenhum tem. O relatório inteiro sai do status do crédito —
 * disponível é quem resgatou e não usou, em uso é quem agendou, usado é consultoria
 * realizada e expirado é quem deixou o prazo passar.
 */
class VoucherRedemptionsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'voucher-redemptions';

    protected string $view = 'company-voucher-redemptions';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::Ticket;

    public static function getNavigationLabel(): string
    {
        return __('panel-company::resources.pages.voucher_redemptions.navigation_label');
    }

    public function getTitle(): string
    {
        return __('panel-company::resources.pages.voucher_redemptions.title');
    }

    public function getSubheading(): ?string
    {
        return __('panel-company::resources.pages.voucher_redemptions.subheading');
    }

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user->isAdmin() && ! $user->isCompanyOwner() && ! $user->isCompanyManager()) {
            return false;
        }

        /** @var Company|null $tenant */
        $tenant = filament()->getTenant();

        return $tenant instanceof Company && VoucherBatch::query()
            ->where('company_id', $tenant->getKey())
            ->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->redemptions())
            ->defaultSort('redeemed_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('panel-company::resources.pages.voucher_redemptions.columns.user'))
                    ->description(fn (VoucherRedemption $record): ?string => $record->user?->email)
                    ->searchable(),
                TextColumn::make('code.code')
                    ->label(__('panel-company::resources.pages.voucher_redemptions.columns.code'))
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('code.batch.name')
                    ->label(__('panel-company::resources.pages.voucher_redemptions.columns.batch')),
                TextColumn::make('redeemed_at')
                    ->label(__('panel-company::resources.pages.voucher_redemptions.columns.redeemed_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('credit_status')
                    ->label(__('panel-company::resources.pages.voucher_redemptions.columns.status'))
                    ->badge()
                    ->state(fn (VoucherRedemption $record): ?UserCreditStatusEnum => $record->credits->first()?->status),
                TextColumn::make('credit_expires_at')
                    ->label(__('panel-company::resources.pages.voucher_redemptions.columns.expires_at'))
                    ->state(fn (VoucherRedemption $record): ?string => $record->credits->first()?->expires_at?->format('d/m/Y'))
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('batch')
                    ->label(__('panel-company::resources.pages.voucher_redemptions.columns.batch'))
                    ->options(fn (): array => $this->batchOptions())
                    ->query(fn (Builder $query, array $data): Builder => blank($data['value'] ?? null)
                        ? $query
                        : $query->whereHas('code', fn (Builder $inner): Builder => $inner->where('voucher_batch_id', $data['value']))),
                SelectFilter::make('credit_status')
                    ->label(__('panel-company::resources.pages.voucher_redemptions.columns.status'))
                    ->options(UserCreditStatusEnum::class)
                    ->query(fn (Builder $query, array $data): Builder => blank($data['value'] ?? null)
                        ? $query
                        : $query->whereHas('credits', fn (Builder $inner): Builder => $inner->where('status', $data['value']))),
            ]);
    }

    /**
     * @return Builder<VoucherRedemption>
     */
    private function redemptions(): Builder
    {
        /** @var Company $tenant */
        $tenant = filament()->getTenant();

        return VoucherRedemption::query()
            ->whereHas(
                'code.batch',
                fn (Builder $query): Builder => $query->where('company_id', $tenant->getKey()),
            )
            ->with(['user', 'code.batch', 'credits']);
    }

    /**
     * @return array<string, string>
     */
    private function batchOptions(): array
    {
        /** @var Company $tenant */
        $tenant = filament()->getTenant();

        return VoucherBatch::query()
            ->where('company_id', $tenant->getKey())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
