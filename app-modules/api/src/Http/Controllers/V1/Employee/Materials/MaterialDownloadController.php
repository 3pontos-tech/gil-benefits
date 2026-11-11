<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Materials;

use App\Models\Users\User;
use Illuminate\Http\Request;
use TresPontosTech\Api\Actions\Employee\Materials\PrepareMaterialDownloadAction;
use TresPontosTech\Api\Http\Resources\V1\Employee\Materials\MaterialDownloadResource;

class MaterialDownloadController
{
    /**
     * O app pede o link na hora de abrir, baixar ou compartilhar, e usa o arquivo baixado.
     */
    public function __invoke(Request $request, string $document, PrepareMaterialDownloadAction $prepareDownload): MaterialDownloadResource
    {
        /** @var User $user */
        $user = $request->user();

        return MaterialDownloadResource::make($prepareDownload->handle($user, $document));
    }
}
