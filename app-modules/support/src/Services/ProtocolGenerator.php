<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Services;

use Illuminate\Support\Facades\DB;
use TresPontosTech\Support\Models\SupportTicket;

class ProtocolGenerator
{
    public function generate(): string
    {
        return DB::transaction(function (): string {
            $year = now()->year;

            $last = SupportTicket::query()
                ->whereYear('created_at', $year)
                ->lockForUpdate()->latest()
                ->value('protocol');

            $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

            return sprintf('SUP-%d-%04d', $year, $seq);
        });
    }
}
