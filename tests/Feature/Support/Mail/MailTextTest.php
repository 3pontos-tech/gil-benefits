<?php

declare(strict_types=1);

use App\Support\Mail\MailText;

it('turns the **highlight** of the translation files into bold', function (): void {
    expect((string) MailText::inline('Consulta com **Ana Lima** confirmada.'))
        ->toBe('Consulta com <strong style="color:' . MailText::STRONG . ';">Ana Lima</strong> confirmada.');
});

it('escapes the text before adding the bold, so a name never becomes html', function (): void {
    expect((string) MailText::inline('Olá, **<script>alert(1)</script>**!'))
        ->toBe('Olá, <strong style="color:' . MailText::STRONG . ';">&lt;script&gt;alert(1)&lt;/script&gt;</strong>!');
});

it('keeps the line breaks of free text such as notes', function (): void {
    expect((string) MailText::inline("primeira\nsegunda", MailText::ACCENT))->toBe("primeira<br>\nsegunda");
});
