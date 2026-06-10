<?php

declare(strict_types=1);

namespace TresPontosTech\Support\DTOs;

use Illuminate\Http\UploadedFile;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;

final readonly class CreateSupportTicketDTO
{
    public function __construct(
        public SupportTicketCategoryEnum $category,
        public string $subject,
        public string $description,
        public ?string $userId = null,
        public ?string $companyId = null,
        public ?string $visitorName = null,
        public ?string $visitorEmail = null,
        public ?string $visitorCompanyName = null,
        public ?string $url = null,
        public ?string $browser = null,
        public ?string $device = null,
        public ?string $environment = null,
        public ?UploadedFile $attachment = null,
    ) {}
}
