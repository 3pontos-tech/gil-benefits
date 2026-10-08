<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Support;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;

/**
 * Os materiais que o colaborador enxerga, com as mesmas regras do painel: os que ele
 * enviou ("Meus materiais") e os que um consultor compartilhou com ele, com documento e
 * compartilhamento ativos. Cada material vem com `shared_at` (o compartilhamento ativo
 * mais recente, ou o envio) para ordenar a lista inteira por data.
 */
final readonly class EmployeeMaterials
{
    /**
     * @return Builder<Document>
     */
    public function visibleTo(User $user): Builder
    {
        $sharedAt = DocumentShare::query()
            ->selectRaw('coalesce(max(document_shares.created_at), documents.created_at)')
            ->whereColumn('document_shares.document_id', 'documents.id')
            ->where('document_shares.employee_id', $user->getKey())
            ->where('document_shares.active', true);

        return Document::query()
            ->select('documents.*')
            ->selectSub($sharedAt, 'shared_at')
            ->where(fn (Builder $query): Builder => $query
                ->where(fn (Builder $own): Builder => $own
                    ->where('documentable_type', $user->getMorphClass())
                    ->where('documentable_id', $user->getKey()))
                ->orWhere(fn (Builder $shared): Builder => $shared
                    ->where('active', true)
                    ->whereHas('shares', fn (Builder $share): Builder => $share
                        ->where('employee_id', $user->getKey())
                        ->where('active', true))))
            ->with([
                'media',
                'states' => fn (Relation $states): Relation => $states->where('user_id', $user->getKey()),
                'shares' => fn (Relation $shares): Relation => $shares
                    ->where('employee_id', $user->getKey())
                    ->where('active', true)
                    ->latest()
                    ->with('consultant'),
            ]);
    }
}
