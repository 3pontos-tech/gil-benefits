<?php

declare(strict_types=1);

namespace TresPontosTech\User\Support;

use Illuminate\Support\Collection;
use Normalizer;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;

final readonly class DepartmentLookup
{
    /**
     * @param  list<string>  $names
     * @param  array<string, list<Department>>  $byNormalizedName
     */
    private function __construct(
        private array $names,
        private array $byNormalizedName,
    ) {}

    public static function for(Company $company): self
    {
        $names = [];
        $byNormalizedName = [];

        foreach ($company->departments()->orderBy('name')->get(['id', 'company_id', 'name']) as $department) {
            $names[] = $department->name;
            $key = self::normalize($department->name);

            if ($key !== '') {
                $byNormalizedName[$key][] = $department;
            }
        }

        return new self($names, $byNormalizedName);
    }

    public static function normalize(string $name): string
    {
        $decomposed = Normalizer::normalize($name, Normalizer::FORM_KD);

        return str(preg_replace('/\p{Mn}/u', '', $decomposed === false ? $name : $decomposed) ?? $name)
            ->lower()
            ->squish()
            ->toString();
    }

    public static function isBlank(mixed $name): bool
    {
        return self::normalize((string) $name) === '';
    }

    /**
     * @return list<Department>
     */
    public function find(string $name): array
    {
        $key = self::normalize($name);

        return $key === '' ? [] : $this->byNormalizedName[$key] ?? [];
    }

    public function idFor(string $name): ?string
    {
        $matches = $this->find($name);

        return count($matches) === 1 ? $matches[0]->id : null;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function withDepartmentIds(Collection $rows): Collection
    {
        return $rows->map(fn (array $row): array => $this->withDepartmentId($row));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function withDepartmentId(array $row): array
    {
        $row['department_id'] = $this->idFor((string) ($row['department'] ?? ''));

        return $row;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return $this->names;
    }
}
