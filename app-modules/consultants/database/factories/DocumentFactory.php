<?php

namespace TresPontosTech\Consultants\Database\Factories;

use App\Models\Users\User;
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

    /**
     * Define apenas o type do documento, sem anexar mídia.
     */
    public function withType(DocumentExtensionTypeEnum $type = DocumentExtensionTypeEnum::PDF): self
    {
        return $this->state([
            'type' => $type,
        ]);
    }

    /**
     * Anexa uma mídia fake com a extensão do type; o MediaObserver define o type a partir dela.
     */
    public function withMedia(DocumentExtensionTypeEnum $type = DocumentExtensionTypeEnum::PDF): self
    {
        return $this->afterCreating(function (Document $document) use ($type): void {
            $document->addMedia(
                UploadedFile::fake()->create('documento_teste.' . $type->value, 100)
            )->toMediaCollection('documents');
        });
    }

    /**
     * Documento baseado em arquivo: define o type e anexa a mídia correspondente.
     */
    public function withFile(DocumentExtensionTypeEnum $type = DocumentExtensionTypeEnum::PDF): self
    {
        return $this->withType($type)->withMedia($type);
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
