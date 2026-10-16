<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Actions;

use App\Models\Users\User;
use TresPontosTech\Consultants\Exceptions\MaterialRemovalException;
use TresPontosTech\Consultants\Models\Document;

final readonly class RemoveUploadedMaterialAction
{
    /**
     * Remove um material que a própria pessoa enviou. O que o consultor compartilhou fica: a
     * pessoa pode deixar de ver, mas não apagar o documento dele.
     *
     * @throws MaterialRemovalException
     */
    public function handle(Document $document, User $user): void
    {
        throw_unless($document->isUploadedBy($user), MaterialRemovalException::notUploadedBy());

        $document->delete();
    }
}
