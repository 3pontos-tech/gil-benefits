<?php

namespace TresPontosTech\PanelApp\Filament\Pages;

use App\Filament\Shared\Fields\DocumentIdInput;
use App\Filament\Shared\Fields\TaxIdInput;
use App\Models\Users\Detail;
use App\Models\Users\User;
use Closure;
use Filament\Auth\Pages\Register;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Override;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;
use TresPontosTech\Vouchers\Actions\RedeemVoucher;
use TresPontosTech\Vouchers\Exceptions\VoucherRedemptionException;
use TresPontosTech\Vouchers\Support\VoucherCodeGenerator;
use TresPontosTech\Vouchers\Support\VoucherRedemptionUrl;

final class UserRegistration extends Register
{
    #[Override]
    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->components([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            TaxIdInput::make()
                ->rule(fn (Field $component): Closure => $this->uniqueDetailRule('tax_id', $component->getLabel(), withTrashed: true)),
            DocumentIdInput::make()
                ->rule(fn (Field $component): Closure => $this->uniqueDetailRule('document_id', $component->getLabel())),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getVoucherFormComponent(),
        ]);
    }

    /**
     * Quem chega pelo QR da carteirinha já encontra o campo preenchido; quem digitou o
     * endereço na mão preenche à mão. O código é opcional — o cadastro avulso continua
     * igual ao que era.
     */
    private function getVoucherFormComponent(): TextInput
    {
        $fromLink = request()->query(VoucherRedemptionUrl::QUERY_PARAMETER);

        return TextInput::make('voucher')
            ->label(__('panel-app::pages.registration.voucher'))
            ->helperText(__('panel-app::pages.registration.voucher_hint'))
            ->maxLength(32)
            ->default(is_string($fromLink) ? VoucherCodeGenerator::normalize($fromLink) : null)
            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                ? VoucherCodeGenerator::normalize($state)
                : null)
            ->rule(fn (): Closure => $this->redeemableVoucherRule());
    }

    /**
     * Só as regras do código cabem aqui: as que dependem da pessoa não têm como rodar
     * antes de ela existir, e voltam no resgate.
     */
    private function redeemableVoucherRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || blank($value)) {
                return;
            }

            try {
                resolve(RedeemVoucher::class)->ensureRedeemable($value, null);
            } catch (VoucherRedemptionException $voucherRedemptionException) {
                $fail($voucherRedemptionException->getMessage());
            }
        };
    }

    /**
     * Validate uniqueness against the dehydrated (unmasked) value, since the
     * form state is masked but the column stores only digits.
     *
     * The database constraint on (document_id, deleted_at) does not prevent
     * duplicates among active rows (Postgres treats NULL deleted_at as
     * distinct), so this application rule is what actually enforces it.
     * tax_id passes withTrashed to mirror its plain column-level constraint.
     */
    private function uniqueDetailRule(string $column, Htmlable|string|null $attributeLabel, bool $withTrashed = false): Closure
    {
        $label = $attributeLabel instanceof Htmlable ? $attributeLabel->toHtml() : $attributeLabel;

        return static function (string $attribute, mixed $value, Closure $fail) use ($column, $label, $withTrashed): void {
            $normalized = preg_replace('/\D/', '', (string) $value);

            if ($normalized === null || $normalized === '') {
                return;
            }

            $query = Detail::query()->where($column, $normalized);

            if ($withTrashed) {
                $query->withTrashed();
            }

            if ($query->exists()) {
                $fail(__('validation.unique', ['attribute' => $label ?? $attribute]));
            }
        };
    }

    #[Override]
    protected function handleRegistration(array $data): Model
    {
        /** @var User $user */
        $user = parent::handleRegistration($data);
        event(new UserRegistered($user, Roles::Employee));
        $user->detail()->create([
            'tax_id' => $data['tax_id'],
            'document_id' => $data['document_id'],
        ]);

        $this->redeemVoucher($user, $data['voucher'] ?? null);

        return $user;
    }

    /**
     * O cadastro já está feito quando o resgate roda, então uma recusa de última hora
     * (código consumido entre a validação e aqui) vira aviso, não erro de formulário.
     */
    private function redeemVoucher(User $user, ?string $code): void
    {
        if (! is_string($code) || blank($code)) {
            return;
        }

        try {
            resolve(RedeemVoucher::class)->handle($user, $code);
        } catch (VoucherRedemptionException $voucherRedemptionException) {
            Notification::make()
                ->warning()
                ->title(__('panel-app::pages.registration.voucher_failed'))
                ->body($voucherRedemptionException->getMessage())
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title(__('panel-app::pages.registration.voucher_redeemed'))
            ->body(__('panel-app::pages.registration.voucher_redeemed_body'))
            ->send();
    }
}
