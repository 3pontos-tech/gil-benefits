<?php

declare(strict_types=1);

namespace TresPontosTech\User\Support;

use Illuminate\Support\Str;
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
            $byNormalizedName[self::normalize($department->name)][] = $department;
        }

        return new self($names, $byNormalizedName);
    }

    public static function normalize(string $name): string
    {
        return Str::of($name)->ascii()->lower()->squish()->toString();
    }

    /**
     * @return list<Department>
     */
    public function find(string $name): array
    {
        return $this->byNormalizedName[self::normalize($name)] ?? [];
    }

    public function idFor(string $name): ?string
    {
        $matches = $this->find($name);

        return count($matches) === 1 ? $matches[0]->id : null;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return $this->names;
    }
}
