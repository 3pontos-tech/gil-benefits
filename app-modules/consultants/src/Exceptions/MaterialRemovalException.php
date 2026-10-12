<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Exceptions;

use RuntimeException;

class MaterialRemovalException extends RuntimeException
{
    public static function notUploadedBy(): self
    {
        return new self(__('consultants::resources.materials.exceptions.not_uploaded_by_you'));
    }
}
