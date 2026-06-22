<?php

use Illuminate\Support\Facades\Date;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Support\ConsultantSyncContext;
use Zap\Facades\Zap;

beforeEach(function (): void {
    Date::setTestNow('2026-04-29 09:00:00');
    $this->consultant = Consultant::factory()->create();
});

afterEach(function (): void {
    Date::setTestNow();
});

it('resolves metadata for a preloaded blocked google event', function (): void {
    Zap::for($this->consultant)
        ->named('Block')
        ->blocked()
        ->allowOverlap()
        ->from('2026-05-15')
        ->to(null)
        ->addPeriod('14:00', '15:00')
        ->withMetadata(['google_event_id' => 'evt-1', 'source' => 'google-calendar', 'updated' => '2026-04-01T00:00:00+00:00'])
        ->save();

    $context = ConsultantSyncContext::for($this->consultant);

    expect($context->metadataForEvent('evt-1'))
        ->toMatchArray(['google_event_id' => 'evt-1', 'source' => 'google-calendar'])
        ->and($context->metadataForEvent('missing'))->toBeNull();
});

it('resolves a block by event id regardless of its source', function (): void {
    Zap::for($this->consultant)
        ->named('Legacy block')
        ->blocked()
        ->allowOverlap()
        ->from('2026-05-15')
        ->to(null)
        ->addPeriod('14:00', '15:00')
        ->withMetadata(['google_event_id' => 'legacy-1', 'source' => 'legacy-import'])
        ->save();

    expect(ConsultantSyncContext::for($this->consultant)->metadataForEvent('legacy-1'))
        ->toMatchArray(['google_event_id' => 'legacy-1', 'source' => 'legacy-import']);
});

it('ignores blocks without a google event id', function (): void {
    Zap::for($this->consultant)
        ->named('Manual block')
        ->blocked()
        ->allowOverlap()
        ->from('2026-05-15')
        ->to(null)
        ->addPeriod('14:00', '15:00')
        ->withMetadata(['source' => 'manual'])
        ->save();

    expect(ConsultantSyncContext::for($this->consultant)->metadataForEvent('manual-1'))->toBeNull();
});

it('detects an appointment overlapping the given window', function (): void {
    Zap::for($this->consultant)
        ->named('Appointment')
        ->appointment()
        ->from('2026-05-15')
        ->to('2026-05-16')
        ->addPeriod('14:00', '15:00')
        ->save();

    $context = ConsultantSyncContext::for($this->consultant);

    expect($context->hasOverlappingAppointment('2026-05-15', '2026-05-16', '14:30', '15:30'))->toBeTrue()
        ->and($context->hasOverlappingAppointment('2026-05-15', '2026-05-16', '16:00', '17:00'))->toBeFalse()
        ->and($context->hasOverlappingAppointment('2026-05-20', '2026-05-21', '14:00', '15:00'))->toBeFalse();
});

it('reports no overlap when the consultant has no appointments', function (): void {
    expect(ConsultantSyncContext::for($this->consultant)->hasOverlappingAppointment('2026-05-15', '2026-05-16', '14:00', '15:00'))->toBeFalse();
});
