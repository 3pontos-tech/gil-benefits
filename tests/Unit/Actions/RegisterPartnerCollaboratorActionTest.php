<?php

namespace Tests\Unit\Actions;

use App\Actions\RegisterPartnerCollaboratorAction;
use App\DTO\PartnerRegistrationDTO;
use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Company\Models\Company;

class RegisterPartnerCollaboratorActionTest extends TestCase
{
    use RefreshDatabase;

    private RegisterPartnerCollaboratorAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new RegisterPartnerCollaboratorAction();
    }

    public function test_successful_registration(): void
    {
        // Arrange
        $company = Company::factory()->create([
            'partner_code' => 'PARTNER123',
        ]);

        $dto = new PartnerRegistrationDTO(
            name: 'João Silva',
            rg: '12.345.678-9',
            cpf: '111.444.777-35',
            email: 'joao@example.com',
            password: 'password123',
            partnerCode: 'PARTNER123'
        );

        // Act
        $result = $this->action->execute($dto);

        // Assert
        $this->assertTrue($result->isSuccess());
        $this->assertNotNull($result->user);
        $this->assertNotNull($result->company);
        $this->assertEquals($company->id, $result->company->id);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
        ]);

        // Verify user details were created
        $this->assertDatabaseHas('user_details', [
            'user_id' => $result->user->id,
            'document_id' => '12.345.678-9',
            'tax_id' => '11144477735', // CPF without formatting
            'company_id' => $company->id,
        ]);

        // Verify company association
        $this->assertDatabaseHas('company_employees', [
            'user_id' => $result->user->id,
            'company_id' => $company->id,
            'role' => CompanyRoleEnum::Employee->value,
            'active' => true,
        ]);
    }

    public function test_invalid_partner_code(): void
    {
        // Arrange
        $dto = new PartnerRegistrationDTO(
            name: 'João Silva',
            rg: '12.345.678-9',
            cpf: '123.456.789-09',
            email: 'joao@example.com',
            password: 'password123',
            partnerCode: 'INVALID_CODE'
        );

        // Act
        $result = $this->action->execute($dto);

        // Assert
        $this->assertTrue($result->isFailure());
        $this->assertEquals('Código de parceiro inválido ou não encontrado', $result->error);
        $this->assertNull($result->user);
        $this->assertNull($result->company);
    }

    public function test_case_insensitive_partner_code_validation(): void
    {
        // Arrange
        $company = Company::factory()->create([
            'partner_code' => 'PARTNER123',
        ]);

        $dto = new PartnerRegistrationDTO(
            name: 'João Silva',
            rg: '12.345.678-9',
            cpf: '111.444.777-35',
            email: 'joao@example.com',
            password: 'password123',
            partnerCode: 'partner123' // lowercase
        );

        // Act
        $result = $this->action->execute($dto);

        // Assert
        $this->assertTrue($result->isSuccess());
        $this->assertEquals($company->id, $result->company->id);
    }

    public function test_invalid_cpf_format(): void
    {
        // Arrange
        Company::factory()->create([
            'partner_code' => 'PARTNER123',
        ]);

        $dto = new PartnerRegistrationDTO(
            name: 'João Silva',
            rg: '12.345.678-9',
            cpf: '111.111.111-11', // Invalid CPF (all same digits)
            email: 'joao@example.com',
            password: 'password123',
            partnerCode: 'PARTNER123'
        );

        // Act
        $result = $this->action->execute($dto);

        // Assert
        $this->assertTrue($result->isFailure());
        $this->assertEquals('CPF inválido. Verifique o formato', $result->error);
    }

    public function test_duplicate_email(): void
    {
        // Arrange
        Company::factory()->create([
            'partner_code' => 'PARTNER123',
        ]);

        User::factory()->create([
            'email' => 'joao@example.com',
        ]);

        $dto = new PartnerRegistrationDTO(
            name: 'João Silva',
            rg: '12.345.678-9',
            cpf: '111.444.777-35',
            email: 'joao@example.com', // Duplicate email
            password: 'password123',
            partnerCode: 'PARTNER123'
        );

        // Act
        $result = $this->action->execute($dto);

        // Assert
        $this->assertTrue($result->isFailure());
        $this->assertEquals('Este email já está cadastrado no sistema', $result->error);
    }

    public function test_duplicate_cpf(): void
    {
        // Arrange
        Company::factory()->create([
            'partner_code' => 'PARTNER123',
        ]);

        $existingUser = User::factory()->create();
        Detail::factory()->create([
            'user_id' => $existingUser->id,
            'tax_id' => '11144477735', // Same CPF without formatting
        ]);

        $dto = new PartnerRegistrationDTO(
            name: 'João Silva',
            rg: '12.345.678-9',
            cpf: '111.444.777-35', // Duplicate CPF (formatted)
            email: 'joao@example.com',
            password: 'password123',
            partnerCode: 'PARTNER123'
        );

        // Act
        $result = $this->action->execute($dto);

        // Assert
        $this->assertTrue($result->isFailure());
        $this->assertEquals('Este CPF já está cadastrado no sistema', $result->error);
    }

    public function test_password_is_hashed(): void
    {
        // Arrange
        $company = Company::factory()->create([
            'partner_code' => 'PARTNER123',
        ]);

        $dto = new PartnerRegistrationDTO(
            name: 'João Silva',
            rg: '12.345.678-9',
            cpf: '111.444.777-35',
            email: 'joao@example.com',
            password: 'password123',
            partnerCode: 'PARTNER123'
        );

        // Act
        $result = $this->action->execute($dto);

        // Assert
        $this->assertTrue($result->isSuccess());
        
        $user = User::find($result->user->id);
        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(\Hash::check('password123', $user->password));
    }

    public function test_cpf_is_stored_without_formatting(): void
    {
        // Arrange
        $company = Company::factory()->create([
            'partner_code' => 'PARTNER123',
        ]);

        $dto = new PartnerRegistrationDTO(
            name: 'João Silva',
            rg: '12.345.678-9',
            cpf: '111.444.777-35', // Formatted CPF
            email: 'joao@example.com',
            password: 'password123',
            partnerCode: 'PARTNER123'
        );

        // Act
        $result = $this->action->execute($dto);

        // Assert
        $this->assertTrue($result->isSuccess());
        
        $detail = Detail::where('user_id', $result->user->id)->first();
        $this->assertEquals('11144477735', $detail->tax_id); // Stored without formatting
    }

    public function test_transaction_rollback_on_failure(): void
    {
        // Arrange
        $company = Company::factory()->create([
            'partner_code' => 'PARTNER123',
        ]);

        // Mock a scenario where user creation succeeds but detail creation fails
        // by creating a user with the same email first
        User::factory()->create(['email' => 'joao@example.com']);

        $dto = new PartnerRegistrationDTO(
            name: 'João Silva',
            rg: '12.345.678-9',
            cpf: '111.444.777-35',
            email: 'joao@example.com',
            password: 'password123',
            partnerCode: 'PARTNER123'
        );

        $initialUserCount = User::count();

        // Act
        $result = $this->action->execute($dto);

        // Assert
        $this->assertTrue($result->isFailure());
        $this->assertEquals($initialUserCount, User::count()); // No new users created
    }

    public function test_user_is_assigned_employee_role(): void
    {
        // Arrange
        $company = Company::factory()->create([
            'partner_code' => 'PARTNER123',
        ]);

        $dto = new PartnerRegistrationDTO(
            name: 'João Silva',
            rg: '12.345.678-9',
            cpf: '111.444.777-35',
            email: 'joao@example.com',
            password: 'password123',
            partnerCode: 'PARTNER123'
        );

        // Act
        $result = $this->action->execute($dto);

        // Assert
        $this->assertTrue($result->isSuccess());
        
        $user = $result->user;
        $companyEmployee = $company->employees()->where('user_id', $user->id)->first();
        
        $this->assertNotNull($companyEmployee);
        $this->assertEquals(CompanyRoleEnum::Employee, $companyEmployee->pivot->role);
        $this->assertTrue($companyEmployee->pivot->active);
    }

    public function test_valid_cpf_formats_are_accepted(): void
    {
        // Arrange
        $company = Company::factory()->create([
            'partner_code' => 'PARTNER123',
        ]);

        $validCpfs = [
            '111.444.777-35',
            '529.982.247-25',
            '123.456.789-09',
            '987.654.321-00',
        ];

        foreach ($validCpfs as $index => $cpf) {
            $dto = new PartnerRegistrationDTO(
                name: "User {$index}",
                rg: "12.345.678-{$index}",
                cpf: $cpf,
                email: "user{$index}@example.com",
                password: 'password123',
                partnerCode: 'PARTNER123'
            );

            // Act
            $result = $this->action->execute($dto);

            // Assert
            $this->assertTrue($result->isSuccess(), "Failed for CPF: {$cpf}. Error: " . ($result->error ?? 'No error'));
        }
    }
}