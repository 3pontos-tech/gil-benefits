<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Vouchers\Actions\GenerateVoucherBatch;
use TresPontosTech\Vouchers\DTOs\GenerateVoucherBatchData;
use TresPontosTech\Vouchers\Models\VoucherCode;
use TresPontosTech\Vouchers\Support\VoucherCodeGenerator;

function generateBatch(CompanyPlan $plan, int $quantity = 5, ?User $admin = null): mixed
{
    return resolve(GenerateVoucherBatch::class)->handle(new GenerateVoucherBatchData(
        companyPlanId: $plan->getKey(),
        name: 'Campanha Lançamento',
        quantity: $quantity,
        createdBy: $admin?->getKey(),
    ));
}

it('creates one code per unit of the batch', function (): void {
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create();

    $batch = generateBatch($plan, quantity: 25);

    expect($batch->codes()->count())->toBe(25)
        ->and($batch->quantity)->toBe(25);
});

it('inherits the company from the plan', function (): void {
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create();

    $batch = generateBatch($plan);

    expect($batch->company_id)->toBe($plan->company_id)
        ->and($batch->company_plan_id)->toBe($plan->getKey());
});

it('records who generated the batch', function (): void {
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create();
    $admin = User::factory()->create();

    $batch = generateBatch($plan, admin: $admin);

    expect($batch->created_by)->toBe($admin->getKey());
});

it('gives every code a single use by default', function (): void {
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create();

    generateBatch($plan, quantity: 10);

    expect(VoucherCode::query()->where('max_redemptions', 1)->count())->toBe(10)
        ->and(VoucherCode::query()->where('redemptions_count', 0)->count())->toBe(10);
});

it('generates codes that are unique across batches', function (): void {
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create();

    generateBatch($plan, quantity: 40);
    generateBatch($plan, quantity: 40);

    expect(VoucherCode::query()->distinct()->count('code'))->toBe(80);
});

it('keeps codes free of ambiguous characters', function (): void {
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create();

    generateBatch($plan, quantity: 30);

    VoucherCode::query()->pluck('code')->each(function (string $code): void {
        expect($code)->toMatch('/^[' . VoucherCodeGenerator::ALPHABET . ']{4}-[' . VoucherCodeGenerator::ALPHABET . ']{4}$/')
            ->and($code)->not->toContain('0')
            ->and($code)->not->toContain('O')
            ->and($code)->not->toContain('1')
            ->and($code)->not->toContain('I');
    });
});
