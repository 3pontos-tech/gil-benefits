<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetAdoptionFunnel;

beforeEach(fn () => Cache::flush());

it('counts invited, with access and adoption as a snapshot', function (): void {
    $company = Company::factory()->create();
    $verified = User::factory()->create(['email_verified_at' => now()]);
    $unverified = User::factory()->create(['email_verified_at' => null]);
    $company->employees()->attach($verified->id, ['active' => true]);
    $company->employees()->attach($unverified->id, ['active' => true]);

    $data = resolve(GetAdoptionFunnel::class)->handle($company);

    expect($data->invited)->toBe(2)
        ->and($data->withAccess)->toBe(1)
        ->and($data->noAccess)->toBe(1)
        ->and($data->steps)->toHaveCount(3);
});

it('shows zero percent at the top of the funnel when no one was invited', function (): void {
    $company = Company::factory()->create();

    $data = resolve(GetAdoptionFunnel::class)->handle($company);

    expect($data->invited)->toBe(0)
        ->and($data->steps[0]->percent)->toBe(0.0)
        ->and($data->adoptionRate)->toBe(0.0);
});
