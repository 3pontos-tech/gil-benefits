<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\Employee\Tickets;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Actions\ResolveQuotaAllowance;
use TresPontosTech\Support\Actions\CreateSupportTicketAction;
use TresPontosTech\Support\DTOs\CreateSupportTicketDTO;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Models\SupportTicket;

final readonly class OpenEmployeeTicketAction
{
    public function __construct(
        private CreateSupportTicketAction $createTicket,
        private ResolveQuotaAllowance $resolveQuotaAllowance,
    ) {}

    /**
     * Abre o chamado pelo mesmo caminho do painel (protocolo, e-mail de confirmação, encaminhamento
     * ao setor). A empresa é a do app, como a aberta na tela do painel; a origem fica `app`/`mobile`,
     * que é o que a equipe vê no admin e no e-mail do chamado.
     */
    public function handle(User $user, SupportTicketCategoryEnum $category, string $subject, string $description): SupportTicket
    {
        return $this->createTicket->execute(new CreateSupportTicketDTO(
            category: $category,
            subject: $subject,
            description: $description,
            userId: $user->id,
            companyId: $this->resolveQuotaAllowance->companyIdFor($user),
            browser: 'app',
            device: 'mobile',
            environment: app()->environment(),
        ));
    }
}
