<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Consultants\Models\Consultant;

class ConsultantFactory extends Factory
{
    protected $model = Consultant::class;

    /**
     * O e-mail sai do `fake()`, o mesmo pool do UserFactory: o ConsultantObserver cria um usuário
     * com este e-mail, e o `$this->faker` zera a unicidade a cada fábrica, então dois pools
     * independentes de vez em quando sorteavam o mesmo e-mail e quebravam o índice único.
     */
    public function definition(): array
    {
        $name = $this->faker->firstName() . ' ' . $this->faker->lastName();

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'phone' => $this->faker->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'short_description' => $this->faker->sentence(),
            'slug' => str($name)->slug(),
            'biography' => $this->faker->paragraph(),
            'readme' => $this->faker->paragraph(),
            'socials_urls' => [],
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }
}
