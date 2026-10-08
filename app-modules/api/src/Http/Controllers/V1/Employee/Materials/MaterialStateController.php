<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Materials;

use App\Models\Users\User;
use Illuminate\Http\Request;
use TresPontosTech\Api\Actions\V1\Employee\FindEmployeeMaterialAction;
use TresPontosTech\Api\Http\Requests\V1\Employee\FavoriteMaterialRequest;
use TresPontosTech\Api\Http\Resources\V1\Employee\MaterialResource;
use TresPontosTech\Consultants\Actions\MarkDocumentViewedAction;
use TresPontosTech\Consultants\Actions\SetDocumentFavoriteAction;

class MaterialStateController
{
    public function __construct(private readonly FindEmployeeMaterialAction $findMaterial) {}

    public function favorite(FavoriteMaterialRequest $request, string $document, SetDocumentFavoriteAction $setFavorite): MaterialResource
    {
        /** @var User $user */
        $user = $request->user();

        $setFavorite->handle($this->findMaterial->handle($user, $document), $user, $request->boolean('favorite'));

        return MaterialResource::make($this->findMaterial->handle($user, $document));
    }

    public function viewed(Request $request, string $document, MarkDocumentViewedAction $markViewed): MaterialResource
    {
        /** @var User $user */
        $user = $request->user();

        $markViewed->handle($this->findMaterial->handle($user, $document), $user);

        return MaterialResource::make($this->findMaterial->handle($user, $document));
    }
}
