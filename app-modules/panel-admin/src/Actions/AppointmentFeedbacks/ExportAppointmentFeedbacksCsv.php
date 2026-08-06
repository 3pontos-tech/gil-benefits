<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\AppointmentFeedbacks;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TresPontosTech\Appointments\Models\AppointmentFeedback;

/**
 * Streams the evaluations currently on screen as a CSV file. Uses a semicolon
 * delimiter and a UTF-8 BOM so the file opens correctly in the spreadsheet
 * tools the business team already uses.
 */
final class ExportAppointmentFeedbacksCsv
{
    private const string DELIMITER = ';';

    /**
     * Leading characters that make a spreadsheet evaluate a cell as a formula.
     *
     * @var array<int, string>
     */
    private const array FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r", "\n"];

    /**
     * @param  Builder<AppointmentFeedback>  $query
     */
    public function handle(Builder $query): StreamedResponse
    {
        $filename = sprintf('avaliacoes_atendimentos_%s.csv', now()->toDateString());

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $this->headers(), self::DELIMITER);

            $rows = $query
                ->with(['user', 'appointment.consultant', 'appointment.company'])
                ->reorder()
                ->lazyById(200);

            foreach ($rows as $feedback) {
                fputcsv($handle, $this->line($feedback), self::DELIMITER);
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
            __('panel-admin::resources.appointment_feedbacks.fields.created_at'),
            __('panel-admin::resources.appointment_feedbacks.fields.rating'),
            __('panel-admin::resources.appointment_feedbacks.fields.comment'),
            __('panel-admin::resources.appointment_feedbacks.fields.user'),
            __('panel-admin::resources.appointment_feedbacks.fields.consultant'),
            __('panel-admin::resources.appointment_feedbacks.fields.company'),
            __('panel-admin::resources.appointment_feedbacks.fields.appointment_at'),
            __('panel-admin::resources.appointment_feedbacks.fields.appointment_status'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function line(AppointmentFeedback $feedback): array
    {
        return [
            $feedback->created_at?->format('d/m/Y H:i') ?? '',
            (string) $feedback->rating,
            $this->asText($feedback->comment),
            $this->asText($feedback->user?->name),
            $this->asText($feedback->appointment?->consultant?->name),
            $this->asText($feedback->appointment?->company?->name),
            $feedback->appointment?->appointment_at?->format('d/m/Y H:i') ?? '',
            $feedback->appointment?->status?->getLabel() ?? '',
        ];
    }

    /**
     * Keeps a spreadsheet from evaluating a cell as a formula. Comments and
     * names are typed by people, so a value like "=HYPERLINK(...)" would
     * otherwise run as soon as the business team opens the file.
     */
    private function asText(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return in_array(mb_substr($value, 0, 1), self::FORMULA_TRIGGERS, true)
            ? "'" . $value
            : $value;
    }
}
