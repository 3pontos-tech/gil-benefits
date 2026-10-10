<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\V1\Employee;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use TresPontosTech\Api\Enums\AppointmentListFilter;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

final readonly class ListEmployeeAppointmentsAction
{
    private const int PER_PAGE = 50;

    /**
     * Lista os encontros do colaborador, 50 por página, com os mesmos cortes que o app faz hoje no
     * cliente: `upcoming` = pendente ou confirmado de hoje em diante (ordem crescente); `pending` =
     * pendentes, com qualquer data (crescente); `history` = encerrados, cancelados, no-show e os
     * pendentes/confirmados cuja data já passou (decrescente). Sem filtro, tudo em ordem decrescente,
     * como a tabela do painel. `$with` são as relações a carregar, definidas por quem serializa.
     *
     * @param  list<string>  $with
     * @return LengthAwarePaginator<int, Appointment>
     */
    public function handle(User $user, ?AppointmentListFilter $filter, array $with = []): LengthAwarePaginator
    {
        $query = $user->appointments()->with($with);
        $today = today();

        match ($filter) {
            AppointmentListFilter::Upcoming => $query
                ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Active->value])
                ->where('appointment_at', '>=', $today)
                ->oldest('appointment_at'),
            AppointmentListFilter::Pending => $query
                ->where('status', AppointmentStatus::Pending->value)
                ->oldest('appointment_at'),
            AppointmentListFilter::History => $query
                ->where(fn (Builder $closed): Builder => $closed
                    ->whereIn('status', array_map(fn (AppointmentStatus $status): string => $status->value, AppointmentStatus::closed()))
                    ->orWhere('appointment_at', '<', $today))
                ->latest('appointment_at'),
            null => $query->latest('appointment_at'),
        };

        return $query->paginate(self::PER_PAGE);
    }
}
