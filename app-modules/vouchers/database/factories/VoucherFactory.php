<?php

namespace TresPontosTech\Vouchers\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Tenant\Models\Company;
use TresPontosTech\Vouchers\Enums\VoucherStatusEnum;
use TresPontosTech\Vouchers\Models\Voucher;

class VoucherFactory extends Factory
{
    protected $model = Voucher::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->uuid(),
            'status' => $this->faker->randomElement(VoucherStatusEnum::cases()),
            'valid_until' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'consultant_id' => Consultant::factory(),
            'user_id' => User::factory(),
        ];
    }

    public function forCompany(Company $company): self
    {
        return $this->state(fn (array $attributes): array => [
            'company_id' => $company->id,
        ]);
    }

    public function forUser(User $user): self
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    public function forConsultant(Consultant $consultant): self
    {
        return $this->state(fn (array $attributes): array => [
            'consultant_id' => $consultant->id,
        ]);
    }

    public function unused(): self
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => null,
            'consultant_id' => null,
            'status' => VoucherStatusEnum::Pending,
        ]);
    }

    public function withStatus(VoucherStatusEnum $status): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes): array => [
            'valid_until' => Carbon::now()->subDay(),
            'status' => VoucherStatusEnum::Expired,
        ]);
    }
}
