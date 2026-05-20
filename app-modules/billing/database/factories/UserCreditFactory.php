<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;

/** @extends Factory<UserCredit> */
class UserCreditFactory extends Factory
{
    protected $model = UserCredit::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'holder_id' => User::factory(),
            'company_id' => Company::factory(),
            'status' => UserCreditStatusEnum::Available,
            'appointment_id' => null,
            'transferred_at' => null,
        ];
    }

    public function available(): self
    {
        return $this->state([
            'status' => UserCreditStatusEnum::Available,
            'appointment_id' => null,
        ]);
    }

    public function inUse(): self
    {
        return $this->state([
            'status' => UserCreditStatusEnum::InUse,
        ]);
    }

    public function used(): self
    {
        return $this->state([
            'status' => UserCreditStatusEnum::Used,
        ]);
    }

    public function expired(): self
    {
        return $this->state([
            'status' => UserCreditStatusEnum::Expired,
        ]);
    }

    public function transferred(): self
    {
        return $this->state([
            'transferred_at' => now(),
        ]);
    }
}
