<?php

declare(strict_types=1);

namespace App\Infrastructure;

use PDO;

/**
 * Crea la conexión PDO con opciones seguras por defecto.
 */
final class Database
{
    public static function connect(Config $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config->require('DB_HOST'),
            (int) $config->get('DB_PORT', '3306'),
            $config->require('DB_NAME'),
        );

        return new PDO($dsn, $config->require('DB_USER'), (string) $config->get('DB_PASS', ''), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Prepared statements reales en el servidor: los parámetros nunca
            // se interpolan en el SQL, ni siquiera por el driver.
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);
    }
}
