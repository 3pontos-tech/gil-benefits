<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Requests\V1\Employee\Tickets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;

/**
 * Mesmos campos do "Novo chamado" do painel, anexos inclusive: fotos JPG ou PNG e PDFs de até
 * 5 MB, como o painel aceita. A descrição e a quantidade de anexos ganham limite porque a API
 * aceita envio direto. Com anexo, o app manda `multipart/form-data`; sem, JSON como antes.
 */
class StoreTicketRequest extends FormRequest
{
    /** Quantos anexos um chamado aberto pelo app leva. */
    public const int MAX_ATTACHMENTS = 5;

    /** O limite de cada anexo, em KB: o do "Anexo" do painel. */
    public const int MAX_ATTACHMENT_KILOBYTES = 5120;

    /** Os tipos do "Anexo" do painel, que a coleção `attachments` do chamado aceita. */
    public const array ATTACHMENT_MIME_TYPES = ['image/jpeg', 'image/png', 'application/pdf'];

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(SupportTicketCategoryEnum::class)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'attachments' => ['sometimes', 'array', 'max:' . self::MAX_ATTACHMENTS],
            'attachments.*' => [
                'file',
                'mimetypes:' . implode(',', self::ATTACHMENT_MIME_TYPES),
                'max:' . self::MAX_ATTACHMENT_KILOBYTES,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category' => __('api::attributes.category'),
            'subject' => __('api::attributes.subject'),
            'description' => __('api::attributes.description'),
            'attachments' => __('api::attributes.attachments'),
            'attachments.*' => __('api::attributes.attachment'),
        ];
    }

    /**
     * Os arquivos já validados, na ordem em que chegaram.
     *
     * @return list<UploadedFile>
     */
    public function attachments(): array
    {
        /** @var array<array-key, UploadedFile> $files */
        $files = $this->file('attachments', []);

        return array_values($files);
    }
}
