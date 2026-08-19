<?php

declare(strict_types=1);

use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;

return [
    // Issued by us, one per environment, and handed to ChatX. Keys the HMAC that
    // signs every request.
    'webhook_secret' => env('CHATX_WEBHOOK_SECRET'),

    /*
     * Static egress addresses ChatX calls us from. IPv4, IPv6 and CIDR all work.
     * An EMPTY list turns the check off — the endpoint then rests on the signature
     * alone. That is deliberate: it lets homologation run before ChatX has told us
     * their addresses, and it means a misconfigured production env fails open on
     * this layer instead of rejecting every legitimate call. Fill it in for prod.
     */
    'allowed_ips' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('CHATX_ALLOWED_IPS', '')),
    ))),

    // How far X-Timestamp may sit from our clock, in seconds, in either direction.
    // Agreed with ChatX at 10 minutes.
    'timestamp_tolerance' => (int) env('CHATX_TIMESTAMP_TOLERANCE', 600),

    /*
     * ChatX sends its own category vocabulary as free text. Anything not listed
     * here falls back to `other` rather than failing the request — a taxonomy
     * mismatch should never cost a customer their ticket. Keys are compared
     * lowercased and trimmed.
     */
    'category_map' => [
        'financeiro' => SupportTicketCategoryEnum::FinancialIssue->value,
        'financas' => SupportTicketCategoryEnum::FinancialIssue->value,
        'cobranca' => SupportTicketCategoryEnum::FinancialIssue->value,
        'boleto' => SupportTicketCategoryEnum::FinancialIssue->value,
        'nota fiscal' => SupportTicketCategoryEnum::FinancialIssue->value,
        'reembolso' => SupportTicketCategoryEnum::FinancialIssue->value,

        'acesso' => SupportTicketCategoryEnum::LoginAccess->value,
        'login' => SupportTicketCategoryEnum::LoginAccess->value,
        'senha' => SupportTicketCategoryEnum::LoginAccess->value,

        'erro' => SupportTicketCategoryEnum::PlatformError->value,
        'plataforma' => SupportTicketCategoryEnum::PlatformError->value,
        'bug' => SupportTicketCategoryEnum::Bug->value,
        'integracao' => SupportTicketCategoryEnum::Integration->value,
        'lentidao' => SupportTicketCategoryEnum::Performance->value,
        'performance' => SupportTicketCategoryEnum::Performance->value,

        'agendamento' => SupportTicketCategoryEnum::SchedulingIssue->value,
        'consulta' => SupportTicketCategoryEnum::SchedulingIssue->value,

        'contrato' => SupportTicketCategoryEnum::ContractPlan->value,
        'plano' => SupportTicketCategoryEnum::ContractPlan->value,
        'upgrade' => SupportTicketCategoryEnum::ContractPlan->value,

        'cancelamento' => SupportTicketCategoryEnum::CancellationComplaint->value,
        'reclamacao' => SupportTicketCategoryEnum::CancellationComplaint->value,

        'sugestao' => SupportTicketCategoryEnum::SuggestionFeedback->value,
        'feedback' => SupportTicketCategoryEnum::SuggestionFeedback->value,

        'duvida' => SupportTicketCategoryEnum::GeneralQuestion->value,
        'outros' => SupportTicketCategoryEnum::Other->value,
    ],
];
