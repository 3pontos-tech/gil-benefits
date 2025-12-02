<?php

use App\Models\Users\User;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;

describe('Appointment Booking Flow', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'partner_code' => 'TEST123',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
        ]);

        $this->consultant = Consultant::factory()->create([
            'name' => 'Dr. João Silva',
            'specialty' => 'Cardiologia',
        ]);
    });

    it('allows user to book new appointment', function () {
        $this->actingAs($this->user);

        $page = visit("/app/{$this->company->id}/appointments");

        $page->assertSee('Agendamentos')
            ->assertNoJavaScriptErrors()
            ->click('Novo Agendamento');

        $page->waitForLocation("/app/{$this->company->id}/appointments/create", 5)
            ->assertSee('Agendar Consulta');

        // Select consultant
        $page->select('[name="consultant_id"]', $this->consultant->id)
            ->assertSee($this->consultant->name)
            ->assertSee($this->consultant->specialty);

        // Select date and time
        $futureDate = now()->addDays(7)->format('Y-m-d');
        $page->type('[name="appointment_date"]', $futureDate)
            ->select('[name="appointment_time"]', '14:00')
            ->type('[name="notes"]', 'Consulta de rotina');

        $page->click('Agendar')
            ->assertSee('Agendamento realizado com sucesso')
            ->assertSee('Dr. João Silva')
            ->assertSee('Cardiologia');
    });

    it('shows available time slots for selected consultant', function () {
        $this->actingAs($this->user);

        $page = visit("/app/{$this->company->id}/appointments/create");

        $page->select('[name="consultant_id"]', $this->consultant->id);

        // Should show available time slots
        $page->assertSee('Horários disponíveis')
            ->assertSee('09:00')
            ->assertSee('10:00')
            ->assertSee('14:00')
            ->assertSee('15:00');
    });

    it('prevents booking appointments in the past', function () {
        $this->actingAs($this->user);

        $page = visit("/app/{$this->company->id}/appointments/create");

        $pastDate = now()->subDays(1)->format('Y-m-d');
        $page->select('[name="consultant_id"]', $this->consultant->id)
            ->type('[name="appointment_date"]', $pastDate)
            ->select('[name="appointment_time"]', '14:00')
            ->click('Agendar')
            ->assertSee('Não é possível agendar para datas passadas');
    });

    it('shows appointment confirmation details', function () {
        $this->actingAs($this->user);

        $page = visit("/app/{$this->company->id}/appointments/create");

        $futureDate = now()->addDays(7);
        $page->select('[name="consultant_id"]', $this->consultant->id)
            ->type('[name="appointment_date"]', $futureDate->format('Y-m-d'))
            ->select('[name="appointment_time"]', '14:00')
            ->type('[name="notes"]', 'Consulta importante');

        $page->click('Agendar')
            ->assertSee('Agendamento confirmado')
            ->assertSee($this->consultant->name)
            ->assertSee($futureDate->format('d/m/Y'))
            ->assertSee('14:00')
            ->assertSee('Consulta importante');
    });

    it('allows user to view appointment details', function () {
        $this->actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'user_id' => $this->user->id,
            'consultant_id' => $this->consultant->id,
            'appointment_date' => now()->addDays(5),
            'appointment_time' => '15:00',
            'notes' => 'Consulta de acompanhamento',
        ]);

        $page = visit("/app/{$this->company->id}/appointments");

        $page->assertSee($this->consultant->name)
            ->assertSee('15:00')
            ->click($appointment->id);

        $page->assertSee('Detalhes do Agendamento')
            ->assertSee($this->consultant->name)
            ->assertSee($this->consultant->specialty)
            ->assertSee('Consulta de acompanhamento')
            ->assertSee('Status: Agendado');
    });

    it('allows user to cancel appointment', function () {
        $this->actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'user_id' => $this->user->id,
            'consultant_id' => $this->consultant->id,
            'appointment_date' => now()->addDays(5),
            'status' => 'scheduled',
        ]);

        $page = visit("/app/{$this->company->id}/appointments/{$appointment->id}");

        $page->click('Cancelar Agendamento')
            ->assertSee('Cancelar Agendamento')
            ->assertSee('Tem certeza que deseja cancelar?')
            ->type('[name="cancellation_reason"]', 'Imprevisto pessoal')
            ->click('Confirmar Cancelamento');

        $page->assertSee('Agendamento cancelado com sucesso')
            ->assertSee('Status: Cancelado')
            ->assertSee('Imprevisto pessoal');
    });

    it('allows user to reschedule appointment', function () {
        $this->actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'user_id' => $this->user->id,
            'consultant_id' => $this->consultant->id,
            'appointment_date' => now()->addDays(5),
            'appointment_time' => '14:00',
            'status' => 'scheduled',
        ]);

        $page = visit("/app/{$this->company->id}/appointments/{$appointment->id}");

        $page->click('Reagendar')
            ->assertSee('Reagendar Consulta');

        $newDate = now()->addDays(10)->format('Y-m-d');
        $page->type('[name="appointment_date"]', $newDate)
            ->select('[name="appointment_time"]', '16:00')
            ->click('Reagendar');

        $page->assertSee('Agendamento reagendado com sucesso')
            ->assertSee('16:00');
    });

    it('shows appointment history', function () {
        $this->actingAs($this->user);

        // Create past and future appointments
        $pastAppointment = Appointment::factory()->create([
            'user_id' => $this->user->id,
            'consultant_id' => $this->consultant->id,
            'appointment_date' => now()->subDays(30),
            'status' => 'completed',
        ]);

        $futureAppointment = Appointment::factory()->create([
            'user_id' => $this->user->id,
            'consultant_id' => $this->consultant->id,
            'appointment_date' => now()->addDays(7),
            'status' => 'scheduled',
        ]);

        $page = visit("/app/{$this->company->id}/appointments");

        // Should show both appointments with different statuses
        $page->assertSee('Próximos agendamentos')
            ->assertSee('Histórico')
            ->assertSee('Agendado')
            ->assertSee('Concluído');

        // Filter by status
        $page->select('[name="status_filter"]', 'completed')
            ->assertSee('Concluído')
            ->assertDontSee('Agendado');
    });

    it('sends appointment reminders', function () {
        $this->actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'user_id' => $this->user->id,
            'consultant_id' => $this->consultant->id,
            'appointment_date' => now()->addDay(),
            'appointment_time' => '14:00',
            'status' => 'scheduled',
        ]);

        $page = visit("/app/{$this->company->id}/appointments/{$appointment->id}");

        $page->assertSee('Lembrete ativo')
            ->assertSee('Você receberá um lembrete 24h antes da consulta');

        // Toggle reminder settings
        $page->click('Configurar lembretes')
            ->check('[name="email_reminder"]')
            ->check('[name="sms_reminder"]')
            ->click('Salvar configurações');

        $page->assertSee('Lembretes configurados com sucesso');
    });

    it('shows consultant availability calendar', function () {
        $this->actingAs($this->user);

        $page = visit("/app/{$this->company->id}/appointments/create");

        $page->select('[name="consultant_id"]', $this->consultant->id)
            ->assertSee('Calendário de disponibilidade');

        // Should show calendar with available/unavailable slots
        $page->assertSee('Disponível')
            ->assertSee('Ocupado')
            ->assertElementExists('.calendar-day.available')
            ->assertElementExists('.calendar-day.unavailable');
    });

    it('validates appointment booking constraints', function () {
        $this->actingAs($this->user);

        $page = visit("/app/{$this->company->id}/appointments/create");

        // Try to book without selecting consultant
        $page->click('Agendar')
            ->assertSee('Selecione um consultor');

        // Try to book without selecting date
        $page->select('[name="consultant_id"]', $this->consultant->id)
            ->click('Agendar')
            ->assertSee('Selecione uma data');

        // Try to book without selecting time
        $page->type('[name="appointment_date"]', now()->addDays(7)->format('Y-m-d'))
            ->click('Agendar')
            ->assertSee('Selecione um horário');
    });

    it('handles appointment conflicts gracefully', function () {
        $this->actingAs($this->user);

        // Create existing appointment
        $existingAppointment = Appointment::factory()->create([
            'consultant_id' => $this->consultant->id,
            'appointment_date' => now()->addDays(7),
            'appointment_time' => '14:00',
            'status' => 'scheduled',
        ]);

        $page = visit("/app/{$this->company->id}/appointments/create");

        // Try to book same time slot
        $page->select('[name="consultant_id"]', $this->consultant->id)
            ->type('[name="appointment_date"]', now()->addDays(7)->format('Y-m-d'))
            ->select('[name="appointment_time"]', '14:00')
            ->click('Agendar')
            ->assertSee('Este horário não está mais disponível')
            ->assertSee('Selecione outro horário');
    });
});

describe('Consultant Appointment Management', function () {
    beforeEach(function () {
        $this->consultant = Consultant::factory()->create([
            'name' => 'Dr. Maria Santos',
            'specialty' => 'Dermatologia',
        ]);

        $this->consultantUser = User::factory()->create([
            'name' => 'Dr. Maria Santos',
            'email' => 'maria@consultant.com',
        ]);

        $this->patient = User::factory()->create([
            'name' => 'Patient User',
            'email' => 'patient@example.com',
        ]);
    });

    it('allows consultant to view their appointments', function () {
        $this->actingAs($this->consultantUser);

        $appointment = Appointment::factory()->create([
            'consultant_id' => $this->consultant->id,
            'user_id' => $this->patient->id,
            'appointment_date' => now()->addDays(3),
            'appointment_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $page = visit('/consultant/appointments');

        $page->assertSee('Meus Agendamentos')
            ->assertNoJavaScriptErrors()
            ->assertSee($this->patient->name)
            ->assertSee('10:00')
            ->assertSee('Agendado');
    });

    it('allows consultant to update appointment status', function () {
        $this->actingAs($this->consultantUser);

        $appointment = Appointment::factory()->create([
            'consultant_id' => $this->consultant->id,
            'user_id' => $this->patient->id,
            'appointment_date' => now(),
            'status' => 'scheduled',
        ]);

        $page = visit("/consultant/appointments/{$appointment->id}");

        $page->click('Marcar como Concluído')
            ->type('[name="consultation_notes"]', 'Consulta realizada com sucesso. Paciente apresentou melhora.')
            ->click('Confirmar');

        $page->assertSee('Status atualizado com sucesso')
            ->assertSee('Status: Concluído')
            ->assertSee('Consulta realizada com sucesso');
    });

    it('allows consultant to manage availability', function () {
        $this->actingAs($this->consultantUser);

        $page = visit('/consultant/availability');

        $page->assertSee('Gerenciar Disponibilidade')
            ->assertSee('Horários de Atendimento');

        // Set weekly schedule
        $page->check('[name="monday_available"]')
            ->select('[name="monday_start"]', '09:00')
            ->select('[name="monday_end"]', '17:00')
            ->check('[name="tuesday_available"]')
            ->select('[name="tuesday_start"]', '09:00')
            ->select('[name="tuesday_end"]', '17:00')
            ->click('Salvar Horários');

        $page->assertSee('Disponibilidade atualizada com sucesso');
    });

    it('allows consultant to block specific dates', function () {
        $this->actingAs($this->consultantUser);

        $page = visit('/consultant/availability');

        $page->click('Bloquear Data')
            ->type('[name="blocked_date"]', now()->addDays(15)->format('Y-m-d'))
            ->select('[name="reason"]', 'vacation')
            ->type('[name="notes"]', 'Férias programadas')
            ->click('Bloquear');

        $page->assertSee('Data bloqueada com sucesso')
            ->assertSee('Férias programadas');
    });

    it('shows consultant dashboard with statistics', function () {
        $this->actingAs($this->consultantUser);

        $page = visit('/consultant');

        $page->assertSee('Dashboard do Consultor')
            ->assertSee('Agendamentos hoje')
            ->assertSee('Próximos agendamentos')
            ->assertSee('Total este mês')
            ->assertSee('Taxa de comparecimento');
    });
});

describe('Admin Appointment Management', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->consultant = Consultant::factory()->create();
        $this->user = User::factory()->create();
    });

    it('allows admin to view all appointments', function () {
        $this->actingAs($this->admin);

        $appointment = Appointment::factory()->create([
            'consultant_id' => $this->consultant->id,
            'user_id' => $this->user->id,
        ]);

        $page = visit('/admin/appointments');

        $page->assertSee('Appointments')
            ->assertNoJavaScriptErrors()
            ->assertSee($this->consultant->name)
            ->assertSee($this->user->name);
    });

    it('allows admin to filter appointments by status and date', function () {
        $this->actingAs($this->admin);

        $page = visit('/admin/appointments');

        // Filter by status
        $page->select('[name="status_filter"]', 'scheduled')
            ->click('Aplicar filtros')
            ->assertSee('Agendado');

        // Filter by date range
        $page->type('[name="date_from"]', now()->format('Y-m-d'))
            ->type('[name="date_to"]', now()->addDays(30)->format('Y-m-d'))
            ->click('Aplicar filtros');
    });

    it('allows admin to export appointments data', function () {
        $this->actingAs($this->admin);

        $page = visit('/admin/appointments');

        $page->click('Exportar')
            ->select('[name="format"]', 'xlsx')
            ->select('[name="date_range"]', 'this_month')
            ->click('Exportar');

        $page->assertSee('Exportação iniciada');
    });

    it('shows appointment analytics and reports', function () {
        $this->actingAs($this->admin);

        $page = visit('/admin/appointments/reports');

        $page->assertSee('Relatórios de Agendamentos')
            ->assertSee('Agendamentos por período')
            ->assertSee('Consultores mais procurados')
            ->assertSee('Taxa de cancelamento')
            ->assertSee('Horários de pico');
    });
})->group('browser', 'appointment-booking');
