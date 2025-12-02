<?php

namespace App\Actions;

use App\DTO\PartnerRegistrationDTO;
use App\DTO\RegistrationResult;
use App\Models\Users\Detail;
use App\Models\Users\User;
use App\Utils\CpfValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Company\Models\Company;

/**
 * Action class responsible for registering new partner collaborators.
 * 
 * This action handles the complete registration process including:
 * - Partner code validation
 * - CPF format and uniqueness validation
 * - Email uniqueness validation
 * - User account creation
 * - User details creation with document information
 * - Company association with appropriate role assignment
 * 
 * The entire process is wrapped in a database transaction to ensure data consistency.
 * 
 * @package App\Actions
 * @author TresPontosTech Development Team
 * @since 1.0.0
 */
class RegisterPartnerCollaboratorAction
{
    /**
     * Execute the partner collaborator registration process.
     * 
     * This method orchestrates the complete registration workflow:
     * 1. Validates the provided partner code against existing companies
     * 2. Validates CPF format using Brazilian CPF validation rules
     * 3. Checks for duplicate email addresses in the system
     * 4. Checks for duplicate CPF numbers in user details
     * 5. Creates the user account with encrypted password
     * 6. Creates associated user details with document information
     * 7. Associates the user with the partner company as an employee
     * 
     * All operations are performed within a database transaction to ensure
     * atomicity. If any step fails, the entire process is rolled back.
     * 
     * @param PartnerRegistrationDTO $dto The registration data transfer object containing
     *                                   all required user information including name, email,
     *                                   password, CPF, RG, and partner code
     * 
     * @return RegistrationResult Success result with created user and company objects,
     *                           or failure result with descriptive error message
     * 
     * @throws \Exception When database transaction fails or unexpected errors occur
     * 
     * @example
     * ```php
     * $dto = PartnerRegistrationDTO::fromArray([
     *     'name' => 'João Silva',
     *     'email' => 'joao@example.com',
     *     'password' => 'SecurePass123!',
     *     'cpf' => '123.456.789-00',
     *     'rg' => '12.345.678-9',
     *     'partner_code' => 'PARTNER123'
     * ]);
     * 
     * $action = new RegisterPartnerCollaboratorAction();
     * $result = $action->execute($dto);
     * 
     * if ($result->isSuccess()) {
     *     $user = $result->getUser();
     *     $company = $result->getCompany();
     *     // Handle successful registration
     * } else {
     *     $errorMessage = $result->getErrorMessage();
     *     // Handle registration failure
     * }
     * ```
     */
    public function execute(PartnerRegistrationDTO $dto): RegistrationResult
    {
        // Validate partner code
        $company = $this->validatePartnerCode($dto->partnerCode);
        if (! $company) {
            return RegistrationResult::failure('Código de parceiro inválido ou não encontrado');
        }

        // Validate CPF format
        if (! CpfValidator::validate($dto->cpf)) {
            return RegistrationResult::failure('CPF inválido. Verifique o formato');
        }

        // Check for duplicate email
        if ($this->emailExists($dto->email)) {
            return RegistrationResult::failure('Este email já está cadastrado no sistema');
        }

        // Check for duplicate CPF
        if ($this->cpfExists($dto->cpf)) {
            return RegistrationResult::failure('Este CPF já está cadastrado no sistema');
        }

        try {
            return DB::transaction(function () use ($dto, $company) {
                // Create user
                $user = $this->createUser($dto);

                // Create user details
                $this->createUserDetails($user, $dto, $company);

                // Associate user with company
                $this->associateUserWithCompany($user, $company);

                return RegistrationResult::success($user, $company);
            });
        } catch (\Exception $e) {
            return RegistrationResult::failure('Erro interno do sistema. Tente novamente mais tarde.');
        }
    }

    /**
     * Validate partner code against companies table using case-insensitive matching.
     * 
     * This method performs a case-insensitive search for the provided partner code
     * in the companies table. Partner codes are unique identifiers that allow
     * users to register as collaborators for specific companies.
     * 
     * @param string $partnerCode The partner code to validate (case-insensitive)
     * 
     * @return Company|null The matching company if found, null if no match exists
     * 
     * @example
     * ```php
     * $company = $this->validatePartnerCode('PARTNER123');
     * if ($company) {
     *     // Valid partner code, proceed with registration
     * } else {
     *     // Invalid partner code, show error
     * }
     * ```
     */
    private function validatePartnerCode(string $partnerCode): ?Company
    {
        return Company::whereRaw('LOWER(partner_code) = LOWER(?)', [$partnerCode])->first();
    }

    /**
     * Check if the provided email address already exists in the system.
     * 
     * This method performs an exact match search for the email address
     * in the users table to prevent duplicate registrations.
     * 
     * @param string $email The email address to check for existence
     * 
     * @return bool True if email exists, false otherwise
     */
    private function emailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * Check if the provided CPF already exists in the system.
     * 
     * This method cleans the CPF (removes formatting) and searches for it
     * in the user details table to prevent duplicate registrations with
     * the same tax identification number.
     * 
     * @param string $cpf The CPF to check (can be formatted or unformatted)
     * 
     * @return bool True if CPF exists, false otherwise
     */
    private function cpfExists(string $cpf): bool
    {
        $cleanCpf = CpfValidator::clean($cpf);

        return Detail::where('tax_id', $cleanCpf)->exists();
    }

    /**
     * Create a new user record with securely hashed password.
     * 
     * This method creates the main user account with the provided name,
     * email, and password. The password is automatically hashed using
     * Laravel's default hashing algorithm (bcrypt).
     * 
     * @param PartnerRegistrationDTO $dto The registration data containing user information
     * 
     * @return User The newly created user model instance
     * 
     * @throws \Illuminate\Database\QueryException If user creation fails
     */
    private function createUser(PartnerRegistrationDTO $dto): User
    {
        return User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
        ]);
    }

    /**
     * Create user details record containing document information.
     * 
     * This method creates the user details record that stores additional
     * information such as RG (document ID) and CPF (tax ID). The CPF
     * is cleaned of formatting before storage.
     * 
     * @param User $user The user model to associate details with
     * @param PartnerRegistrationDTO $dto The registration data containing document information
     * @param Company $company The company to associate the user details with
     * 
     * @return Detail The newly created user details model instance
     * 
     * @throws \Illuminate\Database\QueryException If details creation fails
     */
    private function createUserDetails(User $user, PartnerRegistrationDTO $dto, Company $company): Detail
    {
        return Detail::create([
            'user_id' => $user->id,
            'document_id' => $dto->rg,
            'tax_id' => CpfValidator::clean($dto->cpf),
            'company_id' => $company->id,
        ]);
    }

    /**
     * Associate user with company through the company_employees pivot table.
     * 
     * This method creates the many-to-many relationship between the user
     * and company, assigning the Employee role and setting the association
     * as active. This allows the user to access company-specific features
     * and data within their assigned role permissions.
     * 
     * @param User $user The user to associate with the company
     * @param Company $company The company to associate the user with
     * 
     * @return void
     * 
     * @throws \Illuminate\Database\QueryException If association creation fails
     */
    private function associateUserWithCompany(User $user, Company $company): void
    {
        $company->employees()->attach($user->id, [
            'role' => CompanyRoleEnum::Employee->value,
            'active' => true,
        ]);
    }
}
