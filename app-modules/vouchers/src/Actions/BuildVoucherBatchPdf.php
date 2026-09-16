<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use TresPontosTech\Vouchers\Models\VoucherBatch;

/**
 * Monta a carteirinha frente e verso, uma por página, na proporção 4:5 da arte.
 *
 * A arte é medida em pixels numa prancha de 1080 x 1350; aqui ela é reescalada para a
 * largura física configurada. Manter os números originais no template é o que permite
 * conferi-lo contra a arte de origem sem refazer conta nenhuma.
 */
final readonly class BuildVoucherBatchPdf
{
    public const DESIGN_WIDTH = 1080;

    public const DESIGN_HEIGHT = 1350;

    private const POINTS_PER_MM = 2.834645669;

    public function handle(VoucherBatch $batch): PdfDocument
    {
        $batch->loadMissing(['company', 'companyPlan']);

        $cards = $batch->codes()
            ->with('media')
            ->orderBy('code')
            ->get();

        $widthMm = (float) config('vouchers.card.width_mm');

        return Pdf::loadView('vouchers::pdf.batch-cards', [
            'cards' => $cards,
            'companyName' => $batch->company->name,
            'deadline' => $this->deadline($batch)?->format('d/m/Y') ?? 'sem prazo',
            'steps' => $this->steps(),
            'supportEmail' => config('vouchers.card.support_email'),
            'site' => config('vouchers.card.site'),
            'mm' => $this->scaler($widthMm, 1.0),
            'pt' => $this->scaler($widthMm, self::POINTS_PER_MM),
        ])->setPaper($this->paper($widthMm));
    }

    public function fileName(VoucherBatch $batch): string
    {
        return sprintf('vouchers-%s.pdf', Str::slug($batch->name));
    }

    /**
     * O prazo impresso é o de RESGATE, não o da consultoria: o crédito só ganha validade
     * própria quando alguém informa o código. Vale a data que fechar primeiro, porque
     * qualquer uma das duas já faz o resgate ser recusado.
     */
    public function deadline(VoucherBatch $batch): ?Carbon
    {
        return collect([$batch->expires_at, $batch->companyPlan?->ends_at])
            ->filter()
            ->min();
    }

    /**
     * @return list<string>
     */
    private function steps(): array
    {
        return [
            'Aponte a câmera para o QR Code',
            'Crie sua conta — o código já vem preenchido',
            'Responda ao questionário inicial',
            'Escolha o melhor horário e agende sua sessão',
        ];
    }

    /**
     * @return Closure(float): string
     */
    private function scaler(float $widthMm, float $factor): Closure
    {
        $ratio = $widthMm / self::DESIGN_WIDTH * $factor;

        return static fn (float $designPixels): string => (string) round($designPixels * $ratio, 3);
    }

    /**
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    private function paper(float $widthMm): array
    {
        $heightMm = $widthMm / self::DESIGN_WIDTH * self::DESIGN_HEIGHT;

        return [0.0, 0.0, $widthMm * self::POINTS_PER_MM, $heightMm * self::POINTS_PER_MM];
    }
}
