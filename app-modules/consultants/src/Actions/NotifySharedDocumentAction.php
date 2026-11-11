<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Actions;

use App\Models\Users\User;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Notifications\MaterialSharedNotification;

/**
 * Avisa a pessoa de um material novo. O consultor costuma compartilhar vários em seguida, então
 * só o primeiro de cada janela de 10 minutos vai ao celular; todos ficam no sino.
 */
final readonly class NotifySharedDocumentAction
{
    public const int BURST_MINUTES = 10;

    public function handle(User $employee, Document $document): void
    {
        $firstOfBurst = Cache::add('material-shared-push:' . $employee->getKey(), true, now()->addMinutes(self::BURST_MINUTES));

        $employee->notify(new MaterialSharedNotification($document, $firstOfBurst));
    }
}
