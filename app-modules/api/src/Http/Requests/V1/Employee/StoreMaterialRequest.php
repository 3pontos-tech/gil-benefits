<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Requests\V1\Employee;

use Illuminate\Foundation\Http\FormRequest;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;

/**
 * Mesmos tipos e limite do envio em "Meus materiais" no painel (até 100 MB).
 */
class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $mimeTypes = collect(DocumentExtensionTypeEnum::cases())
            ->reject(fn (DocumentExtensionTypeEnum $type): bool => $type === DocumentExtensionTypeEnum::Link)
            ->map(fn (DocumentExtensionTypeEnum $type): string => $type->getMimeType())
            ->implode(',');

        return [
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimetypes:' . $mimeTypes, 'max:102400'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => __('api::attributes.title'),
            'file' => __('api::attributes.file'),
        ];
    }
}
