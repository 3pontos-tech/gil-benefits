<?php

namespace TresPontosTech\Consultants\Database\Factories;

use Illuminate\Support\Facades\Date;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Consultants\Document;
use TresPontosTech\Consultants\DocumentShare;
use TresPontosTech\Consultants\Models\Consultant;

class DocumentShareFactory extends Factory
{
    protected $model = DocumentShare::class;

    public function definition(): array
    {
        return [
            'created_at' => Date::now(),
            'updated_at' => Date::now(),

            'document_id' => Document::factory(),
            'consultant_id' => Consultant::factory(),
            'employee_id' => User::factory(),
        ];
    }
}
