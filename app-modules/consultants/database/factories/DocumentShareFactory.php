<?php

namespace TresPontosTech\Consultants\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;

class DocumentShareFactory extends Factory
{
    protected $model = DocumentShare::class;

    public function definition(): array
    {
        return [
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'active' => true,
            'document_id' => Document::factory(),
            'consultant_id' => Consultant::factory(),
            'employee_id' => User::factory(),
        ];
    }

    public function active(): self
    {
        return $this->state([
            'active' => true,
        ]);
    }

    public function notActive()
    {
        return $this->state([
            'active' => false,
        ]);
    }
}
