<?php

use App\Filament\FilamentPanel;
use TresPontosTech\Appointments\Providers\AppointmentsServiceProvider;
use TresPontosTech\Billing\BillingServiceProvider;
use TresPontosTech\Company\Providers\CompanyServiceProvider;
use TresPontosTech\Consultants\Providers\ConsultantsServiceProvider;
use TresPontosTech\IntegrationHighlevel\IntegrationHighlevelServiceProvider;
use TresPontosTech\Tenant\Providers\TenantServiceProvider;
use TresPontosTech\User\Providers\UserServiceProvider;

describe('Service Provider Standardization', function () {
    it('all service providers follow consistent structure', function () {
        $providers = [
            AppointmentsServiceProvider::class,
            BillingServiceProvider::class,
            CompanyServiceProvider::class,
            ConsultantsServiceProvider::class,
            IntegrationHighlevelServiceProvider::class,
            TenantServiceProvider::class,
            UserServiceProvider::class,
        ];

        foreach ($providers as $providerClass) {
            $reflection = new ReflectionClass($providerClass);

            // Check that all providers have register() and boot() methods
            expect($reflection->hasMethod('register'))->toBeTrue("$providerClass should have register() method");
            expect($reflection->hasMethod('boot'))->toBeTrue("$providerClass should have boot() method");

            // Check that register() method has proper return type
            $registerMethod = $reflection->getMethod('register');
            expect($registerMethod->getReturnType()?->getName())->toBe('void');

            // Check that boot() method has proper return type
            $bootMethod = $reflection->getMethod('boot');
            expect($bootMethod->getReturnType()?->getName())->toBe('void');
        }
    });

    it('filament panel enum values are consistent', function () {
        // Check that FilamentPanel enum has all expected values
        $expectedPanels = [
            'app',     // User panel
            'admin',   // Admin panel
            'company',
            'consultant',
            'guest',
        ];

        $actualPanels = array_map(fn ($case) => $case->value, FilamentPanel::cases());

        expect($actualPanels)->toEqual($expectedPanels);
    });

    it('service providers use consistent method naming', function () {
        $providersWithTranslations = [
            AppointmentsServiceProvider::class,
        ];

        foreach ($providersWithTranslations as $providerClass) {
            $reflection = new ReflectionClass($providerClass);

            // Check for consistent private method naming
            if ($reflection->hasMethod('loadTranslations')) {
                $method = $reflection->getMethod('loadTranslations');
                expect($method->isPrivate())->toBeTrue("loadTranslations should be private in $providerClass");
            }

            if ($reflection->hasMethod('registerFilamentResources')) {
                $method = $reflection->getMethod('registerFilamentResources');
                expect($method->isPrivate())->toBeTrue("registerFilamentResources should be private in $providerClass");
            }
        }
    });
});
