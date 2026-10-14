<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Tickets;

use App\Models\Users\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Api\Actions\Employee\Tickets\CloseEmployeeTicketAction;
use TresPontosTech\Api\Actions\Employee\Tickets\ListEmployeeTicketsAction;
use TresPontosTech\Api\Actions\Employee\Tickets\OpenEmployeeTicketAction;
use TresPontosTech\Api\Http\Requests\V1\Employee\Tickets\StoreTicketRequest;
use TresPontosTech\Api\Http\Requests\V1\Employee\Tickets\UpdateTicketRequest;
use TresPontosTech\Api\Http\Resources\V1\Employee\Tickets\TicketResource;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;

class TicketController
{
    private const int PER_PAGE = 20;

    public function index(Request $request, ListEmployeeTicketsAction $listTickets): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return TicketResource::collection($listTickets->handle($user)->paginate(self::PER_PAGE));
    }

    public function store(StoreTicketRequest $request, OpenEmployeeTicketAction $openTicket): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var SupportTicketCategoryEnum $category */
        $category = $request->enum('category', SupportTicketCategoryEnum::class);

        $ticket = $openTicket->handle(
            $user,
            $category,
            $request->string('subject')->toString(),
            $request->string('description')->toString(),
        );

        return TicketResource::make($ticket)
            ->response($request)
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Já encerrado volta 422 em `status`; chamado de outra pessoa responde 404.
     */
    public function update(UpdateTicketRequest $request, string $ticket, CloseEmployeeTicketAction $closeTicket): TicketResource
    {
        /** @var User $user */
        $user = $request->user();

        return new TicketResource($closeTicket->handle($user, $ticket));
    }
}
