<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Requests\V1\Employee\Me;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Mesmos tipos e limite do upload de foto no perfil do painel.
 */
class StoreAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Até 5 MB e 4096 px de lado: a miniatura de 96 px é gerada na hora do envio, e uma imagem
     * pequena em bytes mas enorme em pixels estouraria a memória ao ser decodificada.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['required', 'file', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'avatar' => __('api::attributes.avatar'),
        ];
    }
}
