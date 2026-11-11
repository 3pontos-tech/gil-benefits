<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Jobs;

use App\Models\Users\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\Credits\Notifications\CreditsExpiringNotification;

/**
 * Avisa quem tem crédito livre perto de vencer (gil-benefits#349): uma semana antes e na véspera.
 *
 * Cada crédito guarda quando foi avisado, então uma rodada perdida é recuperada na seguinte e
 * nada se repete. O job roda de hora em hora durante o dia, e cada pessoa recebe no máximo um
 * push por rodada, o mais urgente, com a data do primeiro crédito a vencer.
 */
class WarnExpiringCreditsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 600;

    public function handle(): void
    {
        $warnedToday = $this->warn(daysAhead: 1, marker: 'expiry_warned_day_before_at', alsoMark: ['expiry_warned_week_before_at']);

        $this->warn(daysAhead: 7, marker: 'expiry_warned_week_before_at', alsoMark: [], alreadyWarned: $warnedToday);
    }

    /**
     * Marca os créditos que entraram na janela e avisa quem ainda não foi avisado nesta rodada.
     * A janela conta pelo último dia em que o crédito vale, não pelo dia do vencimento: o que vence
     * à meia-noite do dia X vale até o dia X-1 (como no `lastDay()` do aviso), e a véspera dele é
     * X-2. Por isso o limite é a meia-noite do dia seguinte à janela, e não o fim do dia.
     *
     * @param  list<string>  $alsoMark  marcas que este aviso também cobre (a véspera cobre a semana)
     * @param  list<string>  $alreadyWarned  quem já recebeu o aviso mais urgente: só é marcado
     * @return list<string> quem foi avisado
     */
    private function warn(int $daysAhead, string $marker, array $alsoMark, array $alreadyWarned = []): array
    {
        $due = UserCredit::query()
            ->where('status', UserCreditStatusEnum::Available)
            ->whereNull($marker)
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', today()->addDays($daysAhead + 1))
            ->oldest('expires_at')
            ->get(['id', 'holder_id', 'expires_at'])
            ->groupBy('holder_id');

        $warned = [];

        foreach ($due as $holderId => $credits) {
            /** @var Collection<int, UserCredit> $credits */
            $holderId = (string) $holderId;

            if (DB::transaction(fn (): bool => $this->claimAndWarn($holderId, $credits, $marker, $alsoMark, $alreadyWarned))) {
                $warned[] = $holderId;
            }
        }

        return $warned;
    }

    /**
     * A marca é um update condicional: duas rodadas ao mesmo tempo nunca avisam duas vezes. Ela e
     * o aviso ficam na mesma transação, então um aviso que falha antes do commit (a linha do sino,
     * a busca do aparelho) desfaz a marca e a próxima rodada tenta de novo; sem isso, a véspera
     * marcaria também a semana e o crédito venceria sem aviso.
     *
     * @param  Collection<int, UserCredit>  $credits
     * @param  list<string>  $alsoMark
     * @param  list<string>  $alreadyWarned
     */
    private function claimAndWarn(string $holderId, Collection $credits, string $marker, array $alsoMark, array $alreadyWarned): bool
    {
        $claimed = UserCredit::query()
            ->whereKey($credits->modelKeys())
            ->whereNull($marker)
            ->update(array_fill_keys([$marker, ...$alsoMark], now()));

        $firstToExpire = $credits->first()?->expires_at;

        if ($claimed === 0 || in_array($holderId, $alreadyWarned, true) || ! $firstToExpire instanceof CarbonInterface) {
            return false;
        }

        $holder = User::query()->find($holderId);

        if (! $holder instanceof User) {
            return false;
        }

        $holder->notify(new CreditsExpiringNotification($firstToExpire));

        return true;
    }
}
