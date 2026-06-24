<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when Monday notifies us that a column value changed on a board item.
 * Domain-agnostic: carries the raw Monday identifiers and the new status label.
 * Consumers decide whether the board/column is relevant to them.
 */
final readonly class MondayItemColumnChanged
{
    use Dispatchable;

    public function __construct(
        public string $boardId,
        public string $itemId,
        public string $columnId,
        public ?int $index,
    ) {}
}
