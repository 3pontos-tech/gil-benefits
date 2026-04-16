<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use TresPontosTech\Appointments\Exceptions\RecordGenerationFailedException;
use TresPontosTech\Appointments\Support\DocumentTextExtractor;

beforeEach(function (): void {
    Log::spy();
});

afterEach(function (): void {
    foreach (glob(sys_get_temp_dir() . '/extractor-test-*') as $tmp) {
        @unlink($tmp);
    }
});

function buildDocxFixture(string $text): string
{
    $phpWord = new PhpWord;
    $section = $phpWord->addSection();
    $section->addText($text);

    $path = tempnam(sys_get_temp_dir(), 'extractor-test-') . '.docx';
    IOFactory::createWriter($phpWord, 'Word2007')->save($path);

    return $path;
}

function fakeUploadedFromPath(string $path, string $mime, string $originalName): UploadedFile
{
    $file = Mockery::mock(UploadedFile::class);
    $file->shouldReceive('getRealPath')->andReturn($path);
    $file->shouldReceive('getMimeType')->andReturn($mime);
    $file->shouldReceive('getClientOriginalName')->andReturn($originalName);
    $file->shouldReceive('get')->andReturnUsing(fn (): string => (string) file_get_contents($path));

    return $file;
}

it('retorna null para PDF (sem extração)', function (): void {
    $file = Mockery::mock(UploadedFile::class);
    $file->shouldReceive('getMimeType')->andReturn('application/pdf');

    $result = resolve(DocumentTextExtractor::class)->extractText($file);

    expect($result)->toBeNull();
});

it('extrai texto de DOCX usando PhpWord Word2007', function (): void {
    $path = buildDocxFixture('Conteúdo de teste do documento DOCX.');

    $file = fakeUploadedFromPath(
        $path,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'amostra.docx',
    );

    $text = resolve(DocumentTextExtractor::class)->extractText($file);

    expect($text)->toBeString()
        ->and($text)->toContain('Conteúdo de teste do documento DOCX.');
});

it('lança unsupportedMimeType para mime desconhecido', function (): void {
    $file = Mockery::mock(UploadedFile::class);
    $file->shouldReceive('getMimeType')->andReturn('image/png');

    resolve(DocumentTextExtractor::class)->extractText($file);
})->throws(RecordGenerationFailedException::class);

it('lança unreadableDocument quando o arquivo Word está corrompido', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'extractor-test-') . '.docx';
    file_put_contents($path, 'isto não é um docx válido');

    $file = fakeUploadedFromPath(
        $path,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'corrupted.docx',
    );

    expect(fn () => resolve(DocumentTextExtractor::class)->extractText($file))
        ->toThrow(RecordGenerationFailedException::class);

    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $msg, array $ctx = []): bool => $msg === 'IA :: falha ao extrair texto do documento Word'
            && ($ctx['original_filename'] ?? null) === 'corrupted.docx')
        ->once();
});

it('preserva o texto extraído por completo sem truncar', function (): void {
    $bigText = str_repeat('A', 200);
    $path = buildDocxFixture($bigText);

    $file = fakeUploadedFromPath(
        $path,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'grande.docx',
    );

    $text = resolve(DocumentTextExtractor::class)->extractText($file);

    expect(mb_strlen($text))->toBe(200);
});
