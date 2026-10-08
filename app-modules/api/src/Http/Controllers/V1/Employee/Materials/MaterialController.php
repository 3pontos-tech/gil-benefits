<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Materials;

use App\Models\Users\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpStatus;
use TresPontosTech\Api\Actions\V1\Employee\FindEmployeeMaterialAction;
use TresPontosTech\Api\Http\Requests\V1\Employee\StoreMaterialRequest;
use TresPontosTech\Api\Http\Resources\V1\Employee\MaterialResource;
use TresPontosTech\Api\Support\EmployeeMaterials;
use TresPontosTech\Consultants\Actions\MarkDocumentViewedAction;
use TresPontosTech\Consultants\Actions\UploadMaterialAction;

class MaterialController
{
    private const int PER_PAGE = 50;

    public function __construct(private readonly FindEmployeeMaterialAction $findMaterial) {}

    /**
     * Enviados e compartilhados numa lista só, do mais recente ao mais antigo.
     */
    public function index(Request $request, EmployeeMaterials $materials): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return MaterialResource::collection(
            $materials->visibleTo($user)
                ->latest('shared_at')
                ->orderByDesc('documents.id')
                ->paginate(self::PER_PAGE),
        );
    }

    /**
     * Quem envia já viu o próprio arquivo, então o material nasce marcado como visto.
     */
    public function store(
        StoreMaterialRequest $request,
        UploadMaterialAction $uploadMaterial,
        MarkDocumentViewedAction $markViewed,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        /** @var UploadedFile $file */
        $file = $request->file('file');

        $document = $uploadMaterial->handle($user, $request->string('title')->toString(), $file);
        $markViewed->handle($document, $user);

        return MaterialResource::make($this->findMaterial->handle($user, (string) $document->getKey()))
            ->response()
            ->setStatusCode(HttpStatus::HTTP_CREATED);
    }

    /**
     * Só os próprios materiais podem ser removidos; os do consultor respondem 403.
     */
    public function destroy(Request $request, string $document): Response
    {
        /** @var User $user */
        $user = $request->user();

        $material = $this->findMaterial->handle($user, $document);

        abort_unless($material->isUploadedBy($user), HttpStatus::HTTP_FORBIDDEN);

        $material->delete();

        return response()->noContent();
    }
}
