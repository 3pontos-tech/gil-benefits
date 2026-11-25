<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\Schemas;

use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Contracts\SupportsLimitedDurationDiscount;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Discounts\Enums\DiscountDistributionEnum;
use TresPontosTech\Discounts\Enums\DiscountDurationEnum;
use TresPontosTech\Discounts\Enums\DiscountValueTypeEnum;
use TresPontosTech\Discounts\Models\Discount;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('slug')
                            ->label('Identificador')
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated(fn (string $operation): bool => $operation === 'create'),

                        Select::make('distribution')
                            ->label('Distribuição')
                            ->options(DiscountDistributionEnum::class)
                            ->default(DiscountDistributionEnum::Grant->value)
                            ->required()
                            ->live(),

                        TextInput::make('code')
                            ->label('Código')
                            ->maxLength(40)
                            ->unique(ignoreRecord: true)
                            ->helperText('Sem espaços e em maiúsculas. A pessoa digita o código no checkout.')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                $set('code', Discount::normalizeCodeValue($state));
                            })
                            ->visible(fn (Get $get): bool => self::is($get, 'distribution', DiscountDistributionEnum::Code))
                            ->required(fn (Get $get): bool => self::is($get, 'distribution', DiscountDistributionEnum::Code))
                            ->dehydratedWhenHidden()
                            ->dehydrateStateUsing(fn (mixed $state, Get $get): ?string => self::is($get, 'distribution', DiscountDistributionEnum::Code) ? Discount::normalizeCodeValue($state) : null),

                        Toggle::make('active')
                            ->label('Ativo'),
                    ])
                    ->columns(2),

                Section::make('Desconto')
                    ->schema([
                        Select::make('value_type')
                            ->label('Tipo de desconto')
                            ->options(DiscountValueTypeEnum::class)
                            ->default(DiscountValueTypeEnum::Percent->value)
                            ->required()
                            ->live(),

                        TextInput::make('percent_off')
                            ->label('Percentual')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->suffix('%')
                            ->visible(fn (Get $get): bool => self::is($get, 'value_type', DiscountValueTypeEnum::Percent))
                            ->required(fn (Get $get): bool => self::is($get, 'value_type', DiscountValueTypeEnum::Percent))
                            ->dehydratedWhenHidden()
                            ->dehydrateStateUsing(fn (mixed $state, Get $get): mixed => self::is($get, 'value_type', DiscountValueTypeEnum::Percent) ? $state : null),

                        TextInput::make('amount_off_cents')
                            ->label('Valor do desconto')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->prefix('R$')
                            ->formatStateUsing(fn (mixed $state): ?float => is_numeric($state) ? (float) $state / 100 : null)
                            ->visible(fn (Get $get): bool => self::is($get, 'value_type', DiscountValueTypeEnum::FixedAmount))
                            ->required(fn (Get $get): bool => self::is($get, 'value_type', DiscountValueTypeEnum::FixedAmount))
                            ->dehydratedWhenHidden()
                            ->dehydrateStateUsing(fn (mixed $state, Get $get): ?int => self::is($get, 'value_type', DiscountValueTypeEnum::FixedAmount) && is_numeric($state) ? (int) round((float) $state * 100) : null),

                        Select::make('duration')
                            ->label('Duração')
                            ->options(DiscountDurationEnum::class)
                            ->default(DiscountDurationEnum::Forever->value)
                            ->required()
                            ->live(),

                        TextInput::make('duration_cycles')
                            ->label('Quantidade de cobranças')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('cobranças')
                            ->visible(fn (Get $get): bool => self::is($get, 'duration', DiscountDurationEnum::Repeating))
                            ->required(fn (Get $get): bool => self::is($get, 'duration', DiscountDurationEnum::Repeating))
                            ->dehydratedWhenHidden()
                            ->dehydrateStateUsing(fn (mixed $state, Get $get): mixed => self::is($get, 'duration', DiscountDurationEnum::Repeating) ? $state : null),

                        Callout::make('Duração não suportada pelo gateway de pagamento')
                            ->description('O gateway atual só aplica o desconto em todas as mensalidades. Este cupom não será ofertado às pessoas até que a integração suporte esta duração.')
                            ->warning()
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => ! self::is($get, 'duration', DiscountDurationEnum::Forever) && ! self::gatewaySupportsLimitedDuration()),
                    ])
                    ->columns(2),

                Section::make('Quem pode usar')
                    ->schema([
                        Select::make('audience')
                            ->label('Público')
                            ->options([
                                PriceAudienceEnum::Standalone->value => 'Pessoa física (plano pessoal)',
                            ])
                            ->default(PriceAudienceEnum::Standalone->value)
                            ->placeholder('Qualquer público')
                            ->nullable(),

                        Select::make('eligible_plan_slugs')
                            ->label('Planos elegíveis')
                            ->multiple()
                            ->options(fn (): array => self::planOptions())
                            ->placeholder('Todos os planos')
                            ->helperText('Sem seleção, o cupom vale para todos os planos pessoais.')
                            ->dehydrateStateUsing(fn (mixed $state): ?array => blank($state) ? null : array_values($state)),

                        Toggle::make('first_subscription_only')
                            ->label('Só para quem nunca assinou')
                            ->helperText('Quem já teve uma assinatura paga não recebe este cupom.'),
                    ])
                    ->columns(2),

                Section::make('Prazos e limites')
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Válido a partir de')
                            ->helperText('Vazio = sem início.'),

                        DateTimePicker::make('ends_at')
                            ->label('Válido até')
                            ->helperText('Vazio = sem fim. Depois desta data o cupom não é concedido nem usado.'),

                        TextInput::make('claim_window_days')
                            ->label('Prazo para assinar')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('dias')
                            ->helperText('Contado a partir da concessão ou do resgate do código.')
                            ->default(180)
                            ->visible(fn (Get $get): bool => ! self::is($get, 'distribution', DiscountDistributionEnum::Automatic))
                            ->required(fn (Get $get): bool => ! self::is($get, 'distribution', DiscountDistributionEnum::Automatic))
                            ->dehydratedWhenHidden()
                            ->dehydrateStateUsing(fn (mixed $state, Get $get): mixed => self::is($get, 'distribution', DiscountDistributionEnum::Automatic) ? null : $state),

                        TextInput::make('max_redemptions')
                            ->label('Limite total de usos')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Sem limite')
                            ->helperText('Conta os usos confirmados e as reservas em andamento.'),

                        TextInput::make('max_redemptions_per_customer')
                            ->label('Limite por pessoa')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Sem limite')
                            ->default(1),

                        Toggle::make('locks_plan')
                            ->label('Trava o plano')
                            ->helperText('O plano fica travado no primeiro pagamento. Dentro da janela, a pessoa pode reassinar o mesmo plano.'),
                    ])
                    ->columns(2),
            ]);
    }

    private static function is(Get $get, string $field, BackedEnum $expected): bool
    {
        $state = $get($field);

        return ($state instanceof BackedEnum ? $state->value : $state) === $expected->value;
    }

    private static function gatewaySupportsLimitedDuration(): bool
    {
        return resolve(BillingManager::class)->getDriver(BillingProviderEnum::checkoutCases()[0]) instanceof SupportsLimitedDurationDiscount;
    }

    /**
     * @return array<string, string>
     */
    private static function planOptions(): array
    {
        return Plan::query()
            ->where('type', BillableTypeEnum::User)
            ->where('active', true)
            ->orderBy('name')
            ->pluck('name', 'slug')
            ->all();
    }
}
