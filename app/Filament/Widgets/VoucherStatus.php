<?php

namespace App\Filament\Widgets;

use App\Enums\VoucherStatusEnum;
use App\Models\Voucher;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

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
            'gray' => '#6B7280',
            'warning' => '#F59E0B',
            'success' => '#10B981',
            'info' => '#3B82F6',
            'danger' => '#EF4444',
        };
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
