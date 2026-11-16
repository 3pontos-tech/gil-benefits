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
            'visitor.document' => ['nullable', 'string', 'max:20'],
            'visitor.phone' => ['nullable', 'string', 'max:20'],
            'visitor.company_name' => ['nullable', 'string', 'max:255'],

            'ticket' => ['required', 'array'],
            'ticket.category' => ['required', 'string', 'max:100'],
            'ticket.subject' => ['required', 'string', 'max:255'],
            'ticket.description' => ['required', 'string'],
        ];
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
