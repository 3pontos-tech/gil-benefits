<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * The person ChatX sent could not be matched to an account.
 *
 * Renders as a flat "invalid data" on purpose. Answering "no customer with that
 * e-mail" would let anyone holding the endpoint's credentials — or anyone who ever
 * sees a response — test addresses one by one and learn who is a Flamma customer.
 * The message is identical whether the e-mail is unknown, the CPF does not match,
 * or the two belong to different people.
 */
final class UnknownRequesterException extends RuntimeException
{
    public function render(Request $request): JsonResponse
    {
        return response()->json(
            ['error' => 'dados inválidos'],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
