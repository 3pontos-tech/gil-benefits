<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketChannelEnum;
use TresPontosTech\Support\Models\SupportTicket;

it('backfills the tickets opened before the channel existed as panel_app', function (): void {
    $migration = require base_path('app-modules/support/database/migrations/2026_11_17_175607_add_channel_to_support_tickets_table.php');
    $migration->down();

    $ticketId = (string) Str::uuid();

    DB::table('support_tickets')->insert([
        'id' => $ticketId,
        'protocol' => 'SUP-2026-0001',
        'category' => SupportTicketCategoryEnum::Bug->value,
        'subject' => 'subject',
        'description' => 'description',
        'status' => SupportTicketStatusEnum::Resolved->value,
        'environment' => 'testing',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration->up();

    expect(SupportTicket::query()->findOrFail($ticketId)->channel)->toBe(TicketChannelEnum::PanelApp);
});
