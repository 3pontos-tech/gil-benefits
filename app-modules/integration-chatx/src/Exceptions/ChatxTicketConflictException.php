<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;

/**
 * The customer's answer does not fit the ticket's status. Carries the current status
 * so the bot can tell the customer what happened instead of retrying.
 */
final class ChatxTicketConflictException extends RuntimeException
{
    private function __construct(
        string $message,
        private readonly SupportTicketStatusEnum $status,
    ) {
        parent::__construct($message);
    }

    public static function closed(SupportTicketStatusEnum $status): self
    {
        return new self('chamado encerrado, abra outro chamado', $status);
    }

    public static function notResolved(SupportTicketStatusEnum $status): self
    {
        return new self('chamado não está resolvido', $status);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(
            ['error' => $this->getMessage(), 'status' => $this->status->value],
            Response::HTTP_CONFLICT,
        );
    }
}
