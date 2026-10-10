<?php

declare(strict_types=1);

use TresPontosTech\Support\Models\SupportTicket;

use function Pest\Laravel\getJson;

it('exposes every key the app reads from Ticket', function (): void {
    $employee = actingAsApiEmployee();
    SupportTicket::factory()->create(['user_id' => $employee->id]);

    $response = getJson(route('api.v1.tickets.index'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'protocol', 'category', 'subject', 'description', 'status', 'created_at', 'updated_at']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

    $iso = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/';

    expect($response->json('data.0.protocol'))->toMatch('/^SUP-\d{4}-\d{4}$/')
        ->and($response->json('data.0.created_at'))->toMatch($iso)
        ->and($response->json('data.0.updated_at'))->toMatch($iso);
});
