<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Pages;

use App\Models\Users\User;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\RateLimiter;
use TresPontosTech\Billing\Core\Actions\ResolveQuotaAllowance;
use TresPontosTech\Billing\Core\Enums\CompanyPlanKindEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Vouchers\Actions\RedeemVoucher;
use TresPontosTech\Vouchers\Exceptions\VoucherRedemptionException;
use TresPontosTech\Vouchers\Support\VoucherCodeGenerator;

class RedeemVoucherPage extends Page implements HasForms
{
    use InteractsWithForms;

    public const ATTEMPTS = 5;

    public const DECAY_SECONDS = 300;

    protected static ?string $slug = 'redeem-voucher';

    protected string $view = 'redeem-voucher';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::Ticket;

    protected static ?int $navigationSort = 4;

    public ?string $code = null;

    public static function canAccess(): bool
    {
        return self::voucherProgram() instanceof CompanyPlan;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-app::navigation.groups.platform.label');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-app::pages.redeem_voucher.navigation_label');
    }

    public function getTitle(): string
    {
        return __('panel-app::pages.redeem_voucher.title');
    }

    public function getSubheading(): ?string
    {
        return __('panel-app::pages.redeem_voucher.subheading');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->components([
                TextInput::make('code')
                    ->label(__('panel-app::pages.redeem_voucher.code'))
                    ->helperText(__('panel-app::pages.redeem_voucher.code_hint'))
                    ->maxLength(32)
                    ->required()
                    ->autofocus(),
            ]);
    }

    public function redeem(): void
    {
        $this->validate();

        /** @var User $user */
        $user = auth()->user();

        if (RateLimiter::tooManyAttempts($this->rateKey(), self::ATTEMPTS)) {
            $this->failure(__('panel-app::pages.redeem_voucher.throttled'));

            return;
        }

        try {
            resolve(RedeemVoucher::class)->handle($user, VoucherCodeGenerator::normalize((string) $this->code));
        } catch (VoucherRedemptionException $voucherRedemptionException) {
            RateLimiter::hit($this->rateKey(), self::DECAY_SECONDS);
            $this->failure($voucherRedemptionException->getMessage());

            return;
        }

        RateLimiter::clear($this->rateKey());
        $this->code = null;

        Notification::make()
            ->success()
            ->title(__('panel-app::pages.redeem_voucher.redeemed'))
            ->body(__('panel-app::pages.redeem_voucher.redeemed_body'))
            ->send();

        $this->redirect(UserCreditsPage::getUrl());
    }

    private function failure(string $message): void
    {
        Notification::make()
            ->danger()
            ->title(__('panel-app::pages.redeem_voucher.failed'))
            ->body($message)
            ->send();
    }

    private function rateKey(): string
    {
        return 'redeem-voucher:' . auth()->id();
    }

    private static function voucherProgram(): ?CompanyPlan
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        $plan = resolve(ResolveQuotaAllowance::class)->contractualPlanFor($user);

        return $plan instanceof CompanyPlan && $plan->kind === CompanyPlanKindEnum::CreditsOnly
            ? $plan
            : null;
    }
}
