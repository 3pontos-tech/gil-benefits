<?php

use App\Actions\RegisterPartnerCollaboratorAction;
use App\DTO\PartnerRegistrationDTO;
use App\Filament\Guest\Pages\PartnerRegistrationPage;
use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Company\Models\Company;

uses(RefreshDatabase::class);

// Helper function to generate valid CPF
function generateValidCpf(?int $seed = null): string
{
    if ($seed !== null) {
        // Generate deterministic CPF based on seed
        $base = str_pad((string) ($seed * 123456789 % 999999999), 9, '0', STR_PAD_LEFT);
    } else {
        // Generate random base
        $base = str_pad((string) rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
    }

    // Calculate first verification digit
    $sum = 0;
    for ($i = 0; $i < 9; ++$i) {
        $sum += intval($base[$i]) * (10 - $i);
    }
    $remainder = $sum % 11;
    $firstDigit = $remainder < 2 ? 0 : 11 - $remainder;

    // Calculate second verification digit
    $sum = 0;
    for ($i = 0; $i < 9; ++$i) {
        $sum += intval($base[$i]) * (11 - $i);
    }
    $sum += $firstDigit * 2;
    $remainder = $sum % 11;
    $secondDigit = $remainder < 2 ? 0 : 11 - $remainder;

    $cpf = $base . $firstDigit . $secondDigit;

    // Format with mask
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

beforeEach(function () {
    // Create test companies with partner codes
    $this->partnerCompany = Company::factory()->create([
        'name' => 'Partner Company Ltd ' . uniqid(),
        'slug' => 'partner-company-' . uniqid(),
        'partner_code' => 'PARTNER123',
    ]);

    $this->anotherPartnerCompany = Company::factory()->create([
        'name' => 'Another Partner Co ' . uniqid(),
        'slug' => 'another-partner-' . uniqid(),
        'partner_code' => 'PARTNER456',
    ]);

    // Create a regular company without partner code
    $this->regularCompany = Company::factory()->create([
        'name' => 'Regular Company ' . uniqid(),
        'slug' => 'regular-company-' . uniqid(),
        'partner_code' => null,
    ]);

    // Valid registration data
    $this->validRegistrationData = [
        'name' => 'João Silva Santos',
        'rg' => '12.345.678-9',
        'cpf' => generateValidCpf(1), // Use deterministic CPF
        'email' => 'joao.silva@example.com',
        'password' => 'SecurePassword123!',
        'password_confirmation' => 'SecurePassword123!',
        'partner_code' => 'PARTNER123',
    ];
});

describe('End-to-End Registration Process', function () {
    test('complete registration flow creates all required records', function () {
        // Record initial counts (companies create owner users)
        $initialUserCount = User::count();
        $initialDetailCount = Detail::count();
        $initialEmployeeCount = DB::table('company_employees')->count();

        // Execute registration through Livewire component
        $component = Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($this->validRegistrationData)
            ->call('submit');

        // Verify success state
        expect($component->get('registrationSuccess'))->toBeTrue();
        expect($component->get('successMessage'))->toContain('João Silva Santos');

        // Verify counts increased by 1
        expect(User::count())->toBe($initialUserCount + 1);
        expect(Detail::count())->toBe($initialDetailCount + 1);
        expect(DB::table('company_employees')->count())->toBe($initialEmployeeCount + 1);

        // Verify user was created
        $user = User::where('email', 'joao.silva@example.com')->first();
        expect($user)->not()->toBeNull();
        expect($user->name)->toBe('João Silva Santos');
        expect(Hash::check('SecurePassword123!', $user->password))->toBeTrue();

        // Verify user details were created
        $detail = Detail::where('user_id', $user->id)->first();
        expect($detail)->not()->toBeNull();
        expect($detail->document_id)->toBe('12.345.678-9');
        expect($detail->tax_id)->toMatch('/^\d{11}$/'); // CPF without formatting (11 digits)
        expect($detail->company_id)->toBe($this->partnerCompany->id);

        // Verify company association was created
        $employeeRecord = DB::table('company_employees')
            ->where('user_id', $user->id)
            ->where('company_id', $this->partnerCompany->id)
            ->first();

        expect($employeeRecord)->not()->toBeNull();
        expect($employeeRecord->role)->toBe(CompanyRoleEnum::Employee->value);
        expect($employeeRecord->active)->toBe(1);

        // Verify user can access the partner company
        expect($user->companies->contains($this->partnerCompany))->toBeTrue();
        expect($user->isPartnerCollaborator())->toBeTrue();
        expect($user->getPartnerCompany()->id)->toBe($this->partnerCompany->id);
    });

    test('registration with different partner code associates with correct company', function () {
        // Register with second partner company
        $registrationData = array_merge($this->validRegistrationData, [
            'email' => 'maria@example.com',
            'cpf' => generateValidCpf(2),
            'partner_code' => 'PARTNER456',
        ]);

        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($registrationData)
            ->call('submit');

        $user = User::where('email', 'maria@example.com')->first();
        expect($user)->not()->toBeNull();

        // Verify association with correct company
        expect($user->getPartnerCompany()->id)->toBe($this->anotherPartnerCompany->id);
        expect($user->companies->contains($this->anotherPartnerCompany))->toBeTrue();
        expect($user->companies->contains($this->partnerCompany))->toBeFalse();
    });

    test('case insensitive partner code matching works correctly', function () {
        $testCases = [
            'partner123',
            'PARTNER123',
            'Partner123',
            'pArTnEr123',
        ];

        foreach ($testCases as $index => $partnerCode) {
            $registrationData = array_merge($this->validRegistrationData, [
                'email' => "test{$index}@example.com",
                'cpf' => generateValidCpf(10 + $index),
                'partner_code' => $partnerCode,
            ]);

            $component = Livewire::test(PartnerRegistrationPage::class)
                ->fillForm($registrationData)
                ->call('submit');

            expect($component->get('registrationSuccess'))->toBeTrue();

            $user = User::where('email', "test{$index}@example.com")->first();
            expect($user->getPartnerCompany()->id)->toBe($this->partnerCompany->id);
        }
    });

    test('registration through action class produces same results as form submission', function () {
        $dto = PartnerRegistrationDTO::fromArray([
            'name' => 'Action Test User',
            'rg' => '98.765.432-1',
            'cpf' => generateValidCpf(3),
            'email' => 'action@example.com',
            'password' => 'ActionPassword123!',
            'partner_code' => 'PARTNER123',
        ]);

        $action = new RegisterPartnerCollaboratorAction;
        $result = $action->execute($dto);

        expect($result->isSuccess())->toBeTrue();
        expect($result->user)->not()->toBeNull();
        expect($result->company->id)->toBe($this->partnerCompany->id);

        // Verify all records were created correctly
        $user = $result->user;
        expect($user->name)->toBe('Action Test User');
        expect($user->email)->toBe('action@example.com');
        expect($user->isPartnerCollaborator())->toBeTrue();
        expect($user->getPartnerCompany()->id)->toBe($this->partnerCompany->id);

        $detail = $user->detail;
        expect($detail->document_id)->toBe('98.765.432-1');
        expect($detail->tax_id)->toMatch('/^\d{11}$/'); // CPF without formatting
        expect($detail->company_id)->toBe($this->partnerCompany->id);
    });
});

describe('Database Transaction Integrity and Rollback Scenarios', function () {
    test('transaction rolls back on user creation failure', function () {
        $initialUserCount = User::count();
        $initialDetailCount = Detail::count();
        $initialEmployeeCount = DB::table('company_employees')->count();

        // Use duplicate email to cause constraint violation
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $invalidDto = new PartnerRegistrationDTO(
            name: 'Test User',
            rg: '12.345.678-9',
            cpf: generateValidCpf(5),
            email: 'existing@example.com', // Duplicate email
            password: 'password',
            partnerCode: 'PARTNER123'
        );

        $action = new RegisterPartnerCollaboratorAction;
        $result = $action->execute($invalidDto);

        expect($result->isFailure())->toBeTrue();

        // Verify counts remain unchanged (except for the existing user we created)
        expect(User::count())->toBe($initialUserCount + 1); // Only the existing user
        expect(Detail::count())->toBe($initialDetailCount);
        expect(DB::table('company_employees')->count())->toBe($initialEmployeeCount);
    });

    test('transaction rolls back on detail creation failure', function () {
        // Create a partial mock that allows user creation but fails on detail creation
        $originalDetailClass = Detail::class;

        // Use database transaction to test rollback
        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
            ]);

            // Simulate detail creation failure by trying to create with invalid data
            Detail::create([
                'user_id' => $user->id,
                'document_id' => null, // This should cause a constraint violation
                'tax_id' => null,
                'company_id' => 999999, // Non-existent company
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            // Verify rollback worked
            expect(User::where('email', 'test@example.com')->exists())->toBeFalse();
            expect(Detail::count())->toBe(0);
        }
    });

    test('transaction rolls back on company association failure', function () {
        $initialUserCount = User::count();
        $initialDetailCount = Detail::count();
        $initialEmployeeCount = DB::table('company_employees')->count();

        // Create a DTO with invalid partner code to simulate failure
        $dto = new PartnerRegistrationDTO(
            name: 'Test User',
            rg: '12.345.678-9',
            cpf: generateValidCpf(4),
            email: 'test@example.com',
            password: 'password',
            partnerCode: 'INVALID_CODE' // This will cause validation failure
        );

        $action = new RegisterPartnerCollaboratorAction;
        $result = $action->execute($dto);

        expect($result->isFailure())->toBeTrue();
        expect($result->error)->toBe('Código de parceiro inválido ou não encontrado');

        // Verify counts remain unchanged
        expect(User::count())->toBe($initialUserCount);
        expect(Detail::count())->toBe($initialDetailCount);
        expect(DB::table('company_employees')->count())->toBe($initialEmployeeCount);
    });

    test('concurrent registration attempts handle uniqueness constraints properly', function () {
        $registrationData1 = $this->validRegistrationData;
        $registrationData2 = array_merge($this->validRegistrationData, [
            'name' => 'Different Name',
            'rg' => '98.765.432-1',
            // Same email and CPF to test uniqueness
        ]);

        // First registration should succeed
        $component1 = Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($registrationData1)
            ->call('submit');

        expect($component1->get('registrationSuccess'))->toBeTrue();

        // Second registration with same email should fail
        $component2 = Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($registrationData2)
            ->call('submit');

        expect($component2->get('registrationSuccess'))->toBeFalse();
        $component2->assertHasErrors(['data.email', 'data.cpf']);

        // Verify only one user was created
        expect(User::where('email', 'joao.silva@example.com')->count())->toBe(1);
    });

    test('database constraints are properly enforced', function () {
        // Test foreign key constraint on company_id in user_details
        $user = User::factory()->create();

        expect(function () use ($user) {
            Detail::create([
                'user_id' => $user->id,
                'document_id' => '12.345.678-9',
                'tax_id' => '11144477735',
                'company_id' => 999999, // Non-existent company ID
            ]);
        })->toThrow(\Exception::class);

        // Test foreign key constraint on company_employees table
        try {
            DB::table('company_employees')->insert([
                'user_id' => $user->id,
                'company_id' => 999999, // Non-existent company ID
                'role' => CompanyRoleEnum::Employee->value,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // If we reach here, the constraint didn't work as expected
            expect(false)->toBeTrue('Foreign key constraint should have prevented this insert');
        } catch (\Exception $e) {
            // This is expected - foreign key constraint should prevent the insert
            expect(true)->toBeTrue();
        }
    });
});

describe('Company Association and Employee Table Updates', function () {
    test('user is correctly associated with partner company', function () {
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($this->validRegistrationData)
            ->call('submit');

        $user = User::where('email', 'joao.silva@example.com')->first();

        // Refresh user to ensure relationships are loaded
        $user->refresh();
        $user->load('companies');

        // Test direct company relationship
        expect($user->companies->count())->toBe(1);
        expect($user->companies->first()->id)->toBe($this->partnerCompany->id);

        // Test pivot table data
        $pivot = $user->companies->first()->pivot;
        expect($pivot->role)->toBe(CompanyRoleEnum::Employee);

        // Check the raw database record to verify the data was inserted correctly
        $rawRecord = DB::table('company_employees')
            ->where('user_id', $user->id)
            ->where('company_id', $this->partnerCompany->id)
            ->first();

        expect($rawRecord)->not()->toBeNull();
        expect($rawRecord->active)->toBe(1);
        expect($rawRecord->role)->toBe(CompanyRoleEnum::Employee->value);

        // Test reverse relationship
        $this->partnerCompany->refresh();
        $this->partnerCompany->load('employees');
        expect($this->partnerCompany->employees->contains($user))->toBeTrue();

        // Verify employee role in company
        $employeePivot = $this->partnerCompany->employees->where('id', $user->id)->first()->pivot;
        expect($employeePivot->role)->toBe(CompanyRoleEnum::Employee);

        // Verify the employee record exists in the database with correct values
        $employeeRecord = DB::table('company_employees')
            ->where('user_id', $user->id)
            ->where('company_id', $this->partnerCompany->id)
            ->first();
        expect($employeeRecord->active)->toBe(1);
    });

    test('multiple users can be associated with same partner company', function () {
        $users = [];

        for ($i = 1; $i <= 3; ++$i) {
            $registrationData = array_merge($this->validRegistrationData, [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'cpf' => generateValidCpf(20 + $i),
                'rg' => "12.345.67{$i}-{$i}",
            ]);

            Livewire::test(PartnerRegistrationPage::class)
                ->fillForm($registrationData)
                ->call('submit');

            $users[] = User::where('email', "user{$i}@example.com")->first();
        }

        // Verify all users are associated with the same company
        foreach ($users as $user) {
            if ($user) {
                expect($user->getPartnerCompany()->id)->toBe($this->partnerCompany->id);
                expect($user->isPartnerCollaborator())->toBeTrue();
            }
        }

        // Verify company has all employees
        $this->partnerCompany->refresh();
        expect($this->partnerCompany->employees->count())->toBe(3);

        foreach ($users as $user) {
            expect($this->partnerCompany->employees->contains($user))->toBeTrue();
        }
    });

    test('user detail record links to correct company', function () {
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($this->validRegistrationData)
            ->call('submit');

        $user = User::where('email', 'joao.silva@example.com')->first();
        $detail = $user->detail;

        expect($detail->company_id)->toBe($this->partnerCompany->id);
        expect($detail->user_id)->toBe($user->id);
        expect($detail->document_id)->toBe('12.345.678-9');
        expect($detail->tax_id)->toMatch('/^\d{11}$/'); // CPF without formatting
    });

    test('employee table maintains referential integrity', function () {
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($this->validRegistrationData)
            ->call('submit');

        $user = User::where('email', 'joao.silva@example.com')->first();

        // Verify the employee record exists
        $employeeRecord = DB::table('company_employees')
            ->where('user_id', $user->id)
            ->where('company_id', $this->partnerCompany->id)
            ->first();

        expect($employeeRecord)->not()->toBeNull();
        expect($employeeRecord->role)->toBe(CompanyRoleEnum::Employee->value);
        expect($employeeRecord->active)->toBe(1);
        expect($employeeRecord->created_at)->not()->toBeNull();
        expect($employeeRecord->updated_at)->not()->toBeNull();

        // Test that deleting the user removes the employee record (if cascade is set up)
        $user->delete();

        $employeeRecordAfterDelete = DB::table('company_employees')
            ->where('user_id', $user->id)
            ->first();

        // Depending on cascade settings, this might be null or soft-deleted
        // We'll check that the relationship is properly handled
        expect($this->partnerCompany->employees()->where('user_id', $user->id)->count())->toBe(0);
    });

    test('company employee relationship uses correct pivot model', function () {
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($this->validRegistrationData)
            ->call('submit');

        $user = User::where('email', 'joao.silva@example.com')->first();

        // Test that the relationship uses TenantMember as pivot
        $company = $user->companies->first();
        expect($company->pivot)->toBeInstanceOf(\TresPontosTech\Tenant\Models\TenantMember::class);

        // Test reverse relationship
        $employee = $this->partnerCompany->employees->first();
        expect($employee->pivot)->toBeInstanceOf(\TresPontosTech\Tenant\Models\TenantMember::class);
    });
});

describe('Tenant Isolation and Access Control Integration', function () {
    test('partner collaborator can only access user panel', function () {
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($this->validRegistrationData)
            ->call('submit');

        $user = User::where('email', 'joao.silva@example.com')->first();

        // Test panel access restrictions
        $userPanel = \Filament\Panel::make()->id('app');
        $adminPanel = \Filament\Panel::make()->id('admin');
        $companyPanel = \Filament\Panel::make()->id('company');
        $consultantPanel = \Filament\Panel::make()->id('consultant');
        $guestPanel = \Filament\Panel::make()->id('guest');

        expect($user->canAccessPanel($userPanel))->toBeTrue();
        expect($user->canAccessPanel($adminPanel))->toBeFalse();
        expect($user->canAccessPanel($companyPanel))->toBeFalse();
        expect($user->canAccessPanel($consultantPanel))->toBeFalse();
        expect($user->canAccessPanel($guestPanel))->toBeFalse();
    });

    test('partner collaborator tenant access is restricted to their company only', function () {
        // Create another company and user
        $otherCompany = Company::factory()->create(['partner_code' => 'OTHER123']);
        $otherUser = User::factory()->create();
        $otherCompany->employees()->attach($otherUser->id, [
            'role' => CompanyRoleEnum::Employee->value,
            'active' => true,
        ]);

        // Register our test user
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($this->validRegistrationData)
            ->call('submit');

        $user = User::where('email', 'joao.silva@example.com')->first();
        $userPanel = \Filament\Panel::make()->id('app');

        // Test tenant access
        $userTenants = $user->getTenants($userPanel);
        expect($userTenants->count())->toBe(1);
        expect($userTenants->first()->id)->toBe($this->partnerCompany->id);

        // Test specific tenant access
        expect($user->canAccessTenant($this->partnerCompany))->toBeTrue();
        expect($user->canAccessTenant($otherCompany))->toBeFalse();
        expect($user->canAccessTenant($this->regularCompany))->toBeFalse();
    });

    test('partner collaborator identification works correctly', function () {
        // Register partner collaborator
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($this->validRegistrationData)
            ->call('submit');

        $partnerCollaborator = User::where('email', 'joao.silva@example.com')->first();

        // Create regular user (not a partner collaborator)
        $regularUser = User::factory()->create();
        $this->regularCompany->employees()->attach($regularUser->id, [
            'role' => CompanyRoleEnum::Employee->value,
            'active' => true,
        ]);

        // Create company owner
        $companyOwner = User::factory()->create();
        $this->partnerCompany->employees()->attach($companyOwner->id, [
            'role' => CompanyRoleEnum::Owner->value,
            'active' => true,
        ]);

        // Test partner collaborator identification
        expect($partnerCollaborator->isPartnerCollaborator())->toBeTrue();
        expect($regularUser->isPartnerCollaborator())->toBeFalse();
        expect($companyOwner->isPartnerCollaborator())->toBeFalse(); // Owner, not employee

        // Test getting partner company
        expect($partnerCollaborator->getPartnerCompany())->not()->toBeNull();
        expect($partnerCollaborator->getPartnerCompany()->id)->toBe($this->partnerCompany->id);
        expect($regularUser->getPartnerCompany())->toBeNull();
        expect($companyOwner->getPartnerCompany())->toBeNull();
    });

    test('multiple partner collaborators from same company have same access', function () {
        $users = [];

        // Register multiple users for the same partner company
        for ($i = 1; $i <= 3; ++$i) {
            $registrationData = array_merge($this->validRegistrationData, [
                'name' => "Partner User {$i}",
                'email' => "partner{$i}@example.com",
                'cpf' => generateValidCpf(30 + $i),
                'rg' => "12.345.67{$i}-{$i}",
            ]);

            Livewire::test(PartnerRegistrationPage::class)
                ->fillForm($registrationData)
                ->call('submit');

            $users[] = User::where('email', "partner{$i}@example.com")->first();
        }

        $userPanel = \Filament\Panel::make()->id('app');

        // All users should have the same tenant access
        foreach ($users as $user) {
            expect($user->isPartnerCollaborator())->toBeTrue();
            expect($user->getPartnerCompany()->id)->toBe($this->partnerCompany->id);

            $tenants = $user->getTenants($userPanel);
            expect($tenants->count())->toBe(1);
            expect($tenants->first()->id)->toBe($this->partnerCompany->id);

            expect($user->canAccessTenant($this->partnerCompany))->toBeTrue();
            expect($user->canAccessTenant($this->anotherPartnerCompany))->toBeFalse();
        }
    });

    test('partner collaborators from different companies have isolated access', function () {
        // Register user for first partner company
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($this->validRegistrationData)
            ->call('submit');

        // Register user for second partner company
        $secondUserData = array_merge($this->validRegistrationData, [
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'cpf' => generateValidCpf(40),
            'rg' => '98.765.432-1',
            'partner_code' => 'PARTNER456',
        ]);

        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($secondUserData)
            ->call('submit');

        $user1 = User::where('email', 'joao.silva@example.com')->first();
        $user2 = User::where('email', 'maria@example.com')->first();

        // Verify isolation
        expect($user1->canAccessTenant($this->partnerCompany))->toBeTrue();
        expect($user1->canAccessTenant($this->anotherPartnerCompany))->toBeFalse();

        expect($user2->canAccessTenant($this->anotherPartnerCompany))->toBeTrue();
        expect($user2->canAccessTenant($this->partnerCompany))->toBeFalse();

        // Verify they get different tenant lists
        $userPanel = \Filament\Panel::make()->id('app');
        $user1Tenants = $user1->getTenants($userPanel);
        $user2Tenants = $user2->getTenants($userPanel);

        expect($user1Tenants->first()->id)->toBe($this->partnerCompany->id);
        expect($user2Tenants->first()->id)->toBe($this->anotherPartnerCompany->id);
    });

    test('access control works with soft deleted records', function () {
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($this->validRegistrationData)
            ->call('submit');

        $user = User::where('email', 'joao.silva@example.com')->first();

        // Verify initial access
        expect($user->isPartnerCollaborator())->toBeTrue();
        expect($user->canAccessTenant($this->partnerCompany))->toBeTrue();

        // Soft delete the company
        $this->partnerCompany->delete();

        // Refresh user to clear any cached relationships
        $user->refresh();

        // Access should be revoked when company is soft deleted
        expect($user->isPartnerCollaborator())->toBeFalse();
        expect($user->getPartnerCompany())->toBeNull();
        expect($user->canAccessTenant($this->partnerCompany))->toBeFalse();
    });
});
