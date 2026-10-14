<?php

declare(strict_types=1);

use TresPontosTech\Api\Enums\TokenAbility;

/**
 * O valor fica gravado em cada token emitido (90 dias). Se este teste quebrar, a mudança desconecta
 * todo mundo que está logado no app: só mude junto com uma migração dos tokens ou um aviso de novo login.
 */
it('keeps the value stored in the issued tokens', function (): void {
    expect(TokenAbility::Employee->value)->toBe('employee');
});
