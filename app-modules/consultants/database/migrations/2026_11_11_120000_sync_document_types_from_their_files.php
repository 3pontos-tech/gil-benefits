<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Todo arquivo enviado pelo painel do consultor ficava com o tipo `link` e sem link (a lista de
 * mídias lida antes de o arquivo ser salvo estava vazia), e o app o mostrava como aula. Corrige
 * só esse rastro: `type = link`, sem link e com arquivo. Um documento com arquivo e link fica como
 * está, porque o link foi a escolha do consultor e é o que o app já mostra.
 *
 * A regra do tipo fica escrita aqui, e não na action, para a migration fazer sempre o que fez no
 * deploy. Pelo query builder, sem mexer no `updated_at`.
 */
return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const array TYPE_BY_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/svg+xml' => 'svg',
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    ];

    public function up(): void
    {
        DB::table('documents')
            ->select('id')
            ->where('type', 'link')
            ->where(fn ($query) => $query->whereNull('link')->orWhere('link', ''))
            ->lazyById(100)
            ->each(function (object $document): void {
                $media = DB::table('media')
                    ->where('model_type', 'documents')
                    ->where('model_id', $document->id)
                    ->where('collection_name', 'documents')
                    ->orderBy('order_column')
                    ->first(['mime_type', 'file_name']);

                if ($media === null) {
                    return;
                }

                DB::table('documents')->where('id', $document->id)->update([
                    'type' => $this->typeOf((string) $media->mime_type, (string) $media->file_name),
                ]);
            });
    }

    /**
     * O tipo anterior era o erro; não há o que desfazer.
     */
    public function down(): void {}

    /**
     * Pelo mime type, que vem do conteúdo; a extensão em minúsculas é a reserva. Um tipo que não
     * é aceito fica vazio, nunca `link`.
     */
    private function typeOf(string $mimeType, string $fileName): ?string
    {
        if (isset(self::TYPE_BY_MIME[$mimeType])) {
            return self::TYPE_BY_MIME[$mimeType];
        }

        $extension = str(pathinfo($fileName, PATHINFO_EXTENSION))->lower()->replace('jpeg', 'jpg')->toString();

        return in_array($extension, self::TYPE_BY_MIME, true) ? $extension : null;
    }
};
