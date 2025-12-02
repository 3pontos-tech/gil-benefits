<?php

namespace TresPontosTech\Tenant\Http\Controllers\Api\v1;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Tenant\Actions\CreateExternalUserAction;
use TresPontosTech\Tenant\Http\Requests\CreateExternalUserRequest;
use TresPontosTech\User\DTOs\UserDTO;

class UsersController extends Controller
{
    public function store(CreateExternalUserRequest $request, string $tenant): Response
    {
        $userDTO = UserDTO::make([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'external_id' => $request->validated('external_id'),
            'password' => Hash::make(Uuid::uuid4()->toString()),
            'tenant_id' => $tenant,
        ]);

        resolve(CreateExternalUserAction::class)->execute($userDTO);

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function destroy(string $tenant, string $user): Response
    {
        $company = Company::query()->findOrFail($tenant);
        $user = $company->employees()->where('user_id', $user)->firstOrFail();

        $user->delete();
        $company->employees()->detach($user);

        return response()->noContent(Response::HTTP_NO_CONTENT);

    }
}
