<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Billing\Core\Models\Price;

/**
 * Tira o escopo do preço de dentro do `metadata` e põe numa coluna.
 *
 * Antes, "este preço é do tenant sem empregador" era `metadata->tenant` —
 * jsonb sem índice, editável como texto livre no admin, e lido por um critério
 * negativo ("o preço que NÃO tem tenant" era o padrão), que quebraria em
 * silêncio no dia que alguém escrevesse outro slug ali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_plan_prices', function (Blueprint $table): void {
            $table->string('audience')
                ->default(PriceAudienceEnum::Subsidized->value)
                ->after('provider_price_id')
                ->index()
                ->comment("'subsidized', 'standalone'");
        });

        Price::query()->withoutGlobalScopes()
            ->whereJsonContains('metadata->tenant', 'flamma-company')
            ->get()
            ->each(function (Price $price): void {
                $metadata = $price->metadata ?? [];
                unset($metadata['tenant']);

                $price->forceFill([
                    'audience' => PriceAudienceEnum::Standalone,
                    'metadata' => $metadata,
                ])->saveQuietly();
            });
    }

    public function down(): void
    {
        Price::query()->withoutGlobalScopes()
            ->where('audience', PriceAudienceEnum::Standalone->value)
            ->get()
            ->each(fn (Price $price) => $price->forceFill([
                'metadata' => array_merge($price->metadata ?? [], ['tenant' => 'flamma-company']),
            ])->saveQuietly());

        Schema::table('billing_plan_prices', function (Blueprint $table): void {
            $table->dropIndex(['audience']);
            $table->dropColumn('audience');
        });
    }
};
