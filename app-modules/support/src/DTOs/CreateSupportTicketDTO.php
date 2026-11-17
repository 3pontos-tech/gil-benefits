<?php

declare(strict_types=1);

namespace TresPontosTech\Support\DTOs;

use Illuminate\Http\UploadedFile;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use UnexpectedValueException;

final readonly class CreateSupportTicketDTO
{
    /**
     * @param  list<UploadedFile>  $attachments
     */
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
        public array $attachments = [],
        public ?TicketOriginDTO $origin = null,
    ) {}

    /**
     * Builds the DTO from a Filament form state. Form values arrive dynamically typed
     * (`array<string, mixed>`), so they are narrowed here — this factory is the boundary
     * between the untyped form and the typed domain. Request/auth context is passed in by
     * the caller so the DTO stays free of framework/infra concerns.
     *
     * @param  array<string, mixed>  $state
     */
    public static function fromFormState(
        array $state,
        ?string $userId = null,
        ?string $companyId = null,
        ?string $url = null,
        ?string $userAgent = null,
        ?string $environment = null,
    ): self {
        $category = $state['category'] ?? null;

        return new self(
            category: $category instanceof SupportTicketCategoryEnum
                ? $category
                : SupportTicketCategoryEnum::from(self::requiredString($state['category'] ?? null)),
            subject: self::requiredString($state['subject'] ?? null),
            description: self::requiredString($state['description'] ?? null),
            userId: $userId,
            companyId: $companyId,
            visitorName: self::optionalString($state['visitor_name'] ?? null),
            visitorEmail: self::optionalString($state['visitor_email'] ?? null),
            visitorCompanyName: self::optionalString($state['visitor_company_name'] ?? null),
            url: $url,
            browser: self::parseBrowser($userAgent),
            device: self::parseDevice($userAgent),
            environment: $environment,
            attachments: self::uploadedFiles($state['attachments'] ?? null),
        );
    }

    /**
     * A multiple FileUpload yields an array of files; narrow it to actual
     * uploads (dropping nulls / stray values) so the domain gets a clean list.
     *
     * @return list<UploadedFile>
     */
    private static function uploadedFiles(mixed $value): array
    {
        $files = is_array($value) ? $value : [$value];

        return array_values(array_filter(
            $files,
            static fn (mixed $file): bool => $file instanceof UploadedFile,
        ));
    }

    private static function requiredString(mixed $value): string
    {
        throw_unless(is_string($value), UnexpectedValueException::class, 'Expected a string form value.');

        return $value;
    }

    private static function optionalString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private static function parseBrowser(?string $userAgent): ?string
    {
        if ($userAgent === null) {
            return null;
        }

        return match (true) {
            str_contains($userAgent, 'Chrome') && ! str_contains($userAgent, 'Edg') => 'Chrome',
            str_contains($userAgent, 'Firefox') => 'Firefox',
            str_contains($userAgent, 'Safari') && ! str_contains($userAgent, 'Chrome') => 'Safari',
            str_contains($userAgent, 'Edg') => 'Edge',
            str_contains($userAgent, 'OPR') => 'Opera',
            default => 'Unknown',
        };
    }

    private static function parseDevice(?string $userAgent): ?string
    {
        if ($userAgent === null) {
            return null;
        }

        return match (true) {
            str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android') => 'mobile',
            str_contains($userAgent, 'Tablet') || str_contains($userAgent, 'iPad') => 'tablet',
            default => 'desktop',
        };
    }
}
