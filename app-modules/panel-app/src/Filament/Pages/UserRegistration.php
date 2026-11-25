<?php

namespace TresPontosTech\PanelApp\Filament\Pages;

use App\Filament\Shared\Fields\DocumentIdInput;
use App\Filament\Shared\Fields\TaxIdInput;
use App\Filament\Shared\Pages\RegisterPage;
use App\Http\Middleware\StoreSignupAttribution;
use App\Models\Users\Detail;
use App\Models\Users\User;
use App\Support\PostHog\PostHog;
use Closure;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Override;
use TresPontosTech\Discounts\Actions\ClaimDiscountGrant;
use TresPontosTech\Discounts\Actions\VerifyDiscountGrantTaxId;
use TresPontosTech\Discounts\Enums\DiscountTaxIdCheckEnum;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Support\DiscountInviteUrl;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;
use TresPontosTech\Vouchers\Actions\RedeemVoucher;
use TresPontosTech\Vouchers\Exceptions\VoucherRedemptionException;
use TresPontosTech\Vouchers\Support\VoucherCodeGenerator;
use TresPontosTech\Vouchers\Support\VoucherRedemptionUrl;

final class UserRegistration extends RegisterPage
{
    #[Locked]
    public ?string $discountGrantId = null;

    private ?DiscountGrant $claimedDiscountGrant = null;

    #[Override]
    public function mount(): void
    {
        $grantId = session(DiscountInviteUrl::SESSION_KEY);

        if (is_string($grantId) && DiscountGrant::query()->pending()->find($grantId) !== null) {
            $this->discountGrantId = $grantId;
        } else {
            session()->forget(DiscountInviteUrl::SESSION_KEY);
        }

        parent::mount();
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        $name = $this->lockedToInvite($this->getNameFormComponent(), fn (DiscountGrant $grant): ?string => $grant->name);
        $email = $this->lockedToInvite($this->getEmailFormComponent(), fn (DiscountGrant $grant): string => $grant->email);

        return parent::form($schema)->components([
            Callout::make(fn (): string => sprintf('Convite da Clau: %d%% de desconto vitalício', $this->invitedGrant()?->discount->percent_off))
                ->description(fn (): string => 'Use o mesmo CPF que você informou à Clau. O desconto vale no plano pessoal assinado até ' . $this->invitedGrant()?->expires_at->format('d/m/Y') . '.')
                ->info()
                ->actions([
                    Action::make('leaveDiscountInvite')
                        ->label('Seguir sem o desconto')
                        ->link()
                        ->url(route(DiscountInviteUrl::LEAVE_ROUTE)),
                ])
                ->visible(fn (): bool => $this->invitedGrant() instanceof DiscountGrant),
            $name,
            $email,
            TaxIdInput::make()
                ->rule(fn (Field $component): Closure => $this->uniqueDetailRule('tax_id', $component->getLabel(), withTrashed: true)),
            DocumentIdInput::make()
                ->rule(fn (Field $component): Closure => $this->uniqueDetailRule('document_id', $component->getLabel())),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getVoucherFormComponent(),
            Checkbox::make('accepts_flamma_terms')
                ->label(new HtmlString('Li e aceito a <a href="' . e(route('site.privacy')) . '" target="_blank" class="underline">Política de Privacidade da Flamma</a>'))
                ->accepted()
                ->dehydrated(false)
                ->visible(fn (): bool => $this->invitedGrant() instanceof DiscountGrant),
        ]);
    }

    /**
     * @param  Closure(DiscountGrant): ?string  $value
     */
    private function lockedToInvite(Component $component, Closure $value): Component
    {
        if (! $component instanceof TextInput) {
            return $component;
        }

        return $component
            ->default(function () use ($value): ?string {
                $grant = $this->invitedGrant();

                return $grant instanceof DiscountGrant ? $value($grant) : null;
            })
            ->readOnly(fn (): bool => $this->invitedGrant() instanceof DiscountGrant);
    }

    private function invitedGrant(): ?DiscountGrant
    {
        if ($this->discountGrantId === null) {
            return null;
        }

        return DiscountGrant::query()->pending()->with('discount')->find($this->discountGrantId);
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

    /**
     * A conferência do CPF roda antes do cadastro: o Filament valida dentro de uma transação
     * que desfaria o contador de tentativas. A atribuição só é apagada depois do commit: se o
     * cadastro falhar no meio da transação, a nova tentativa ainda leva o clau_id e as UTMs.
     */
    #[Override]
    public function register(): ?RegistrationResponse
    {
        $grant = $this->invitedGrant();
        $taxId = preg_replace('/\D+/', '', (string) ($this->data['tax_id'] ?? '')) ?? '';

        if ($grant instanceof DiscountGrant && strlen($taxId) === 11) {
            $check = resolve(VerifyDiscountGrantTaxId::class)->handle($grant, $taxId);

            if ($check === DiscountTaxIdCheckEnum::Mismatch) {
                throw ValidationException::withMessages(['data.tax_id' => 'Esse CPF não confere com o informado na Clau. Confira os números ou siga sem o desconto.']);
            }

            if ($check === DiscountTaxIdCheckEnum::Locked) {
                session()->forget(DiscountInviteUrl::SESSION_KEY);
                $this->discountGrantId = null;

                throw ValidationException::withMessages(['data.tax_id' => 'Muitas tentativas com um CPF diferente. Você pode seguir com o cadastro sem o desconto ou falar com o suporte.']);
            }
        }

        $response = parent::register();
        $user = Filament::auth()->user();

        if ($response instanceof RegistrationResponse && $user instanceof User) {
            session()->forget(DiscountInviteUrl::SESSION_KEY);
            $this->trackSignup($user);
        }

        return $response;
    }

    #[Override]
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        $grant = $this->invitedGrant();

        if ($grant instanceof DiscountGrant) {
            $data['name'] = $grant->name ?? $data['name'];
            $data['email'] = $grant->email;
        }

        return parent::mutateFormDataBeforeRegister($data);
    }

    #[Override]
    protected function handleRegistration(array $data): Model
    {
        /** @var User $user */
        $user = parent::handleRegistration($data);
        event(new UserRegistered($user, Roles::Employee));

        $this->claimedDiscountGrant = resolve(ClaimDiscountGrant::class)->handle($user, (string) $data['tax_id']);

        $user->detail()->create([
            'tax_id' => $data['tax_id'],
            'document_id' => $data['document_id'],
            'phone_number' => $this->claimedDiscountGrant?->phone_number,
        ]);

        $this->redeemVoucher($user, $data['voucher'] ?? null);

        return $user;
    }

    /**
     * Quem veio do convite da Clau já é uma pessoa identificada no PostHog, que não pode virar
     * alias de outra: por isso o id do Flamma entra como alias da pessoa da Clau, e não o contrário.
     * O clau_id também vai no evento para auditar uniões feitas por um link repassado, e o `$set`
     * preenche a ficha da pessoa com nome e e-mail.
     */
    private function trackSignup(User $user): void
    {
        $attribution = StoreSignupAttribution::current(request());
        $properties = [...$attribution['utms'] ?? [], '$set' => PostHog::personProperties($user)];
        $clauId = $attribution['clau_id'] ?? $this->claimedDiscountGrant?->external_ref;
        $postHog = resolve(PostHog::class);

        if ($clauId !== null) {
            $postHog->alias(anonymousId: $user->getKey(), userId: $clauId);
            $properties['clau_id'] = $clauId;
        }

        if ($this->claimedDiscountGrant instanceof DiscountGrant) {
            $properties['desconto_clau'] = true;
        }

        $postHog->capture($user->getKey(), 'cadastro_concluido', $properties);
        StoreSignupAttribution::forget();
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
