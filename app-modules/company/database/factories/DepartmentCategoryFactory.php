<?php

declare(strict_types=1);

namespace TresPontosTech\Company\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Company\Models\DepartmentCategory;

/**
 * @extends Factory<DepartmentCategory>
 */
class DepartmentCategoryFactory extends Factory
{
    protected $model = DepartmentCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
        ];
    }
}
