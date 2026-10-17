<?php

declare(strict_types=1);

namespace App\Repository\Pdo;

use App\Repository\TransactionManager;
use PDO;
use Throwable;

final class PdoTransactionManager implements TransactionManager
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function transactional(callable $operacion): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $resultado = $operacion();
            $this->pdo->commit();

            return $resultado;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
