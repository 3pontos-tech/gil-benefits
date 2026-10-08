<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Actions;

use App\Models\Users\User;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentUserState;

final readonly class SetDocumentFavoriteAction
{
    /**
     * Favorita ou desfavorita o material para a pessoa. Favoritar de novo mantém a data
     * original, para a ordem dos favoritos não mudar a cada toque.
     */
    public function handle(Document $document, User $user, bool $favorite): DocumentUserState
    {
        $state = DocumentUserState::query()->firstOrCreate([
            'document_id' => $document->getKey(),
            'user_id' => $user->getKey(),
        ]);

        if ($favorite && $state->favorited_at === null) {
            $state->update(['favorited_at' => now()]);
        }

        if (! $favorite && $state->favorited_at !== null) {
            $state->update(['favorited_at' => null]);
        }

        return $state;
    }
}
