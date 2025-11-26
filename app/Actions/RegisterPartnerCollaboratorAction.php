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

class RegisterPartnerCollaboratorAction
{
    /**
     * Register a new partner collaborator
     */
    public function execute(PartnerRegistrationDTO $dto): RegistrationResult
    {
        // Validate partner code
        $company = $this->validatePartnerCode($dto->partnerCode);
        if (!$company) {
            return RegistrationResult::failure('Código de parceiro inválido ou não encontrado');
        }

        // Validate CPF format
        if (!CpfValidator::validate($dto->cpf)) {
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
     * Validate partner code against companies table (case-insensitive)
     */
    private function validatePartnerCode(string $partnerCode): ?Company
    {
        return Company::whereRaw('LOWER(partner_code) = LOWER(?)', [$partnerCode])->first();
    }

    /**
     * Check if email already exists
     */
    private function emailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * Check if CPF already exists
     */
    private function cpfExists(string $cpf): bool
    {
        $cleanCpf = CpfValidator::clean($cpf);
        return Detail::where('tax_id', $cleanCpf)->exists();
    }

    /**
     * Create user record with hashed password
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
     * Create user details record with RG and CPF
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
     * Associate user with company via company_employees table with Employee role
     */
    private function associateUserWithCompany(User $user, Company $company): void
    {
        $company->employees()->attach($user->id, [
            'role' => CompanyRoleEnum::Employee->value,
            'active' => true,
        ]);
    }
}