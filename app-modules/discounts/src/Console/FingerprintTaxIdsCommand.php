<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Console;

use App\Models\Users\Detail;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use TresPontosTech\Discounts\Support\TaxIdFingerprint;

final class FingerprintTaxIdsCommand extends Command
{
    protected $signature = 'discounts:fingerprint-tax-ids';

    protected $description = 'Preenche a impressão do CPF (tax_id_fingerprint) dos cadastros que ainda não têm';

    public function handle(): int
    {
        if (! TaxIdFingerprint::isConfigured()) {
            $this->error('Configure CLAU_TAX_ID_KEY antes de rodar.');

            return self::FAILURE;
        }

        $count = 0;

        Detail::withTrashed()
            ->whereNull('tax_id_fingerprint')
            ->whereNotNull('tax_id')
            ->chunkById(500, function (Collection $details) use (&$count): void {
                foreach ($details as $detail) {
                    $detail->forceFill(['tax_id_fingerprint' => TaxIdFingerprint::make($detail->tax_id)])->saveQuietly();
                    ++$count;
                }
            });

        $this->info("{$count} cadastro(s) atualizados.");

        return self::SUCCESS;
    }
}
