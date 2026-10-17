<?php

declare(strict_types=1);

namespace App\Infrastructure;

use DateTimeImmutable;

/**
 * Abstracción del reloj para que las reglas que dependen de "hoy"
 * (p. ej. no permitir reservas en el pasado) sean testeables.
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}
