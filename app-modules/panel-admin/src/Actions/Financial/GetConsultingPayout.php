<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial;

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\PanelAdmin\Actions\Financial\Concerns\BuildsFinancialCacheKey;
use TresPontosTech\PanelAdmin\DTOs\Financial\ConsultingPayout;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\DTOs\Financial\PayoutRow;

/**
 * Repasse aos consultores no mês (STORY-239).
 *
 * A story pedia margem operacional. Consultoria não tem custo no domínio e a
 * plataforma não sabe quanto uma consultoria vale, então a story foi reescrita
 * com o PO para entregar o repasse — quanto a Flamma paga ao parceiro pelas
 * consultorias que consumiram crédito do cliente.
 */
final class GetConsultingPayout
{
    use BuildsFinancialCacheKey;

    private const string BUCKET = 'consulting_payout';

    /**
     * Desfechos que consomem o crédito do cliente.
     *
     * Régua definida pelo PO e conferida no código: `AbstractAppointmentTransition`
     * devolve o crédito apenas no cancelamento dentro da regra; todo o resto que
     * passou pela confirmação consumiu.
     *
     * @var list<AppointmentStatus>
     */
    private const array BILLABLE_STATUSES = [
        AppointmentStatus::Completed,
        AppointmentStatus::CancelledLate,
        AppointmentStatus::NoShow,
    ];

    public function handle(FinancialFilters $filters): ConsultingPayout
    {
        return Cache::remember(
            $this->financialCacheKey(self::BUCKET, $filters),
            $this->financialCacheTtl(),
            fn (): ConsultingPayout => $this->build($filters),
        );
    }

    public function forget(FinancialFilters $filters): void
    {
        $this->forgetFinancialCache(self::BUCKET, $filters);
    }

    private function build(FinancialFilters $filters): ConsultingPayout
    {
        $defaultCost = $this->defaultCost();

        $rows = Appointment::query()
            ->betweenDates($filters->period->start, $filters->period->end)
            ->whereNotNull('consultant_id')
            ->whereIn('status', array_map(
                static fn (AppointmentStatus $status): string => $status->value,
                self::BILLABLE_STATUSES,
            ))
            ->when(
                $filters->isFilteredByCompany(),
                fn ($query) => $query->whereIn('company_id', $filters->companyIds),
            )
            ->groupBy('consultant_id', 'status')
            ->selectRaw('consultant_id, status, COUNT(*) as total')
            ->get();

        // Os atributos vêm crus do selectRaw, então viram array aqui em vez de
        // serem lidos como propriedade do modelo — `total` não é coluna.
        $counts = [];

        foreach ($rows as $row) {
            $attributes = $row->getAttributes();
            $consultantId = (string) ($attributes['consultant_id'] ?? '');
            $status = (string) ($attributes['status'] ?? '');

            $counts[$consultantId][$status] = (int) ($attributes['total'] ?? 0);
        }

        if ($counts === []) {
            return new ConsultingPayout(collect(), $defaultCost);
        }

        $consultants = Consultant::query()
            ->whereIn('id', array_keys($counts))
            ->get(['id', 'name', 'cost_per_appointment_cents'])
            ->keyBy(fn (Consultant $consultant): string => (string) $consultant->getKey());

        $payoutRows = collect($counts)
            ->map(function (array $statuses, string $consultantId) use ($consultants, $defaultCost): ?PayoutRow {
                $consultant = $consultants->get($consultantId);

                if (! $consultant instanceof Consultant) {
                    return null;
                }

                $own = $consultant->cost_per_appointment_cents;

                return new PayoutRow(
                    consultantId: $consultantId,
                    consultantName: $consultant->name,
                    completed: (int) ($statuses[AppointmentStatus::Completed->value] ?? 0),
                    cancelledLate: (int) ($statuses[AppointmentStatus::CancelledLate->value] ?? 0),
                    noShow: (int) ($statuses[AppointmentStatus::NoShow->value] ?? 0),
                    costPerAppointmentCents: $own ?? $defaultCost,
                    usesDefaultCost: $own === null,
                );
            })
            ->filter()
            ->sortByDesc(fn (PayoutRow $row): int => $row->payoutCents() ?? $row->billable())
            ->values();

        return new ConsultingPayout($payoutRows, $defaultCost);
    }

    /**
     * Custo padrão do parceiro, ou `null` quando ninguém configurou.
     *
     * Nunca zero: um padrão zerado faria o painel afirmar que o parceiro
     * trabalhou de graça.
     */
    private function defaultCost(): ?int
    {
        $configured = config('billing.consulting_cost_in_cents');

        return is_numeric($configured) ? (int) $configured : null;
    }
}
