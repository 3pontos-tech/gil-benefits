<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Engagement;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TresPontosTech\PanelAdmin\DTOs\EngagementFilters;
use TresPontosTech\PanelAdmin\DTOs\EngagementFunnelRow;
use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Streams the engagement funnel currently on screen as a CSV file. Uses a
 * semicolon delimiter and a UTF-8 BOM so the file opens correctly in the
 * spreadsheet tools the business team already uses.
 *
 * Every row carries the analysed period, because the file name only records the
 * export date — without it the numbers are ambiguous once the file leaves the
 * admin panel.
 */
final class ExportEngagementCsv
{
    private const string DELIMITER = ';';

    /**
     * Leading characters that make a spreadsheet evaluate a cell as a formula.
     *
     * @var array<int, string>
     */
    private const array FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r", "\n"];

    /**
     * @param  Collection<int, EngagementFunnelRow>  $rows
     */
    public function handle(EngagementFilters $filters, Collection $rows): StreamedResponse
    {
        $filename = sprintf('engajamento_flamma_%s.csv', now()->toDateString());

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
            __('panel-admin::widgets.engagement.funnel.company'),
            __('panel-admin::widgets.engagement.funnel.seats'),
            __('panel-admin::widgets.engagement.funnel.registered'),
            __('panel-admin::widgets.engagement.funnel.with_appointment'),
            __('panel-admin::widgets.engagement.funnel.with_completed'),
            __('panel-admin::widgets.engagement.funnel.with_recurrence'),
            __('panel-admin::widgets.engagement.funnel.completion_rate'),
            __('panel-admin::resources.pages.engagement.filter_start_date'),
            __('panel-admin::resources.pages.engagement.filter_end_date'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function line(EngagementFunnelRow $row, EngagementFilters $filters): array
    {
        return [
            $this->asText($row->companyName),
            (string) $row->seats,
            (string) $row->registered,
            (string) $row->withAppointment,
            (string) $row->withCompletedAppointment,
            (string) $row->withRecurrence,
            EngagementNumber::percent($row->completionRate()),
            $filters->start->isoFormat('L'),
            $filters->end->isoFormat('L'),
        ];
    }

    /**
     * Keeps a spreadsheet from evaluating a company name as a formula. Names are
     * typed by people, so a company called "=HYPERLINK(...)" would otherwise run
     * as soon as the business team opens the file.
     */
    private function asText(string $value): string
    {
        return in_array(mb_substr($value, 0, 1), self::FORMULA_TRIGGERS, true)
            ? "'" . $value
            : $value;
    }
}
