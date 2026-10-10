<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Me;

use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use TresPontosTech\Api\Actions\V1\Employee\BuildEmployeeProfileAction;
use TresPontosTech\Api\Http\Requests\V1\Employee\UpdateMeRequest;
use TresPontosTech\Api\Http\Resources\V1\Employee\MeResource;
use TresPontosTech\User\Actions\UpdateUserProfileAction;
use TresPontosTech\User\Exceptions\ProfileUpdateException;

class MeController
{
    public function __construct(private readonly BuildEmployeeProfileAction $buildProfile) {}

    public function show(Request $request): MeResource
    {
        /** @var User $user */
        $user = $request->user();

        return new MeResource($this->buildProfile->handle($user));
    }

    /**
     * A única falha de domínio é telefone sem cadastro completo, que volta em `phone_number`.
     */
    public function update(UpdateMeRequest $request, UpdateUserProfileAction $updateProfile): MeResource
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $updateProfile->handle($user, $request->profileData());
        } catch (ProfileUpdateException $profileUpdateException) {
            throw ValidationException::withMessages(['phone_number' => $profileUpdateException->getMessage()]);
        }

        return new MeResource($this->buildProfile->handle($user));
    }
}
