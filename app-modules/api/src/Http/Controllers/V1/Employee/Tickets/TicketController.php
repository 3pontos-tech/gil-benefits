<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Tickets;

use App\Models\Users\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Api\Actions\V1\Employee\ListEmployeeTicketsAction;
use TresPontosTech\Api\Http\Requests\V1\Employee\StoreTicketRequest;
use TresPontosTech\Api\Http\Requests\V1\Employee\UpdateTicketRequest;
use TresPontosTech\Api\Http\Resources\V1\Employee\TicketResource;
use TresPontosTech\Support\Actions\CreateSupportTicketAction;
use TresPontosTech\Support\Actions\TransitionSupportTicketStatusAction;
use TresPontosTech\Support\DTOs\CreateSupportTicketDTO;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Exceptions\InvalidTransitionException;
use TresPontosTech\Support\Models\SupportTicket;

class TicketController
{
    private const int PER_PAGE = 20;

    public function __construct(private readonly ListEmployeeTicketsAction $listTickets) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return TicketResource::collection(
            $this->listTickets->handle($user)->latest()->orderByDesc('id')->paginate(self::PER_PAGE),
        );
    }

    /**
     * A empresa é a empregadora (a API não tem a empresa aberta na tela, como o painel). A origem
     * fica como `app`/`mobile`, que é o que a equipe vê no admin e no e-mail do chamado.
     */
    public function store(StoreTicketRequest $request, CreateSupportTicketAction $createTicket): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var SupportTicketCategoryEnum $category */
        $category = $request->enum('category', SupportTicketCategoryEnum::class);

        $ticket = $createTicket->execute(new CreateSupportTicketDTO(
            category: $category,
            subject: $request->string('subject')->toString(),
            description: $request->string('description')->toString(),
            userId: $user->id,
            companyId: $user->employerCompanyId(),
            browser: 'app',
            device: 'mobile',
            environment: app()->environment(),
        ));

        return TicketResource::make($ticket)
            ->response($request)
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Finaliza o chamado, como o "Finalizar" do painel. Já encerrado volta 422 em `status`;
     * chamado de outra pessoa responde 404.
     */
    public function update(UpdateTicketRequest $request, string $ticket, TransitionSupportTicketStatusAction $transition): TicketResource
    {
        /** @var User $user */
        $user = $request->user();

        /** @var SupportTicket $found */
        $found = $this->listTickets->handle($user)->findOrFail($ticket);

        try {
            $closed = $transition->execute($found, SupportTicketStatusEnum::Closed);
        } catch (InvalidTransitionException) {
            throw ValidationException::withMessages(['status' => __('api::validation.ticket_already_closed')]);
        }

        return new TicketResource($closed);
    }
}
