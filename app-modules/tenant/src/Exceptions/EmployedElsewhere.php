<?php

declare(strict_types=1);

namespace TresPontosTech\Tenant\Exceptions;

use DomainException;
use TresPontosTech\Company\Models\Company;

/**
 * Thrown when an active employee membership would be the person's second one.
 * A person has at most one active employer; owner and manager memberships and
 * the default bucket do not count. See company/docs/adr/2026-10-30-single-active-employer.md.
 */
final class EmployedElsewhere extends DomainException
{
    public function __construct(public readonly Company $employer)
    {
        parent::__construct(sprintf('Already an active employee of %s.', $employer->name));
    }
}
