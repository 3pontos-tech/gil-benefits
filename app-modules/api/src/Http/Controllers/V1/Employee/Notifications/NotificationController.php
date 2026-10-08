<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Notifications;

use App\Models\Users\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Notifications\DatabaseNotification;
use TresPontosTech\Api\Actions\V1\Employee\ListEmployeeNotificationsAction;
use TresPontosTech\Api\Http\Resources\V1\Employee\NotificationResource;

class NotificationController
{
    private const int PER_PAGE = 20;

    public function __construct(private readonly ListEmployeeNotificationsAction $listNotifications) {}

    /**
     * Mais recentes primeiro; `meta.unread` alimenta o contador do sino no app.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return NotificationResource::collection(
            $this->listNotifications->handle($user)->latest()->paginate(self::PER_PAGE),
        )->additional(['meta' => ['unread' => $this->listNotifications->handle($user)->whereNull('read_at')->count()]]);
    }

    /**
     * Marcar de novo não muda a data da primeira leitura. Aviso de outra pessoa responde 404.
     *
     * O envelope `data` é montado aqui porque o Laravel não embrulha um resource que já tem
     * uma chave `data` (o conteúdo do aviso), e o app espera `{ data: Notification }`.
     */
    public function read(Request $request, string $notification): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var DatabaseNotification $found */
        $found = $this->listNotifications->handle($user)->whereKey($notification)->firstOrFail();

        $found->markAsRead();

        return response()->json(['data' => NotificationResource::make($found)->resolve($request)]);
    }
}
