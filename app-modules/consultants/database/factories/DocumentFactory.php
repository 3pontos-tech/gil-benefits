<?php

namespace TresPontosTech\Consultants\Database\Factories;

use Illuminate\Support\Facades\Date;
use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Consultants\Document;
use TresPontosTech\Consultants\Models\Consultant;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->word(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),

            'consultant_id' => Consultant::factory(),
        ];
    }
}
