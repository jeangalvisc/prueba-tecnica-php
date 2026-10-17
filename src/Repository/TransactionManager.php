<?php

declare(strict_types=1);

namespace App\Repository;

interface TransactionManager
{
    /**
     * Ejecuta $operacion en una transacción: commit si termina bien,
     * rollback si lanza cualquier excepción.
     *
     * @template T
     * @param callable(): T $operacion
     * @return T
     */
    public function transactional(callable $operacion): mixed;
}
