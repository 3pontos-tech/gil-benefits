<?php

declare(strict_types=1);

namespace TresPontosTech\Company\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\Company\Models\DepartmentCategory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'company_id' => Company::factory(),
            'category_id' => DepartmentCategory::factory(),
        ];
    }
}
