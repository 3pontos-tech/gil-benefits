<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Actions;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Vouchers\DTOs\GenerateVoucherBatchData;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherCode;
use TresPontosTech\Vouchers\Support\VoucherCodeGenerator;

final readonly class GenerateVoucherBatch
{
    public const COLLISION_RETRIES = 8;

    public function handle(GenerateVoucherBatchData $data): VoucherBatch
    {
        return DB::transaction(function () use ($data): VoucherBatch {
            $plan = CompanyPlan::query()->findOrFail($data->companyPlanId);

            $batch = VoucherBatch::query()->create([
                'company_id' => $plan->company_id,
                'company_plan_id' => $plan->getKey(),
                'created_by' => $data->createdBy,
                'name' => $data->name,
                'quantity' => $data->quantity,
                'expires_at' => $data->expiresAt,
                'notes' => $data->notes,
            ]);

            for ($i = 0; $i < $data->quantity; ++$i) {
                $this->createCode($batch, $data->maxRedemptions);
            }

            return $batch;
        });
    }

    private function createCode(VoucherBatch $batch, int $maxRedemptions): VoucherCode
    {
        for ($attempt = 0; $attempt < self::COLLISION_RETRIES; ++$attempt) {
            try {
                return VoucherCode::query()->create([
                    'voucher_batch_id' => $batch->getKey(),
                    'code' => VoucherCodeGenerator::generate(),
                    'max_redemptions' => $maxRedemptions,
                    'redemptions_count' => 0,
                ]);
            } catch (QueryException $exception) {
                throw_unless($this->isUniqueViolation($exception), $exception);
            }
        }

        throw new RuntimeException('Não foi possível gerar um código de voucher único.');
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        return in_array($exception->getCode(), ['23000', '23505'], true);
    }
}
