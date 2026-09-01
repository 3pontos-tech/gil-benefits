<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial;

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelAdmin\Actions\Financial\Concerns\BuildsFinancialCacheKey;
use TresPontosTech\PanelAdmin\DTOs\Financial\ConsultingVolume;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;

/**
 * Volume de consultorias do mês, cross-empresa (STORY-238).
 *
 * O dado já existia inteiro: `AppointmentStatus` cobre os quatro buckets que a
 * story pede. O que faltava era a leitura sem tenant — as duas Actions que já
 * contavam agendamentos são do painel da empresa (escopada) e da página de
 * métricas do Admin (que conta `cancelled` sem o `cancelled_late`).
 */
final class GetConsultingVolume
{
    use BuildsFinancialCacheKey;

    private const string BUCKET = 'consulting_volume';

    public function handle(FinancialFilters $filters): ConsultingVolume
    {
        return Cache::remember(
            $this->financialCacheKey(self::BUCKET, $filters),
            $this->financialCacheTtl(),
            fn (): ConsultingVolume => $this->build($filters),
        );
    }

    public function forget(FinancialFilters $filters): void
    {
        $this->forgetFinancialCache(self::BUCKET, $filters);
    }

    private function build(FinancialFilters $filters): ConsultingVolume
    {
        $counts = Appointment::query()
            ->betweenDates($filters->period->start, $filters->period->end)
            ->when(
                $filters->isFilteredByCompany(),
                fn ($query) => $query->whereIn('company_id', $filters->companyIds),
            )
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $of = static fn (AppointmentStatus $status): int => (int) ($counts[$status->value] ?? 0);

        $cancelled = $of(AppointmentStatus::Cancelled);
        $cancelledLate = $of(AppointmentStatus::CancelledLate);

        return new ConsultingVolume(
            // Agendadas é tudo que foi marcado no período, qualquer que tenha
            // sido o desfecho — é o denominador da taxa de realização.
            scheduled: (int) $counts->sum(),
            completed: $of(AppointmentStatus::Completed),
            cancelled: $cancelled + $cancelledLate,
            cancelledLate: $cancelledLate,
            noShow: $of(AppointmentStatus::NoShow),
        );
    }
}
