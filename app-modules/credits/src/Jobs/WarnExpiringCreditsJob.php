<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Jobs;

use App\Models\Users\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\Credits\Notifications\CreditsExpiringNotification;

/**
 * Avisa quem tem crédito livre perto de vencer, 7 dias e 1 dia antes (gil-benefits#349). Um aviso
 * por pessoa e por antecedência, com a data do primeiro crédito a vencer; rodar duas vezes no
 * mesmo dia não repete o aviso.
 */
class WarnExpiringCreditsJob implements ShouldQueue
{
    use Queueable;

    /** @var list<int> */
    public const array DAYS_AHEAD = [7, 1];

    public function handle(): void
    {
        foreach (self::DAYS_AHEAD as $daysAhead) {
            $this->warnCreditsExpiringOn(today()->addDays($daysAhead), $daysAhead);
        }
    }

    private function warnCreditsExpiringOn(CarbonInterface $day, int $daysAhead): void
    {
        UserCredit::query()
            ->where('status', UserCreditStatusEnum::Available)
            ->whereBetween('expires_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->selectRaw('holder_id, min(expires_at) as first_expires_at')
            ->groupBy('holder_id')
            ->get()
            ->each(function (UserCredit $credit) use ($daysAhead): void {
                $this->warn((string) $credit->holder_id, (string) $credit->getAttribute('first_expires_at'), $daysAhead);
            });
    }

    private function warn(string $holderId, string $firstExpiresAt, int $daysAhead): void
    {
        $holder = User::query()->find($holderId);

        if (! $holder instanceof User) {
            return;
        }

        if (! Cache::add(sprintf('credits-expiring:%s:%d:%s', $holderId, $daysAhead, today()->toDateString()), true, now()->addDays(2))) {
            return;
        }

        $holder->notify(new CreditsExpiringNotification(Date::parse($firstExpiresAt)));
    }
}
