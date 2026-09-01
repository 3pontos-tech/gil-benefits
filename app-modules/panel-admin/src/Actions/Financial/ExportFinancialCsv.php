<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial;

use Symfony\Component\HttpFoundation\StreamedResponse;
use TresPontosTech\Billing\Core\Enums\CompanyFinancialStatusEnum;
use TresPontosTech\Billing\Core\Support\MoneyCents;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;

/**
 * Exporta a listagem que está na tela como CSV (STORY-234).
 *
 * Delimitador ponto e vírgula e BOM UTF-8, como o export de engajamento, para
 * abrir direto no Excel em pt_BR. Cada linha carrega o mês de referência: o
 * nome do arquivo só registra a data da exportação, e sem isso os números ficam
 * ambíguos assim que a planilha sai do painel.
 */
final class ExportFinancialCsv
{
    private const string DELIMITER = ';';

    /**
     * Caracteres iniciais que fazem a planilha interpretar a célula como fórmula.
     *
     * @var array<int, string>
     */
    private const array FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r", "\n"];

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function handle(FinancialFilters $filters, array $rows): StreamedResponse
    {
        $filename = sprintf('empresas_contratos_flamma_%s.csv', now()->toDateString());

        return response()->streamDownload(function () use ($filters, $rows): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $this->headers(), self::DELIMITER);

            foreach ($rows as $row) {
                fputcsv($handle, $this->line($row, $filters), self::DELIMITER);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function headers(): array
    {
        return [
            __('panel-admin::widgets.financial.contracts.company'),
            __('panel-admin::widgets.financial.contracts.plan'),
            __('panel-admin::widgets.financial.contracts.monthly_value'),
            __('panel-admin::widgets.financial.contracts.next_charge'),
            __('panel-admin::widgets.financial.contracts.status'),
            __('panel-admin::widgets.financial.contracts.reference_month'),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, string>
     */
    private function line(array $row, FinancialFilters $filters): array
    {
        $cents = $row['monthly_value_cents'] ?? null;
        $status = is_string($row['status'] ?? null)
            ? CompanyFinancialStatusEnum::tryFrom($row['status'])
            : null;

        return array_map($this->neutralise(...), [
            (string) ($row['company_name'] ?? ''),
            (string) ($row['plan_name'] ?? ''),
            $cents === null
                ? __('panel-admin::widgets.financial.contracts.value_unknown')
                : MoneyCents::fromCents((int) $cents)->format(),
            (string) ($row['next_charge_at'] ?? ''),
            $status?->getLabel() ?? '',
            $filters->period->label(),
        ]);
    }

    /**
     * Impede que a planilha avalie o conteúdo como fórmula.
     *
     * Nome de empresa é texto que veio de cadastro, então um nome começando com
     * "=" viraria fórmula ao abrir o arquivo.
     */
    private function neutralise(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        return in_array($value[0], self::FORMULA_TRIGGERS, strict: true)
            ? "'" . $value
            : $value;
    }
}
