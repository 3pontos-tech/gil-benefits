<?php

namespace Database\Seeders;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;

class EssentialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Cria um usuário por tipo de painel (senha = "password" em todos):
     *   - admin@5pontos.com       → painel /admin       (SuperAdmin)
     *   - company@5pontos.com     → painel /company     (CompanyOwner)
     *   - consultant@5pontos.com  → painel /consultant  (Consultant)
     *   - employee@5pontos.com    → painel /app         (Employee)
     */
    public function run(): void
    {
        Artisan::call('sync:permissions');

        $admin = User::factory()
            ->superAdmin()
            ->createQuietly();

        $companyOwner = User::factory()
            ->companyOwner()
            ->createQuietly([
                'name' => 'Company Owner',
                'email' => 'company@5pontos.com',
                'password' => Hash::make('password'),
            ]);

        $company = Company::factory()->create([
            'name' => '5Pontos',
            'slug' => '5pontos',
            'user_id' => $companyOwner->getKey(),
        ]);

        CompanyPlan::factory()
            ->active()
            ->create(['company_id' => $company->getKey()]);

        $employee = User::factory()
            ->employee()
            ->has(Detail::factory())
            ->createQuietly([
                'name' => 'Employee Teste',
                'email' => 'employee@5pontos.com',
                'password' => Hash::make('password'),
            ]);
        $company->employees()->attach($employee);

        // Usuário criado antes do Consultant para que o ConsultantObserver
        // (firstOrCreate por email) preserve a senha 'password' definida aqui.
        // O role Consultant é atribuído pelo próprio observer via UserRegistered.
        User::factory()->createQuietly([
            'name' => 'Consultor Teste',
            'email' => 'consultant@5pontos.com',
            'password' => Hash::make('password'),
        ]);

        $this->call([
            ConsultantSeeder::class,
        ]);

        $testConsultant = Consultant::query()->create([
            'name' => 'Consultor Teste',
            'slug' => 'consultor-teste',
            'phone' => '+5511900000000',
            'email' => 'consultant@5pontos.com',
            'short_description' => 'Consultor de teste para o painel consultor.',
            'biography' => 'Consultor de teste para o painel consultor.',
            'readme' => 'Consultor de teste para o painel consultor.',
            'socials_urls' => [],
        ]);

        Appointment::factory()
            ->count(5)
            ->create([
                'user_id' => $employee->getKey(),
                'company_id' => $company->getKey(),
            ]);

        // Cenários de teste para a feature de Ata (FLM-18), refletindo o fluxo
        // real — a ata só existe depois que o consultor sobe o documento e a
        // IA processa. O seeder NÃO cria records pré-fabricados.
        //
        //  1. Agendamento recém-finalizado sem ata — habilita o botão "Criar
        //     Ata" no painel do consultor para testar upload + geração pela IA.
        //  2. Agendamento futuro — usado para verificar o botão "Resumo do
        //     último atendimento" (FLM-89) APÓS o fluxo (1) ser concluído e
        //     publicado. Antes disso, o botão permanece oculto por design.
        Appointment::factory()->create([
            'user_id' => $employee->getKey(),
            'consultant_id' => $testConsultant->getKey(),
            'company_id' => $company->getKey(),
            'status' => AppointmentStatus::Completed,
            'appointment_at' => now()->subHours(2),
        ]);

        Appointment::factory()->create([
            'user_id' => $employee->getKey(),
            'consultant_id' => $testConsultant->getKey(),
            'company_id' => $company->getKey(),
            'status' => AppointmentStatus::Active,
            'appointment_at' => now()->addDay(),
        ]);

        $company->employees()->attach($admin);
    }
}
