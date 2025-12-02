<?php

namespace TresPontosTech\Tenant\Http\Controllers\Api\v1;

use Illuminate\Routing\Controller;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Tenant\Actions\CreateExternalUserAction;
use TresPontosTech\Tenant\Http\Requests\CreateExternalUserRequest;
use TresPontosTech\User\DTOs\UserDTO;

class UsersController extends Controller
{
    public function store(CreateExternalUserRequest $request, string $tenant)
    {
        dd($tenant);
        $userDTO = UserDTO::make([$request->validated(), 'tenant_id' => $tenant]);
        dd($userDTO);

        app(CreateExternalUserAction::class)->execute($userDTO);
    }
}
