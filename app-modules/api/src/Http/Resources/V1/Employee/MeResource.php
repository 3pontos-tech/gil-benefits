<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Resources\V1\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use TresPontosTech\Api\DTOs\EmployeeProfile;
use TresPontosTech\Api\Support\ApiDates;

/**
 * @property EmployeeProfile $resource
 */
class MeResource extends JsonResource
{
    public function __construct(EmployeeProfile $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource->user;
        $company = $this->resource->company;
        $department = $this->resource->department;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->getFilamentAvatarUrl(),
            'phone_number' => $user->detail?->phone_number,
            'company' => $company === null ? null : [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'logo_url' => $company->getFilamentAvatarUrl(),
            ],
            'department' => $department === null ? null : [
                'id' => $department->id,
                'category' => $department->category->value,
                'name' => $department->name,
            ],
            'member_since' => ApiDates::date($this->resource->memberSince),
            'anamnese_completed' => $user->anamnese?->isComplete() ?? false,
            'life_moment' => $user->anamnese?->life_moment?->value,
            'last_login_at' => ApiDates::dateTime($user->last_login_at),
            'created_at' => ApiDates::dateTime($user->created_at),
        ];
    }
}
