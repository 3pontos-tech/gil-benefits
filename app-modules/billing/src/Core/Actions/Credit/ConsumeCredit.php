<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions\Credit;

use TresPontosTech\Billing\Core\DTOs\CreditDTO;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;

final readonly class ConsumeCredit
{
    /**
     * Gasta o crédito mais antigo que esta pessoa tem NA EMPRESA do agendamento.
     *
     * Sem o recorte por empresa, agendar numa empresa consumia o crédito guardado em outra:
     * a fila é por titular, e o titular pode ter crédito em mais de uma. O saldo da outra
     * empresa some sem que nada tenha sido agendado lá.
     */
    public function execute(CreditDTO $dto): void
    {
        UserCredit::query()
            ->where('holder_id', $dto->holderId)
            ->where('company_id', $dto->companyId)
            ->where('status', UserCreditStatusEnum::Available)
            ->oldest()
            ->first()
            ?->update([
                'status' => UserCreditStatusEnum::InUse,
                'appointment_id' => $dto->appointmentId,
            ]);
    }
}
