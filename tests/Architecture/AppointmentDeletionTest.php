<?php

declare(strict_types=1);

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;

/**
 * An appointment is cancelled, never deleted: cancelling returns the credit, notifies
 * and keeps the history. See appointments/docs/adr/2026-11-05-appointments-are-cancelled-not-deleted.md.
 */
it('offers no delete action on appointment resources', function (string $namespace): void {
    expect($namespace)->not->toUse([
        DeleteAction::class,
        DeleteBulkAction::class,
        ForceDeleteAction::class,
        ForceDeleteBulkAction::class,
    ]);
})->with([
    'admin' => 'TresPontosTech\PanelAdmin\Filament\Resources\Appointments',
    'app' => 'TresPontosTech\PanelApp\Filament\Resources\Appointments',
    'consultant' => 'TresPontosTech\PanelConsultant\Filament\Resources\Appointments',
]);
