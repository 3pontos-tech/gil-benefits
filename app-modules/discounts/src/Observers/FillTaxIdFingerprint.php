<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Observers;

use App\Models\Users\Detail;
use TresPontosTech\Discounts\Support\TaxIdFingerprint;

final class FillTaxIdFingerprint
{
    public function saving(Detail $detail): void
    {
        if ($detail->isDirty('tax_id') && filled($detail->tax_id) && TaxIdFingerprint::isConfigured()) {
            $detail->tax_id_fingerprint = TaxIdFingerprint::make($detail->tax_id);
        }
    }
}
