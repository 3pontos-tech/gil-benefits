<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Notifications\DatabaseNotification;
use TresPontosTech\IntegrationStripe\Events\StripeWebhookFailed;
use TresPontosTech\PanelAdmin\Listeners\NotifyAdminsOfStripeWebhookFailedListener;

it('tells every admin which Stripe event was lost, and nobody else', function (): void {
    $superAdmin = actingAsSuperAdmin();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    resolve(NotifyAdminsOfStripeWebhookFailedListener::class)
        ->handle(new StripeWebhookFailed('evt_1', 'customer.subscription.updated'));

    $notified = DatabaseNotification::query()->pluck('notifiable_id')->all();
    $body = DatabaseNotification::query()->where('notifiable_id', $admin->id)->sole()->data['body'];

    expect($notified)->toEqualCanonicalizing([$superAdmin->id, $admin->id])
        ->and($body)->toContain('customer.subscription.updated')
        ->and($body)->toContain('evt_1');
});
