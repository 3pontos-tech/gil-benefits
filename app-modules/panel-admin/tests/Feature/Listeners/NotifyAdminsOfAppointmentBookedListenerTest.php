<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Admin\Listeners\NotifyAdminsOfAppointmentBookedListener;
use TresPontosTech\Appointments\Events\AppointmentBooked;
use TresPontosTech\Appointments\Models\Appointment;

beforeEach(function (): void {
    $this->admin1 = actingAsSuperAdmin();
    $this->admin2 = User::factory()->create();
    $this->admin2->assignRole('admin');

    $this->employee = User::factory()->create();
    $this->employee->assignRole('employee');
});

it('sends a database notification to each admin when an appointment is booked', function (): void {
    $appointment = Appointment::factory()->create(['user_id' => $this->employee->id]);

    $before1 = DB::table('notifications')->where('notifiable_id', $this->admin1->id)->count();
    $before2 = DB::table('notifications')->where('notifiable_id', $this->admin2->id)->count();

    $event = new AppointmentBooked($appointment);
    resolve(NotifyAdminsOfAppointmentBookedListener::class)->handle($event);

    expect(DB::table('notifications')->where('notifiable_id', $this->admin1->id)->count())->toBe($before1 + 1)
        ->and(DB::table('notifications')->where('notifiable_id', $this->admin2->id)->count())->toBe($before2 + 1);
});

it('does not notify the employee who booked the appointment', function (): void {
    $appointment = Appointment::factory()->create(['user_id' => $this->employee->id]);

    $before = DB::table('notifications')->where('notifiable_id', $this->employee->id)->count();

    $event = new AppointmentBooked($appointment);
    resolve(NotifyAdminsOfAppointmentBookedListener::class)->handle($event);

    expect(DB::table('notifications')->where('notifiable_id', $this->employee->id)->count())->toBe($before);
});

it('includes the booking user name in the notification body', function (): void {
    $appointment = Appointment::factory()->create(['user_id' => $this->employee->id]);

    $existingIds = DB::table('notifications')
        ->where('notifiable_id', $this->admin1->id)
        ->pluck('id')
        ->toArray();

    $event = new AppointmentBooked($appointment);
    resolve(NotifyAdminsOfAppointmentBookedListener::class)->handle($event);

    $notification = DB::table('notifications')
        ->where('notifiable_id', $this->admin1->id)
        ->whereNotIn('id', $existingIds)
        ->first();

    $data = json_decode($notification->data, true);

    expect($data['body'])->toContain($this->employee->name);
});
