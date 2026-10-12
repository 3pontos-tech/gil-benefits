<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\V1\Employee;

use App\Models\Users\User;
use App\Notifications\NotificationKind;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\DatabaseNotification;

final readonly class ListEmployeeNotificationsAction
{
    /**
     * Os avisos da pessoa que o app sabe exibir (NotificationKind::shownInEmployeeApp()), do mais
     * recente ao mais antigo; avisos de outro público, ou anteriores a esta estrutura, ficam de
     * fora. O `reorder()` troca a ordem padrão da relação pela mesma com o id desempatando.
     *
     * @return MorphMany<DatabaseNotification, User>
     */
    public function handle(User $user): MorphMany
    {
        return $user->notifications()
            ->whereIn(
                'type',
                array_map(fn (NotificationKind $kind): string => $kind->value, NotificationKind::shownInEmployeeApp()),
            )
            ->reorder()
            ->latest()
            ->orderByDesc('id');
    }
}
