<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Actions;

use TresPontosTech\Credits\DTOs\CreditDTO;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;

final readonly class ConsumeCredit
{
    /**
     * Gasta o crédito mais antigo que esta pessoa tem NA EMPRESA do agendamento.
     *
     * Sem o recorte por empresa, agendar numa empresa consumia o crédito guardado em outra:
     * a fila é por titular, e o titular pode ter crédito em mais de uma. O saldo da outra
     * empresa some sem que nada tenha sido agendado lá.
     *
     * Entre os disponíveis, o que vence primeiro sai antes — crédito de voucher tem data e
     * o comprado não, então gastar o perene deixaria o de prazo apodrecer na fila.
     */
    public function execute(CreditDTO $dto): void
    {
        UserCredit::query()
            ->where('holder_id', $dto->holderId)
            ->where('company_id', $dto->companyId)
            ->where('status', UserCreditStatusEnum::Available)
            ->notExpired()
            ->orderByRaw('expires_at is null, expires_at asc')
            ->oldest()
            ->first()
            ?->update([
                'status' => UserCreditStatusEnum::InUse,
                'appointment_id' => $dto->appointmentId,
            ]);
    }
}
