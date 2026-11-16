<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\IntegrationChatx\Enums\ChatxEventEnum;

/**
 * Shape of the ChatX intake payload.
 *
 * Schema problems answer with the offending fields, which is what lets ChatX debug
 * their side. Identity problems do not — an unknown requester gets a flat "invalid
 * data" from UnknownRequesterException, because saying "no customer with that
 * e-mail" would turn this endpoint into a way to enumerate who our customers are.
 * Same 422, deliberately different amount of detail.
 */
final class OpenTicketRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event' => ['required', 'string', Rule::enum(ChatxEventEnum::class)],
            'timestamp' => ['required', 'string', 'date'],
            'external_reference' => ['required', 'string', 'max:255'],

            'visitor' => ['required', 'array'],
            'visitor.name' => ['required', 'string', 'max:255'],
            'visitor.email' => ['required', 'string', 'email', 'max:255'],
            'visitor.document' => ['required', 'string', 'max:20'],
            'visitor.phone' => ['nullable', 'string', 'max:20'],
            'visitor.company_name' => ['nullable', 'string', 'max:255'],

            'ticket' => ['required', 'array'],
            'ticket.category' => ['required', 'string', 'max:100'],
            'ticket.subject' => ['required', 'string', 'max:255'],
            'ticket.description' => ['required', 'string'],
        ];
    }

    /**
     * Only the JSON body is covered by the signature, so only the JSON body is read.
     * Laravel's default input also merges the query string, and with a non-JSON
     * Content-Type it reads nothing from the body at all: a replayed signed delivery
     * could then carry a ticket built entirely from unsigned query parameters.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        if (! $this->isJson()) {
            return [];
        }

        /** @var array<string, mixed> */
        return $this->json()->all();
    }

    /**
     * ChatX sends no `Accept: application/json`, so the default handling would answer
     * a schema error with a 302 redirect instead of the 422 the contract promises.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new ValidationException($validator, response()->json([
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
