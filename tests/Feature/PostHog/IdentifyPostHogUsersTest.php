<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use App\Models\Users\User;
use App\Support\PostHog\CapturePostHogEvent;
use Illuminate\Support\Facades\Queue;
use TresPontosTech\Permissions\Roles;

beforeEach(function (): void {
    Queue::fake([CapturePostHogEvent::class]);
});

it('sends name, e-mail and phone of every customer to PostHog, leaving the internal team out', function (): void {
    config(['services.posthog.key' => 'phc_test']);
    $ana = User::factory()->create(['name' => 'Ana Teste', 'email' => 'ana.teste@example.com']);
    Detail::factory()->for($ana)->create(['phone_number' => '+5511900000001', 'company_id' => null]);
    $bruno = User::factory()->create(['name' => 'Bruno Teste', 'email' => 'bruno.teste@example.com']);
    User::factory()->create()->assignRole(Roles::Consultant);

    $this->artisan('posthog:identify-users')
        ->expectsOutput('2 usuário(s) enviado(s) ao PostHog.')
        ->assertSuccessful();

    $sent = Queue::pushed(CapturePostHogEvent::class)
        ->mapWithKeys(fn (CapturePostHogEvent $job): array => [$job->distinctId => [$job->event, $job->properties['$set']]])
        ->all();

    expect($sent)->toEqual([
        $ana->getKey() => ['$set', ['email' => 'ana.teste@example.com', 'name' => 'Ana Teste', 'phone' => '+5511900000001']],
        $bruno->getKey() => ['$set', ['email' => 'bruno.teste@example.com', 'name' => 'Bruno Teste']],
    ]);
});

it('sends nothing without a key', function (): void {
    config(['services.posthog.key' => null]);
    User::factory()->create();

    $this->artisan('posthog:identify-users')
        ->expectsOutput('PostHog sem chave configurada: nada foi enviado.')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});
