<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Credits;

use App\Models\Users\User;
use Illuminate\Http\Request;
use TresPontosTech\Api\Actions\V1\Employee\BuildEmployeeCreditsAction;
use TresPontosTech\Api\Http\Resources\V1\Employee\CreditsResource;

class CreditsController
{
    public function __invoke(Request $request, BuildEmployeeCreditsAction $buildCredits): CreditsResource
    {
        /** @var User $user */
        $user = $request->user();

        return new CreditsResource($buildCredits->handle($user));
    }
}
