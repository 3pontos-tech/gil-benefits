<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\DTOs\MonthlyValue;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Financial\Concerns\BuildsFinancialCacheKey;
use TresPontosTech\PanelAdmin\DTOs\Financial\ConsultingValue;
use TresPontosTech\PanelAdmin\DTOs\Financial\ConsultingValueRow;
use TresPontosTech\PanelAdmin\DTOs\Financial\ContractRow;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;

/**
 * Valor das consultorias consumidas no mês (STORY-239).
 *
 * A conta é `volume × valor da consultoria`. O volume é dado real; o valor é um
 * número único que a Flamma configura, porque a plataforma não vende
 * consultoria avulsa e não tem preço por consultoria em lugar nenhum.
 *
 * A quebra é por empresa, e não por consultor, porque a pergunta que o número
 * responde é do financeiro: quem consome mais do que paga.
 */
final class GetConsultingValue
{
    use BuildsFinancialCacheKey;

    private const string BUCKET = 'consulting_value';

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

    public function __construct(private readonly GetContractsTable $contracts) {}

    public function handle(FinancialFilters $filters): ConsultingValue
    {
        return Cache::remember(
            $this->financialCacheKey($this->bucket(), $filters),
            $this->financialCacheTtl(),
            fn (): ConsultingValue => $this->build($filters),
        );
    }

    public function forget(FinancialFilters $filters): void
    {
        $this->forgetFinancialCache($this->bucket(), $filters);
    }

    /**
     * O valor configurado entra na chave do cache.
     *
     * Ele é entrada do cálculo tanto quanto os filtros: sem isso, mudar a
     * variável de ambiente deixa a tela repetindo o total antigo — ou, pior,
     * repetindo "ainda não configurado" por cinco minutos depois de configurado,
     * o que se parece com a funcionalidade quebrada.
     */
    private function bucket(): string
    {
        return self::BUCKET . '.' . ($this->unitValue() ?? 'unset');
    }

    private function build(FinancialFilters $filters): ConsultingValue
    {
        $unitValue = $this->unitValue();
        $counts = $this->countsByCompany($filters);

        if ($counts === []) {
            return new ConsultingValue(collect(), $unitValue);
        }

        $companies = Company::query()
            ->whereIn('id', array_keys($counts))
            ->get(['id', 'name'])
            ->keyBy(fn (Company $company): string => (string) $company->getKey());

        $monthlyValues = $this->monthlyValuesFor($filters);

        $rows = collect($counts)
            ->map(function (array $statuses, string $companyId) use ($companies, $monthlyValues, $unitValue): ?ConsultingValueRow {
                $company = $companies->get($companyId);

                if (! $company instanceof Company) {
                    return null;
                }

                return new ConsultingValueRow(
                    companyId: $companyId,
                    companyName: $company->name,
                    completed: (int) ($statuses[AppointmentStatus::Completed->value] ?? 0),
                    cancelledLate: (int) ($statuses[AppointmentStatus::CancelledLate->value] ?? 0),
                    noShow: (int) ($statuses[AppointmentStatus::NoShow->value] ?? 0),
                    monthlyValue: $monthlyValues[$companyId] ?? MonthlyValue::unknown(),
                    unitValueCents: $unitValue,
                );
            })
            ->filter()
            ->sortByDesc(fn (ConsultingValueRow $row): int => $row->billable())
            ->values();

        return new ConsultingValue($rows, $unitValue);
    }

    /**
     * Contagem por empresa e status, numa consulta só.
     *
     * @return array<string, array<string, int>>
     */
    private function countsByCompany(FinancialFilters $filters): array
    {
        $rows = Appointment::query()
            ->betweenDates($filters->period->start, $filters->period->end)
            ->whereNotNull('company_id')
            ->whereIn('status', array_map(
                static fn (AppointmentStatus $status): string => $status->value,
                self::BILLABLE_STATUSES,
            ))
            ->when(
                $filters->isFilteredByCompany(),
                fn ($query) => $query->whereIn('company_id', $filters->companyIds),
            )
            ->groupBy('company_id', 'status')
            ->selectRaw('company_id, status, COUNT(*) as total')
            ->get();

        // Os atributos vêm crus do selectRaw, então viram array aqui em vez de
        // serem lidos como propriedade do modelo — `total` não é coluna.
        $counts = [];

        foreach ($rows as $row) {
            $attributes = $row->getAttributes();
            $companyId = (string) ($attributes['company_id'] ?? '');
            $status = (string) ($attributes['status'] ?? '');

            $counts[$companyId][$status] = (int) ($attributes['total'] ?? 0);
        }

        return $counts;
    }

    /**
     * Mensalidade de cada empresa, reaproveitando a listagem da STORY-234.
     *
     * Recalcular aqui abriria a porta para esta tela e a de contratos
     * discordarem sobre quanto a mesma empresa paga.
     *
     * @return array<string, MonthlyValue>
     */
    private function monthlyValuesFor(FinancialFilters $filters): array
    {
        /** @var Collection<int, ContractRow> $contracts */
        $contracts = $this->contracts->handle($filters);

        $values = [];

        foreach ($contracts as $contract) {
            $values[$contract->companyId] = $contract->monthlyValue;
        }

        return $values;
    }

    /**
     * Valor de uma consultoria, ou `null` quando ninguém configurou.
     *
     * Nunca zero: um valor zerado faria o painel afirmar que a consultoria não
     * vale nada.
     */
    private function unitValue(): ?int
    {
        $configured = config('billing.consulting_value_in_cents');

        return is_numeric($configured) ? (int) $configured : null;
    }
}
