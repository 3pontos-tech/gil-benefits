<?php

declare(strict_types=1);

namespace TresPontosTech\Company\Enums;

/**
 * What happens to the previous responsible's membership when ownership is
 * transferred. Chosen explicitly by whoever transfers, never defaulted:
 * keeping them as manager grants panel access they may not have had, and
 * revoking it is the right call when the person left the company.
 */
enum PreviousOwnerOutcome: string
{
    case KeepAsManager = 'keep_as_manager';
    case RevokeAccess = 'revoke_access';
}
