<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use App\Models\Users\User;
use Basement\Webhooks\Models\InboundWebhook;
use Illuminate\Database\Eloquent\Relations\Relation;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Appointments\Models\AppointmentRecord;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;
use TresPontosTech\Permissions\Permission;
use TresPontosTech\Permissions\Role;

it('keeps the morph aliases already stored in the database', function (): void {
    expect(Relation::morphMap())->toEqual([
        'roles' => Role::class,
        'permissions' => Permission::class,
        'users' => User::class,
        'details' => Detail::class,
        'company' => Company::class,
        'consultants' => Consultant::class,
        'documents' => Document::class,
        'document_share' => DocumentShare::class,
        'appointments' => Appointment::class,
        'appointment_records' => AppointmentRecord::class,
        'appointment_feedbacks' => AppointmentFeedback::class,
        'inbound_webhook' => InboundWebhook::class,
    ]);
});
