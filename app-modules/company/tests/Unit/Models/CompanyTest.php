<?php

namespace TresPontosTech\Company\Tests\Unit\Models;

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\assertDatabaseHas;

describe('Company Model', function () {
    describe('partner_code functionality', function () {
        it('can be created with a partner_code', function () {
            $user = User::factory()->create();

            $company = Company::create([
                'user_id' => $user->id,
                'name' => 'Test Company',
                'slug' => 'test-company',
                'tax_id' => '12345678901234',
                'partner_code' => 'PARTNER123',
            ]);

            expect($company->partner_code)->toBe('PARTNER123');

            assertDatabaseHas('companies', [
                'id' => $company->id,
                'partner_code' => 'PARTNER123',
            ]);
        });

        it('can be created without a partner_code', function () {
            $user = User::factory()->create();

            $company = Company::create([
                'user_id' => $user->id,
                'name' => 'Test Company',
                'slug' => 'test-company',
                'tax_id' => '12345678901234',
            ]);

            expect($company->partner_code)->toBeNull();
        });

        it('enforces unique constraint on partner_code', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            Company::create([
                'user_id' => $user1->id,
                'name' => 'First Company',
                'slug' => 'first-company',
                'tax_id' => '12345678901234',
                'partner_code' => 'UNIQUE123',
            ]);

            expect(function () use ($user2) {
                Company::create([
                    'user_id' => $user2->id,
                    'name' => 'Second Company',
                    'slug' => 'second-company',
                    'tax_id' => '98765432109876',
                    'partner_code' => 'UNIQUE123',
                ]);
            })->toThrow(\Illuminate\Database\QueryException::class);
        });

        it('can find company by partner code using findByPartnerCode method', function () {
            $user = User::factory()->create();

            $company = Company::create([
                'user_id' => $user->id,
                'name' => 'Partner Company',
                'slug' => 'partner-company',
                'tax_id' => '12345678901234',
                'partner_code' => 'FIND123',
            ]);

            $foundCompany = Company::findByPartnerCode('FIND123');

            expect($foundCompany)->not->toBeNull();
            expect($foundCompany->id)->toBe($company->id);
            expect($foundCompany->partner_code)->toBe('FIND123');
        });

        it('returns null when partner code is not found', function () {
            $foundCompany = Company::findByPartnerCode('NONEXISTENT');

            expect($foundCompany)->toBeNull();
        });

        it('performs case-sensitive search for partner code', function () {
            $user = User::factory()->create();

            Company::create([
                'user_id' => $user->id,
                'name' => 'Case Sensitive Company',
                'slug' => 'case-sensitive-company',
                'tax_id' => '12345678901234',
                'partner_code' => 'CaseSensitive123',
            ]);

            $foundUpper = Company::findByPartnerCode('CaseSensitive123');
            $foundLower = Company::findByPartnerCode('casesensitive123');

            expect($foundUpper)->not->toBeNull();
            expect($foundLower)->toBeNull();
        });

        it('can update partner_code', function () {
            $user = User::factory()->create();

            $company = Company::create([
                'user_id' => $user->id,
                'name' => 'Update Test Company',
                'slug' => 'update-test-company',
                'tax_id' => '12345678901234',
                'partner_code' => 'ORIGINAL123',
            ]);

            $company->update(['partner_code' => 'UPDATED123']);

            expect($company->fresh()->partner_code)->toBe('UPDATED123');

            assertDatabaseHas('companies', [
                'id' => $company->id,
                'partner_code' => 'UPDATED123',
            ]);
        });

        it('can set partner_code to null', function () {
            $user = User::factory()->create();

            $company = Company::create([
                'user_id' => $user->id,
                'name' => 'Nullable Test Company',
                'slug' => 'nullable-test-company',
                'tax_id' => '12345678901234',
                'partner_code' => 'TONULL123',
            ]);

            $company->update(['partner_code' => null]);

            expect($company->fresh()->partner_code)->toBeNull();
        });
    });
});
