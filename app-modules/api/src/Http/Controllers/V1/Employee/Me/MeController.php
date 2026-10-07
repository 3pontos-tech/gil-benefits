<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Me;

use App\Models\Users\User;
use Illuminate\Http\Request;
use TresPontosTech\Api\Http\Resources\V1\Employee\MeResource;

class MeController
{
    public function show(Request $request): MeResource
    {
        /** @var User $user */
        $user = $request->user();

        return MeResource::make($user);
    }
}
