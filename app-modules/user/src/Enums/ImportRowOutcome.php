<?php

declare(strict_types=1);

namespace TresPontosTech\User\Enums;

enum ImportRowOutcome: string
{
    case Create = 'create';
    case Link = 'link';
    case Reactivate = 'reactivate';
    case DuplicateInSheet = 'duplicate_in_sheet';
    case AlreadyMember = 'already_member';
    case EmployedElsewhere = 'employed_elsewhere';
    case DeletedAccount = 'deleted_account';
    case ConflictingData = 'conflicting_data';

    public function joinsCompany(): bool
    {
        return in_array($this, [self::Create, self::Link, self::Reactivate], true);
    }

    public function isSkipped(): bool
    {
        return in_array($this, [self::DuplicateInSheet, self::AlreadyMember], true);
    }

    /**
     * The company only sees that support is needed, never where the person works: the other
     * company is another tenant (ADR company/2026-10-30, D4).
     */
    public function needsSupport(): bool
    {
        return in_array($this, [self::EmployedElsewhere, self::DeletedAccount, self::ConflictingData], true);
    }

    public function message(): string
    {
        return __('user::actions.import_users.outcomes.' . $this->value, ['app' => config('app.name')]);
    }
}
