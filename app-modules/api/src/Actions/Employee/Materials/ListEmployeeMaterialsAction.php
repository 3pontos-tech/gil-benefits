<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\Employee\Materials;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Consultants\Models\Document;

final readonly class ListEmployeeMaterialsAction
{
    public function __construct(private EmployeeMaterials $materials) {}

    /**
     * Enviados e compartilhados numa lista só, do mais recente ao mais antigo (pelo compartilhamento
     * ativo mais recente, ou pelo envio), com o id desempatando.
     *
     * @return Builder<Document>
     */
    public function handle(User $user): Builder
    {
        return $this->materials->visibleTo($user)
            ->latest('shared_at')
            ->orderByDesc('documents.id');
    }
}
