<?php

namespace TresPontosTech\Consultants\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->word(),
            'type' => $this->faker->randomElement(DocumentExtensionTypeEnum::cases()),
            'active' => $this->faker->boolean(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),

            'consultant_id' => Consultant::factory(),
        ];
    }

    public function active(): self
    {
        return $this->state([
            'active' => true,
        ]);
    }

    public function notActive(): self
    {
        return $this->state([
            'active' => false,
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Document $document): void {
            Storage::fake('r2');
            $document->addMedia(UploadedFile::fake()->create('documento_teste.pdf', 100))
                ->toMediaCollection('documents');
        });
    }
}
