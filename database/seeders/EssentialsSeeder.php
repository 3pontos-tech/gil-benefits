<?php

namespace Database\Seeders;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\Permissions\Roles;

class EssentialsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'staging', 'testing'])) {
            return;
        }

        Queue::fake();
        Mail::fake();

        Artisan::call('sync:permissions');

        $admin = User::factory()->superAdmin()->createQuietly();
        $companyOwner = $this->createCompanyOwner();
        $company = $this->createCompany($companyOwner);
        $employee = $this->createEmployee($company);
        $consultant = $this->createConsultant();

        $this->seedEmployeeAppointments($employee, $company, $consultant);
        $this->seedEmployeeCredits($employee, $company);
        $this->seedEmployeeOwnedDocuments($employee);
        $this->seedDocumentsSharedWithEmployee($employee, $consultant);

        $company->employees()->attach($admin);
    }

    private function createCompanyOwner(): User
    {
        return User::factory()
            ->companyOwner()
            ->createQuietly([
                'name' => 'Company Owner',
                'email' => 'company@5pontos.com',
                'password' => Hash::make('password'),
            ]);
    }

    private function createCompany(User $owner): Company
    {
        $company = Company::factory()->create([
            'name' => '5Pontos',
            'slug' => '5pontos',
            'user_id' => $owner->getKey(),
        ]);

        $company->employees()->attach($owner, ['role' => Roles::CompanyOwner->value]);

        $plan = Plan::factory()
            ->contractual()
            ->create([
                'type' => BillableTypeEnum::Company,
                'name' => 'Plano Bem-Estar Financeiro',
                'description' => 'Consultorias financeiras mensais com especialistas, além de materiais exclusivos para os colaboradores.',
            ]);

        CompanyPlan::factory()
            ->active()
            ->create([
                'company_id' => $company->getKey(),
                'plan_id' => $plan->getKey(),
                'monthly_appointments_per_employee' => 2,
            ]);

        return $company;
    }

    private function createEmployee(Company $company): User
    {
        $employee = User::factory()
            ->employee()
            ->has(Detail::factory())
            ->createQuietly([
                'name' => 'Employee Teste',
                'email' => 'employee@5pontos.com',
                'password' => Hash::make('password'),
            ]);

        $company->employees()->attach($employee);

        return $employee;
    }

    private function createConsultant(): Consultant
    {
        User::factory()->createQuietly([
            'name' => 'Consultor Teste',
            'email' => 'consultant@5pontos.com',
            'password' => Hash::make('password'),
        ]);

        $this->call([ConsultantSeeder::class]);

        return Consultant::query()->create([
            'name' => 'Consultor Teste',
            'slug' => 'consultor-teste',
            'phone' => '+5511900000000',
            'email' => 'consultant@5pontos.com',
            'short_description' => 'Consultor de teste para o painel consultor.',
            'biography' => 'Consultor de teste para o painel consultor.',
            'readme' => 'Consultor de teste para o painel consultor.',
            'socials_urls' => [],
        ]);
    }

    private function seedEmployeeAppointments(User $employee, Company $company, Consultant $consultant): void
    {
        $appointments = [
            [AppointmentStatus::Completed, AppointmentCategoryEnum::PersonalFinance, now()->subDays(36), true],
            [AppointmentStatus::Completed, AppointmentCategoryEnum::InvestmentAdvisory, now()->subDays(24), true],
            [AppointmentStatus::Completed, AppointmentCategoryEnum::RetirementAndEstatePlanning, now()->subDays(12), false],
            [AppointmentStatus::Cancelled, AppointmentCategoryEnum::FundraisingAndCredit, now()->subDays(6), false],
            [AppointmentStatus::Completed, AppointmentCategoryEnum::PersonalFinance, now()->subHours(2), false],
            [AppointmentStatus::Active, AppointmentCategoryEnum::InvestmentAdvisory, now()->addDay(), false],
        ];

        foreach ($appointments as [$status, $category, $scheduledAt, $rated]) {
            $appointment = Appointment::factory()->create([
                'user_id' => $employee->getKey(),
                'consultant_id' => $consultant->getKey(),
                'company_id' => $company->getKey(),
                'status' => $status,
                'category_type' => $category,
                'appointment_at' => $scheduledAt,
            ]);

            if ($rated) {
                AppointmentFeedback::factory()->create([
                    'appointment_id' => $appointment->getKey(),
                    'user_id' => $employee->getKey(),
                    'rating' => 5,
                ]);
            }
        }
    }

    private function seedEmployeeCredits(User $employee, Company $company): void
    {
        UserCredit::factory()
            ->available()
            ->count(2)
            ->create([
                'owner_id' => $employee->getKey(),
                'holder_id' => $employee->getKey(),
                'company_id' => $company->getKey(),
            ]);
    }

    private function seedEmployeeOwnedDocuments(User $employee): void
    {
        $documents = [
            ['Anamnese - Histórico Pessoal', DocumentExtensionTypeEnum::PDF],
            ['Comprovante de Renda 2026', DocumentExtensionTypeEnum::XLSX],
        ];

        foreach ($documents as [$title, $type]) {
            Document::factory()
                ->forUser($employee)
                ->active()
                ->withFile($type)
                ->create(['title' => $title]);
        }
    }

    private function seedDocumentsSharedWithEmployee(User $employee, Consultant $consultant): void
    {
        $documents = [
            ['Proposta de Investimento Personalizada', DocumentExtensionTypeEnum::PDF],
            ['Análise de Carteira de Ações', DocumentExtensionTypeEnum::XLSX],
        ];

        foreach ($documents as [$title, $type]) {
            $document = Document::factory()
                ->forConsultant($consultant)
                ->active()
                ->withFile($type)
                ->create(['title' => $title]);

            DocumentShare::factory()
                ->for($document, 'document')
                ->for($consultant, 'consultant')
                ->for($employee, 'employee')
                ->active()
                ->create();
        }
    }
}
