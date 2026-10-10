<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\V1\Employee;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Support\Models\SupportTicket;

final readonly class ListEmployeeTicketsAction
{
    /**
     * Os chamados da pessoa, de qualquer empresa: os abertos no painel, no app e na central de
     * ajuda com o e-mail dela (CreateSupportTicketAction liga esses à conta).
     *
     * Sem withoutGlobalScopes(), ao contrário do painel: o escopo de tenancy do Filament só
     * filtra quando há um painel aberto, o que não acontece na API.
     *
     * @return Builder<SupportTicket>
     */
    public function handle(User $user): Builder
    {
        return SupportTicket::query()->where('user_id', $user->id);
    }
}
