<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\Pages;

use App\Models\Users\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\VoucherBatchResource;
use TresPontosTech\Vouchers\Actions\GenerateVoucherBatch;
use TresPontosTech\Vouchers\DTOs\GenerateVoucherBatchData;
use TresPontosTech\Vouchers\Jobs\GenerateVoucherQrCodesJob;

class CreateVoucherBatch extends CreateRecord
{
    protected static string $resource = VoucherBatchResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User|null $admin */
        $admin = auth()->user();

        $batch = resolve(GenerateVoucherBatch::class)->handle(new GenerateVoucherBatchData(
            companyPlanId: (string) $data['company_plan_id'],
            name: (string) $data['name'],
            quantity: (int) $data['quantity'],
            expiresAt: blank($data['expires_at'] ?? null) ? null : Date::parse($data['expires_at'])->endOfDay(),
            createdBy: $admin?->id,
            notes: $data['notes'] ?? null,
        ));

        dispatch(new GenerateVoucherQrCodesJob($batch->getKey()));

        return $batch;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
