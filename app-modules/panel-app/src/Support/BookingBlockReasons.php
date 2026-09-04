<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Support;

use App\Models\Users\User;

/**
 * Por que esta pessoa não pode agendar agora, nas palavras que ela entende.
 *
 * São dois impedimentos independentes e ela precisa saber qual é o dela: sem cota, espera a
 * virada do ciclo; com consultoria em aberto, conclui a atual. Dizer "um ou outro" deixa as
 * duas saídas no ar — e ficou pior desde que o card passou a mostrar saldo e data de
 * renovação, porque a tela se contradiz na cara do cliente.
 *
 * Classe própria, e não método da trait de agendamento: o card, o toast do fluxo e a página
 * de criação precisam da mesma resposta, e nem todos usam a trait. Método estático de trait
 * chamado de fora é depreciado no PHP.
 */
final class BookingBlockReasons
{
    /**
     * Recebe os fatos já apurados, para quem já os tem em mãos e não quer repetir as queries.
     *
     * @return list<string>
     */
    public static function from(bool $hasOngoingAppointment, bool $hasQuotaOrCredit): array
    {
        $reasons = [];

        if ($hasOngoingAppointment) {
            $reasons[] = __('panel-app::widgets.plans_overview.ongoing_appointment');
        }

        if (! $hasQuotaOrCredit) {
            $reasons[] = __('panel-app::widgets.plans_overview.no_appointments_available');
        }

        return $reasons;
    }

    /**
     * Os mesmos motivos, apurando os fatos, para quem só tem o usuário em mãos.
     *
     * @return list<string>
     */
    public static function for(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        return self::from(
            $user->hasOngoingAppointment(),
            $user->monthly_appointments_left > 0 || $user->hasAvailableCredit(),
        );
    }
}
