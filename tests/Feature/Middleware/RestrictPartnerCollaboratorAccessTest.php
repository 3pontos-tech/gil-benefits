<?php

use App\Http\Middleware\RestrictPartnerCollaboratorAccess;
use App\Models\Users\User;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\actingAs;

describe('RestrictPartnerCollaboratorAccess Middleware', function () {

    test('allows non-authenticated users to continue', function () {
        $middleware = new RestrictPartnerCollaboratorAccess;
        $request = Request::create('/test');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        expect($response->getContent())->toBe('OK');
    });

    test('allows non-partner collaborators to access any panel', function () {
        $company = Company::factory()->create(['partner_code' => null]);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);

        actingAs($user);

        $middleware = new RestrictPartnerCollaboratorAccess;
        $request = Request::create('/admin');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        expect($response->getContent())->toBe('OK');
    });

    test('redirects partner collaborators trying to access non-user panels', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);

        actingAs($user);

        // Mock Filament to return admin panel
        $adminPanel = Mockery::mock(\Filament\Panel::class);
        $adminPanel->shouldReceive('getId')->andReturn('admin');

        Filament::shouldReceive('getCurrentPanel')->andReturn($adminPanel);

        $middleware = new RestrictPartnerCollaboratorAccess;
        $request = Request::create('/admin');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('Should not reach here');
        });

        expect($response)->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class);
    });

    test('allows partner collaborators to access user panel', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);

        actingAs($user);

        // Mock Filament to return user panel
        $userPanel = Mockery::mock(\Filament\Panel::class);
        $userPanel->shouldReceive('getId')->andReturn('app');

        Filament::shouldReceive('getCurrentPanel')->andReturn($userPanel);

        $middleware = new RestrictPartnerCollaboratorAccess;
        $request = Request::create('/app');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        expect($response->getContent())->toBe('OK');
    });

    test('allows access when no current panel is set', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);

        actingAs($user);

        Filament::shouldReceive('getCurrentPanel')->andReturn(null);

        $middleware = new RestrictPartnerCollaboratorAccess;
        $request = Request::create('/test');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        expect($response->getContent())->toBe('OK');
    });
});
