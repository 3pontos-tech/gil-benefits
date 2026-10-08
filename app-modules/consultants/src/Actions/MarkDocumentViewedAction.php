<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Actions;

use App\Models\Users\User;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentUserState;

final readonly class MarkDocumentViewedAction
{
    /**
     * Registra a primeira vez que a pessoa abriu o material; abrir de novo não muda a data.
     */
    public function handle(Document $document, User $user): DocumentUserState
    {
        $state = DocumentUserState::query()->firstOrCreate([
            'document_id' => $document->getKey(),
            'user_id' => $user->getKey(),
        ]);

        if ($state->viewed_at === null) {
            $state->update(['viewed_at' => now()]);
        }

        return $state;
    }
}
