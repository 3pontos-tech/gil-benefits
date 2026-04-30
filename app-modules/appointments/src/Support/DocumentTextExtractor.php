<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Support;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Throwable;
use TresPontosTech\Appointments\Exceptions\RecordGenerationFailedException;

readonly class DocumentTextExtractor
{
    private const MIME_PDF = 'application/pdf';

    private const MIME_DOCX = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    private const MIME_DOC = 'application/msword';

    public function extractText(UploadedFile $file): ?string
    {
        $mime = $file->getMimeType();

        if ($mime === self::MIME_PDF) {
            return null;
        }

        $reader = match ($mime) {
            self::MIME_DOCX => 'Word2007',
            self::MIME_DOC => 'MsDoc',
            default => throw RecordGenerationFailedException::unsupportedMimeType($mime ?? 'unknown'),
        };

        $tempPath = tempnam(sys_get_temp_dir(), 'appointment-record-docx-');

        if ($tempPath === false) {
            throw RecordGenerationFailedException::unreadableDocument($file->getClientOriginalName());
        }

        try {
            file_put_contents($tempPath, $file->get());

            $phpWord = IOFactory::createReader($reader)->load($tempPath);

            return $this->serialize($phpWord);
        } catch (Throwable $throwable) {
            logger()->error('IA :: falha ao extrair texto do documento Word', [
                'mime' => $mime,
                'reader' => $reader,
                'original_filename' => $file->getClientOriginalName(),
                'exception_class' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            throw RecordGenerationFailedException::unreadableDocument(
                $file->getClientOriginalName(),
                previous: $throwable,
            );
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private function serialize(PhpWord $phpWord): string
    {
        $lines = [];

        foreach ($phpWord->getSections() as $section) {
            $this->collectFromContainer($section, $lines);
        }

        $joined = implode("\n", array_filter($lines, fn (string $line): bool => trim($line) !== ''));

        return preg_replace("/\n{3,}/", "\n\n", $joined) ?? $joined;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function collectFromContainer(AbstractContainer $container, array &$lines): void
    {
        foreach ($container->getElements() as $element) {
            if ($element instanceof Text) {
                $lines[] = $element->getText();

                continue;
            }

            if ($element instanceof TextRun) {
                $buffer = '';
                foreach ($element->getElements() as $child) {
                    if ($child instanceof Text) {
                        $buffer .= $child->getText();
                    }
                }

                $lines[] = $buffer;

                continue;
            }

            if ($element instanceof ListItem) {
                $lines[] = '- ' . $element->getTextObject()->getText();

                continue;
            }

            if ($element instanceof Table) {
                foreach ($element->getRows() as $row) {
                    $cells = [];
                    foreach ($row->getCells() as $cell) {
                        $cellLines = [];
                        $this->collectFromContainer($cell, $cellLines);
                        $cells[] = trim(implode(' ', $cellLines));
                    }

                    $lines[] = implode(' | ', array_filter($cells, fn (string $c): bool => $c !== ''));
                }

                continue;
            }

            if ($element instanceof AbstractContainer) {
                $this->collectFromContainer($element, $lines);
            }
        }
    }
}
