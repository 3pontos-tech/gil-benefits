<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Me;

use App\Models\Users\User;
use Illuminate\Http\Request;
use TresPontosTech\Api\Actions\Employee\Me\BuildEmployeeJourneyAction;
use TresPontosTech\Api\Http\Resources\V1\Employee\Me\JourneyResource;

class JourneyController
{
    public function __invoke(Request $request, BuildEmployeeJourneyAction $buildJourney): JourneyResource
    {
        /** @var User $user */
        $user = $request->user();

        return new JourneyResource($buildJourney->handle($user));
    }
}
