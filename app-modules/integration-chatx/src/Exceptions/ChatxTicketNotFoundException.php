<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class ChatxTicketNotFoundException extends RuntimeException
{
    public function render(Request $request): JsonResponse
    {
        return response()->json(
            ['error' => 'chamado não encontrado'],
            Response::HTTP_NOT_FOUND,
        );
    }
}
