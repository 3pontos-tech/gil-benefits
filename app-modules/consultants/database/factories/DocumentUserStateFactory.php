<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentUserState;

/**
 * @extends Factory<DocumentUserState>
 */
class DocumentUserStateFactory extends Factory
{
    protected $model = DocumentUserState::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'user_id' => User::factory(),
            'viewed_at' => null,
            'favorited_at' => null,
        ];
    }

    public function viewed(): self
    {
        return $this->state(['viewed_at' => Date::now()]);
    }

    public function favorited(): self
    {
        return $this->state(['favorited_at' => Date::now()]);
    }
}
