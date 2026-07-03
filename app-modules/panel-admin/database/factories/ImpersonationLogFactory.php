<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\PanelAdmin\Models\ImpersonationLog;

/**
 * @extends Factory<ImpersonationLog>
 */
class ImpersonationLogFactory extends Factory
{
    protected $model = ImpersonationLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admin_id' => User::factory(),
            'impersonated_user_id' => User::factory(),
            'ip_address' => $this->faker->ipv4(),
            'started_at' => now(),
            'ended_at' => null,
        ];
    }
}
