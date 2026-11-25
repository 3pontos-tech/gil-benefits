<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationClau\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class StoreDiscountReservationRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'clau_id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'tax_id_fingerprint' => ['required', 'string', 'regex:/^v1:[0-9a-f]{64}$/'],
            'consent' => ['required', 'array'],
            'consent.term_version' => ['required', 'string', 'max:20'],
            'consent.accepted_at' => ['required', 'date'],
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json(['status' => 'invalid', 'errors' => $validator->errors()], 422),
        );
    }
}
