<?php

namespace Database\Factories\Users;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): Factory|UserFactory
    {
        return $this->state([
            'name' => 'admin',
            'email' => 'admin@admin.com',
        ]);
    }

    public function companyOwner(): Factory|UserFactory
    {
        return $this->state([
            'name' => 'empresa',
            'email' => 'empresa@empresa.com',
        ]);
    }

    public function employee(): Factory|UserFactory
    {
        return $this->state([
            'name' => 'empregado',
            'email' => 'empregado@empregado.com',
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
