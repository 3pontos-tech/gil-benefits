<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Database\Seeders;

use App\Models\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use TresPontosTech\Billing\Core\Enums\CompanyPlanKindEnum;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Company\Actions\CreateCompanyAction;
use TresPontosTech\Company\DTOs\CompanyDTO;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;

/**
 * Uma empresa parceira pronta para receber campanhas: dono, CNPJ válido e contrato
 * `credits_only` vigente. Não gera lote nenhum — isso é o admin quem faz pelo painel.
 *
 * Idempotente: roda quantas vezes quiser sem duplicar empresa, dono ou contrato.
 */
class VoucherPartnerCompanySeeder extends Seeder
{
    public const COMPANY_SLUG = 'incorporadora-alfa';

    public const OWNER_EMAIL = 'alfa@5pontos.com';

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $this->call(VoucherProgramPlanSeeder::class);

        $owner = $this->owner();
        $company = $this->company($owner);

        CompanyPlan::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'plan_id' => Plan::query()->where('slug', VoucherProgramPlanSeeder::SLUG)->sole()->getKey(),
            ],
            [
                'kind' => CompanyPlanKindEnum::CreditsOnly,
                'status' => CompanyPlanStatusEnum::Active,
                'seats' => 50,
                'monthly_value_cents' => null,
                'monthly_appointments_per_employee' => null,
                'starts_at' => today(),
                'ends_at' => now()->addMonths(3)->endOfDay(),
                'notes' => 'Parceria de campanha — consultoria por voucher.',
            ],
        );
    }

    private function owner(): User
    {
        $owner = User::query()->firstWhere('email', self::OWNER_EMAIL);

        if ($owner instanceof User) {
            return $owner;
        }

        $owner = User::query()->create([
            'name' => 'Ana Alfa',
            'email' => self::OWNER_EMAIL,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        event(new UserRegistered($owner, Roles::Employee));

        $owner->detail()->create([
            'tax_id' => '39053344705',
            'document_id' => '123456789',
        ]);

        return $owner;
    }

    private function company(User $owner): Company
    {
        $company = Company::query()->firstWhere('slug', self::COMPANY_SLUG);

        if (! $company instanceof Company) {
            $company = resolve(CreateCompanyAction::class)->execute(CompanyDTO::make([
                'name' => 'Incorporadora Alfa',
                'slug' => self::COMPANY_SLUG,
                'tax_id' => '17518432000191',
                'integration_access_key' => (string) Uuid::uuid4(),
                'user_id' => $owner->getKey(),
            ]));
        }

        $owner->assignRole(Roles::CompanyOwner->value);

        return $company;
    }
}
