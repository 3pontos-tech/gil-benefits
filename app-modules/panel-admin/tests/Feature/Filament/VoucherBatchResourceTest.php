<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\Pages\CreateVoucherBatch;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\Pages\ListVoucherBatches;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\Pages\ViewVoucherBatch;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\RelationManagers\VoucherCodesRelationManager;
use TresPontosTech\Vouchers\Models\VoucherBatch;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('renders the batch list', function (): void {
    VoucherBatch::factory()->count(2)->create();

    livewire(ListVoucherBatches::class)->assertOk();
});

it('renders the batch detail with its codes', function (): void {
    $batch = VoucherBatch::factory()->create();

    livewire(ViewVoucherBatch::class, ['record' => $batch->getKey()])->assertOk();

    livewire(VoucherCodesRelationManager::class, [
        'ownerRecord' => $batch,
        'pageClass' => ViewVoucherBatch::class,
    ])->assertOk();
});

it('generates the codes when the admin creates a batch', function (): void {
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create();

    livewire(CreateVoucherBatch::class)
        ->fillForm([
            'company_plan_id' => $plan->getKey(),
            'name' => 'Campanha Incorporadora',
            'quantity' => 12,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $batch = VoucherBatch::query()->sole();

    expect($batch->company_id)->toBe($plan->company_id)
        ->and($batch->codes()->count())->toBe(12);
});

it('offers only credits only programs to attach the batch to', function (): void {
    $voucherProgram = CompanyPlan::factory()->active()->creditsOnly()->create();
    $payingContract = CompanyPlan::factory()->active()->create();

    livewire(CreateVoucherBatch::class)
        ->assertOk()
        ->assertFormFieldExists('company_plan_id', checkFieldUsing: function ($field) use ($voucherProgram, $payingContract): bool {
            $options = array_keys($field->getOptions());

            return in_array($voucherProgram->getKey(), $options, true)
                && ! in_array($payingContract->getKey(), $options, true);
        });
});

it('rejects a batch bigger than the allowed maximum', function (): void {
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create();

    livewire(CreateVoucherBatch::class)
        ->fillForm([
            'company_plan_id' => $plan->getKey(),
            'name' => 'Campanha grande',
            'quantity' => 99999,
        ])
        ->call('create')
        ->assertHasFormErrors(['quantity']);
});
