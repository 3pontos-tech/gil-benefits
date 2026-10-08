<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Actions;

use App\Models\Users\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Consultants\Models\Document;

final readonly class UploadMaterialAction
{
    /**
     * Guarda um documento enviado pela própria pessoa, como o "Meus materiais" do painel:
     * o documento é dela, fica ativo e o arquivo vai para a coleção `documents`, onde o
     * MediaObserver preenche o tipo pela extensão.
     */
    public function handle(User $user, string $title, UploadedFile $file): Document
    {
        return DB::transaction(function () use ($user, $title, $file): Document {
            $document = Document::query()->create([
                'title' => $title,
                'active' => true,
                'documentable_type' => $user->getMorphClass(),
                'documentable_id' => $user->getKey(),
            ]);

            $document->addMedia($file)->toMediaCollection('documents');

            return $document->refresh();
        });
    }
}
