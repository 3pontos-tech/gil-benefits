<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use TresPontosTech\Vouchers\Enums\VoucherStatusEnum;
use TresPontosTech\Vouchers\Models\Voucher;

class VoucherStatus extends ChartWidget
{
    protected ?string $heading = 'Vouchers By Status';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        foreach (VoucherStatusEnum::cases() as $status) {
            $data = Trend::query(Voucher::query()
                ->where('status', $status->value))
                ->between(
                    start: now()->startOfMonth(),
                    end: now()->endOfMonth(),
                )
                ->perMonth()
                ->count();

            $datasets[] = [
                'label' => $status->getLabel(),
                'data' => $data->map(fn (TrendValue $value): mixed => $value->aggregate),
                'backgroundColor' => $this->mapEnumColor($status->getColor()),
                'borderColor' => '#47b3bf',
                'borderWidth' => 1,
            ];
        }

        $statusLabels = $data->map(fn (TrendValue $value): string => $value->date);

        return [
            'datasets' => $datasets,
            'labels' => $statusLabels,
        ];
    }

    protected function mapEnumColor(string $enumColor): string
    {
        return match ($enumColor) {
            'gray' => 'rgba(107, 114, 128, 0.6)',
            'warning' => 'rgba(245, 158, 11, 0.6)',
            'success' => 'rgba(16, 185, 129, 0.6)',
            'info' => 'rgba(59, 130, 246, 0.6)',
            'danger' => 'rgba(239, 68, 68, 0.6)',
        };
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
