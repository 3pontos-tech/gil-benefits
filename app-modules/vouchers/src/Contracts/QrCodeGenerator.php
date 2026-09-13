<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Contracts;

interface QrCodeGenerator
{
    public function generate(string $data): string;

    public function fileExtension(): string;
}
