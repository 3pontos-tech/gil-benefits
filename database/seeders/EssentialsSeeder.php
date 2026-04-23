<?php

namespace Database\Seeders;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;

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
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        // Silencia side-effects de notificação/email disparados por
        // UserRegistered e afins — um seed com ~N users geraria N² jobs
        // na fila local (NotifyAdmins..., WelcomeMail) sem valor pro dev.
        // Listeners síncronos que fazem regra de negócio (ex.:
        // AttachUserToDefaultCompanyListener) continuam rodando normal.
        Queue::fake();
        Mail::fake();

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

        // Cenários de teste para a feature de Ata, refletindo o fluxo
        // real — a ata só existe depois que o consultor sobe o documento e a
        // IA processa. O seeder NÃO cria records pré-fabricados.
        //
        //  1. Agendamento recém-finalizado sem ata — habilita o botão "Criar
        //     Ata" no painel do consultor para testar upload + geração pela IA.
        //  2. Agendamento futuro — usado para verificar o botão "Resumo do
        //     último atendimento" APÓS o fluxo (1) ser concluído e
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

        // Documentos do próprio colaborador (anamnese / ficha) — alimentam o
        // painel "Documentos do colaborador" na tela ViewAppointment do admin.
        Document::factory()
            ->forUser($employee)
            ->active()
            ->create([
                'title' => 'Anamnese - Histórico Pessoal',
                'type' => DocumentExtensionTypeEnum::PDF,
            ]);

        Document::factory()
            ->forUser($employee)
            ->active()
            ->create([
                'title' => 'Comprovante de Renda 2026',
                'type' => DocumentExtensionTypeEnum::XLSX,
            ]);

        // Documentos compartilhados pelo consultor com o colaborador —
        // alimentam o painel "Documentos compartilhados" na mesma tela.
        $sharedInvestment = Document::factory()
            ->forConsultant($testConsultant)
            ->active()
            ->create([
                'title' => 'Proposta de Investimento Personalizada',
                'type' => DocumentExtensionTypeEnum::PDF,
            ]);

        DocumentShare::factory()
            ->for($sharedInvestment, 'document')
            ->for($testConsultant, 'consultant')
            ->for($employee, 'employee')
            ->active()
            ->create();

        $sharedPortfolio = Document::factory()
            ->forConsultant($testConsultant)
            ->active()
            ->create([
                'title' => 'Análise de Carteira de Ações',
                'type' => DocumentExtensionTypeEnum::XLSX,
            ]);

        DocumentShare::factory()
            ->for($sharedPortfolio, 'document')
            ->for($testConsultant, 'consultant')
            ->for($employee, 'employee')
            ->active()
            ->create();

        $company->employees()->attach($admin);
    }
}
