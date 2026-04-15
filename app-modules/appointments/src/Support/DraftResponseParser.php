<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Support;

final class DraftResponseParser
{
    /**
     * Divide a resposta do modelo em ata (`content`) e resumo interno (`internalSummary`)
     * usando o delimitador `---INTERNAL_SUMMARY---`. Se o delimitador não estiver presente,
     * assume que a resposta inteira é a ata e o resumo interno fica null.
     *
     * Aplica também um saneamento defensivo removendo code fences Markdown que o modelo
     * eventualmente coloque envolvendo a resposta — o prompt proíbe explicitamente esse
     * padrão, mas mantemos a rede de segurança aqui para não poluir a ata renderizada.
     *
     * @return array{content: string, internalSummary: ?string}
     */
    public static function parse(string $text): array
    {
        $text = self::stripWrappingCodeFence($text);

        $parts = preg_split('/^\s*---INTERNAL_SUMMARY---\s*$/m', $text, 2);

        if ($parts === false || count($parts) < 2) {
            return [
                'content' => trim($text),
                'internalSummary' => null,
            ];
        }

        $content = self::stripWrappingCodeFence($parts[0]);
        $summary = self::stripWrappingCodeFence($parts[1]);

        return [
            'content' => $content,
            'internalSummary' => $summary === '' ? null : $summary,
        ];
    }

    /**
     * Remove um eventual bloco de código Markdown (```lang ... ``` ou ~~~lang ... ~~~)
     * envolvendo o texto inteiro.
     */
    private static function stripWrappingCodeFence(string $text): string
    {
        $trimmed = trim($text);

        if (preg_match('/^(?:```|~~~)[\w+-]*\s*\n(.*)\n(?:```|~~~)\s*$/s', $trimmed, $matches)) {
            return trim($matches[1]);
        }

        return $trimmed;
    }
}
