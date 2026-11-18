<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Mail\SupportTicketConfirmationMail;
use TresPontosTech\Support\Mail\SupportTicketMail;
use TresPontosTech\Support\Mail\SupportTicketStatusUpdatedMail;
use TresPontosTech\Support\Models\SupportTicket;

dataset('support_mailables', [
    'confirmation to the requester' => [SupportTicketConfirmationMail::class],
    'new ticket to the team' => [SupportTicketMail::class],
    'status update to the requester' => [SupportTicketStatusUpdatedMail::class],
]);

describe('support emails', function (): void {
    it('shows the protocol and the requester of a visitor ticket', function (string $mailable): void {
        $ticket = SupportTicket::factory()->create([
            'protocol' => 'SUP-2026-0042',
            'company_id' => null,
            'visitor_name' => 'Joana Visitante',
            'visitor_email' => 'joana@visitante.com',
            'status' => SupportTicketStatusEnum::InProgress,
        ]);

        $html = (new $mailable($ticket))->render();

        expect($html)->toContain('SUP-2026-0042')->toContain('Joana Visitante');
    })->with('support_mailables');

    it('shows the protocol and the requester of a signed-in user ticket', function (string $mailable): void {
        $ticket = SupportTicket::factory()->create([
            'protocol' => 'SUP-2026-0043',
            'user_id' => User::factory()->create(['name' => 'Paula Araujo'])->getKey(),
            'status' => SupportTicketStatusEnum::InProgress,
        ]);

        $html = (new $mailable($ticket))->render();

        expect($html)->toContain('SUP-2026-0043')->toContain('Paula Araujo');
    })->with('support_mailables');

    it('escapes what the requester typed and turns no markdown into a link', function (): void {
        $ticket = SupportTicket::factory()->create([
            'subject' => 'Erro <b>grave</b>',
            'description' => "<script>alert(1)</script>\n[clique aqui](https://evil.example)",
        ]);

        $html = (new SupportTicketMail($ticket))->render();

        expect($html)->not->toContain('<script>alert(1)</script>')
            ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
            ->not->toContain('<b>grave</b>')
            ->not->toContain('href="https://evil.example"');
    });
});
