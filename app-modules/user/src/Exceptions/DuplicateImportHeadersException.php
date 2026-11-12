<?php

declare(strict_types=1);

namespace TresPontosTech\User\Exceptions;

use RuntimeException;

class DuplicateImportHeadersException extends RuntimeException
{
    /**
     * @param  list<string>  $headers
     */
    public function __construct(public readonly array $headers)
    {
        parent::__construct(sprintf(
            'Estrutura inválida. Colunas repetidas: %s. Deixe cada coluna uma vez só no cabeçalho.',
            implode(', ', $headers),
        ));
    }
}
