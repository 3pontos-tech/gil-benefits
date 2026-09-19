<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Support;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use TresPontosTech\Vouchers\Contracts\QrCodeGenerator;

final readonly class ChillerlanQrCodeGenerator implements QrCodeGenerator
{
    /** @var array<string, int> */
    private const ECC_LEVELS = [
        'L' => EccLevel::L,
        'M' => EccLevel::M,
        'Q' => EccLevel::Q,
        'H' => EccLevel::H,
    ];

    public function __construct(
        private string $eccLevel = 'M',
        private int $scale = 6,
        private int $quietzoneSize = 4,
    ) {}

    public function generate(string $data): string
    {
        return (new QRCode($this->options()))->render($data);
    }

    public function fileExtension(): string
    {
        return 'png';
    }

    private function options(): QROptions
    {
        return new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'eccLevel' => self::ECC_LEVELS[$this->eccLevel] ?? EccLevel::M,
            'scale' => $this->scale,
            'quietzoneSize' => $this->quietzoneSize,
            'outputBase64' => false,
        ]);
    }
}
