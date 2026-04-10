<?php

declare(strict_types=1);

namespace TresPontosTech\Company\Listeners;

use TresPontosTech\Company\Actions\AttachToDefaultCompany;
use TresPontosTech\User\Events\UserRegistered;

class AttachUserToDefaultCompanyListener
{
    public function handle(UserRegistered $event): void
    {
        resolve(AttachToDefaultCompany::class)->execute($event->user, $event->role);
    }
}
