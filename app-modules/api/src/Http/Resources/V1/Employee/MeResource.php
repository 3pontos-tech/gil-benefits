<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Resources\V1\Employee;

use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use TresPontosTech\Api\Support\ApiDates;

/**
 * @mixin User
 */
class MeResource extends JsonResource
{
    /**
     * @return array{id: string, name: string, email: string, last_login_at: ?string, created_at: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'last_login_at' => ApiDates::dateTime($this->last_login_at),
            'created_at' => ApiDates::dateTime($this->created_at),
        ];
    }
}
