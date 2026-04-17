<?php

namespace TresPontosTech\Consultants\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
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
            'documentable_id' => null,
            'documentable_type' => null,

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

    public function withLink(string $url = 'https://example.com'): self
    {
        return $this->state([
            'type' => DocumentExtensionTypeEnum::Link,
            'link' => $url,
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Document $document): void {
            if ($document->type === DocumentExtensionTypeEnum::Link) {
                return;
            }

            $document->addMedia(UploadedFile::fake()->create('documento_teste.pdf', 100))
                ->toMediaCollection('documents');
        });
    }

    public function forConsultant(?Consultant $consultant = null): self
    {
        return $this->state(function (array $attributes) use ($consultant): array {

            return [
                'documentable_id' => $consultant instanceof Consultant ? $consultant->getKey() : Consultant::factory(),
                'documentable_type' => $consultant instanceof Consultant ? $consultant->getMorphClass() : (new Consultant)->getMorphClass(),
            ];
        });
    }

    public function forUser(?User $user = null): self
    {
        return $this->state(function (array $attributes) use ($user): array {
            $user = $user ?? User::factory()->create();

            return [
                'documentable_id' => $user->id,
                'documentable_type' => $user->getMorphClass(),
            ];
        });
    }
}
