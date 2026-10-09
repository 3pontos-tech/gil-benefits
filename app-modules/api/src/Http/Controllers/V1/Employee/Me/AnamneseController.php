<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Me;

use App\Models\Users\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Api\Http\Requests\V1\Employee\UpdateAnamneseRequest;
use TresPontosTech\Api\Http\Resources\V1\Employee\AnamneseResource;
use TresPontosTech\User\Actions\UpsertAnamneseAnswersAction;
use TresPontosTech\User\Models\UserAnamnese;

class AnamneseController
{
    public function show(Request $request): AnamneseResource
    {
        /** @var User $user */
        $user = $request->user();

        return new AnamneseResource($user->anamnese ?? new UserAnamnese);
    }

    /**
     * Sempre 200, inclusive quando a primeira resposta cria a linha: para o app é só "salvar
     * respostas", e o Laravel responderia 201 por o model ter acabado de nascer.
     */
    public function update(UpdateAnamneseRequest $request, UpsertAnamneseAnswersAction $upsertAnswers): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return (new AnamneseResource($upsertAnswers->handle($user, $request->answers())))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
}
