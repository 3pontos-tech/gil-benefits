<?php

declare(strict_types=1);

namespace TresPontosTech\User\DTOs;

use Illuminate\Support\Collection;
use TresPontosTech\User\Enums\ImportRowOutcome;

final readonly class ImportUsersResultDTO
{
    /**
     * @param  list<ImportErrorDTO>  $errors
     * @param  list<ImportErrorDTO>  $rowReports
     */
    public function __construct(
        public int $imported,
        public array $errors,
        public int $linked = 0,
        public int $reactivated = 0,
        public int $skipped = 0,
        public int $needsSupport = 0,
        public array $rowReports = [],
    ) {}

    /**
     * @param  Collection<int, ClassifiedImportRowDTO>  $rows
     */
    public static function fromClassifiedRows(Collection $rows): self
    {
        $count = fn (callable $matches): int => $rows->filter(fn (ClassifiedImportRowDTO $row): bool => $matches($row->outcome))->count();

        return new self(
            imported: $count(fn (ImportRowOutcome $outcome): bool => $outcome === ImportRowOutcome::Create),
            errors: [],
            linked: $count(fn (ImportRowOutcome $outcome): bool => $outcome->linksExistingAccount()),
            reactivated: $count(fn (ImportRowOutcome $outcome): bool => $outcome === ImportRowOutcome::Reactivate),
            skipped: $count(fn (ImportRowOutcome $outcome): bool => $outcome->isSkipped()),
            needsSupport: $count(fn (ImportRowOutcome $outcome): bool => $outcome->needsSupport()),
            rowReports: array_values($rows
                ->reject(fn (ClassifiedImportRowDTO $row): bool => $row->outcome === ImportRowOutcome::Create)
                ->sortBy(fn (ClassifiedImportRowDTO $row): int => $row->rowNumber())
                ->map(fn (ClassifiedImportRowDTO $row): ImportErrorDTO => $row->toReport())
                ->all()),
        );
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function isEmpty(): bool
    {
        return $this->joined() === 0 && $this->errors === [] && $this->rowReports === [];
    }

    public function joined(): int
    {
        return $this->imported + $this->linked + $this->reactivated;
    }

    /**
     * @return array{imported: int, linked: int, reactivated: int, skipped: int, needs_support: int, rows: list<array{row: int, email: string, message: string}>}
     */
    public function toSummary(): array
    {
        return [
            'imported' => $this->imported,
            'linked' => $this->linked,
            'reactivated' => $this->reactivated,
            'skipped' => $this->skipped,
            'needs_support' => $this->needsSupport,
            'rows' => self::reportRows($this->rowReports),
        ];
    }

    /**
     * @param  list<ImportErrorDTO>  $reports
     * @return list<array{row: int, email: string, message: string}>
     */
    public static function reportRows(array $reports): array
    {
        return array_map(fn (ImportErrorDTO $report): array => [
            'row' => $report->row,
            'email' => $report->email,
            'message' => $report->message,
        ], $reports);
    }
}
