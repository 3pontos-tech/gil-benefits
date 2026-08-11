<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
    public function authorize(): bool
    {
        // The middleware already authenticated the caller by IP and signature.
        return true;
    }

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
}
