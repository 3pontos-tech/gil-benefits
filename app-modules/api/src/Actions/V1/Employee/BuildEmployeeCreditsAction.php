<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\V1\Employee;

use App\Models\Users\User;
use TresPontosTech\Api\DTOs\EmployeeCredits;
use TresPontosTech\Billing\Core\Actions\ResolveQuotaAllowance;
use TresPontosTech\Billing\Core\Support\QuotaCycle;
use TresPontosTech\Credits\Models\UserCredit;

final readonly class BuildEmployeeCreditsAction
{
    public function __construct(private ResolveQuotaAllowance $resolveQuotaAllowance) {}

    /**
     * Mesmas fontes do painel: a cota vem do plano (empresa ou assinatura individual) e é
     * consumida antes dos créditos; os créditos são os que a pessoa tem em mãos na mesma
     * empresa da cota, como na página "Meus créditos". Quem não tem plano com cota recebe
     * limite e saldo zerados e sem data de renovação.
     */
    public function handle(User $user): EmployeeCredits
    {
        $companyId = $this->resolveQuotaAllowance->companyIdFor($user);
        $allowance = $this->resolveQuotaAllowance->for($user, $companyId);

        $credits = UserCredit::query()
            ->where('holder_id', $user->getKey())
            ->where('company_id', $companyId)
            ->latest()
            ->get();

        if ($allowance->isEmpty()) {
            return new EmployeeCredits(0, 0, null, $credits);
        }

        return new EmployeeCredits(
            monthlyLimit: $allowance->limit,
            monthlyLeft: $user->monthly_appointments_left,
            renewsAt: QuotaCycle::forAnchor($allowance->anchor)->end,
            credits: $credits,
        );
    }
}
