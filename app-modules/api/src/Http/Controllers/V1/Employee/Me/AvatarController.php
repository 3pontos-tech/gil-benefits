<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Me;

use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use TresPontosTech\Api\Actions\V1\Employee\BuildEmployeeProfileAction;
use TresPontosTech\Api\Http\Requests\V1\Employee\StoreAvatarRequest;
use TresPontosTech\Api\Http\Resources\V1\Employee\MeResource;
use TresPontosTech\User\Actions\UpdateUserAvatarAction;

class AvatarController
{
    public function __construct(
        private readonly UpdateUserAvatarAction $updateAvatar,
        private readonly BuildEmployeeProfileAction $buildProfile,
    ) {}

    public function store(StoreAvatarRequest $request): MeResource
    {
        /** @var User $user */
        $user = $request->user();

        /** @var UploadedFile $avatar */
        $avatar = $request->file('avatar');

        return new MeResource($this->buildProfile->handle($this->updateAvatar->handle($user, $avatar)));
    }

    /**
     * Idempotente: remover sem foto responde 200 com o perfil.
     */
    public function destroy(Request $request): MeResource
    {
        /** @var User $user */
        $user = $request->user();

        return new MeResource($this->buildProfile->handle($this->updateAvatar->handle($user, null)));
    }
}
