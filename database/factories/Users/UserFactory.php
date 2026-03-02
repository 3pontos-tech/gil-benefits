<?php

namespace Database\Factories\Users;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use TresPontosTech\Permissions\Roles;

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
            'name' => 'Dev Admin',
            'email' => 'admin@5pontos.com',
        ])->afterCreating(fn (User $user) => $user->assignRole(Roles::Admin));
    }

    public function superAdmin(): Factory|UserFactory
    {
        return $this->state([
            'name' => 'Dev Admin',
            'email' => 'admin@5pontos.com',
        ])->afterCreating(fn (User $user) => $user->assignRole(Roles::SuperAdmin));
    }

    public function companyOwner(): Factory|UserFactory
    {
        return $this->state([
            'name' => 'empresa',
            'email' => $this->faker->userName() . '@5pontos.com',
        ])->afterCreating(fn (User $user) => $user->assignRole(Roles::CompanyOwner));
    }

    public function adminCompanyEmployee(): Factory|UserFactory
    {
        $names = [
            'Renan Silva',
            'Clinton Rocha',
            'Paula Santos',
        ];

        return $this->state([
            'name' => $this->faker->randomElement($names),
            'email' => $this->faker->userName() . '@5pontos.com',
        ])->afterCreating(fn (User $user) => $user->assignRole(Roles::Employee));
    }

    public function employee(): Factory|UserFactory
    {
        return $this->state([
            'name' => 'empregado',
            'email' => 'empregado@empregado.com',
        ])->afterCreating(fn (User $user) => $user->assignRole(Roles::Employee));
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
