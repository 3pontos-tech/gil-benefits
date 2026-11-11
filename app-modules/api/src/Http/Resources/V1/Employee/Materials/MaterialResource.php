<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Resources\V1\Employee\Materials;

use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\Api\Support\ApiDates;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;
use TresPontosTech\Consultants\Models\DocumentUserState;

/**
 * Espera o documento carregado por EmployeeMaterials: `shared_at`, o estado da pessoa em
 * `states` e o compartilhamento ativo mais recente em `shares`.
 *
 * @mixin Document
 */
class MaterialResource extends JsonResource
{
    /**
     * Sem link do arquivo: o app pede um na hora de usar (`POST materials/{id}/download`),
     * baixa o arquivo e compartilha o arquivo, nunca um link do armazenamento.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $media = $this->getFirstMedia('documents');
        $state = $this->states->first();
        $share = $this->shares->first();
        $uploadedByUser = $this->isUploadedBy($user);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type?->value,
            'link' => $this->link,
            'file' => $media instanceof Media ? [
                'name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
            ] : null,
            'uploaded_by' => $uploadedByUser ? 'employee' : 'consultant',
            'shared_by' => $uploadedByUser || ! $share instanceof DocumentShare
                ? ['id' => $user->id, 'name' => $user->name]
                : ['id' => $share->consultant->id, 'name' => $share->consultant->name],
            'shared_at' => ApiDates::dateTime(Date::parse($this->getAttribute('shared_at'))),
            'viewed_at' => ApiDates::dateTime($state instanceof DocumentUserState ? $state->viewed_at : null),
            'favorited_at' => ApiDates::dateTime($state instanceof DocumentUserState ? $state->favorited_at : null),
        ];
    }
}
